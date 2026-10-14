"""Real HTTP acceptance checks. Only runs against the local fixture store."""
import concurrent.futures
import http.cookiejar
import json
import sys
import urllib.error
import urllib.parse
import urllib.request
import uuid
from pathlib import Path

BASE = 'http://localhost'
API = BASE + '/wp-json/storefuse/v1'


class Client:
    def __init__(self):
        self.cookies = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.cookies))
        self.wp = None
        self.wc = None

    def call(self, path, data=None, headers=None, absolute=False):
        headers = dict(headers or {})
        if self.wp:
            headers.setdefault('X-WP-Nonce', self.wp)
        if self.wc:
            headers.setdefault('X-WC-Nonce', self.wc)
        if data is not None:
            headers['Content-Type'] = 'application/json'
        req = urllib.request.Request(path if absolute else API + path,
                                     data=None if data is None else json.dumps(data).encode(), headers=headers)
        try:
            response = self.opener.open(req, timeout=40)
        except urllib.error.HTTPError as error:
            response = error
        raw = response.read()
        try:
            body = json.loads(raw)
        except ValueError:
            raise AssertionError(f'{path}: non-JSON response, status {response.status}') from None
        payload = body.get('data') or {}
        self.wp = response.headers.get('X-WP-Nonce') or payload.get('nonce') or self.wp
        self.wc = response.headers.get('X-WC-Nonce') or payload.get('cart_nonce') or self.wc
        return response.status, body, response.headers

    def bootstrap(self):
        req = urllib.request.Request(BASE + '/wp-admin/admin-ajax.php',
                                     data=b'action=storefuse_session',
                                     headers={'Content-Type': 'application/x-www-form-urlencoded'})
        body = json.load(self.opener.open(req, timeout=30))
        assert body['success'], 'Session bootstrap failed'
        self.wp, self.wc = body['data']['nonce'], body['data']['cart_nonce']
        return body['data']


def main():
    fixtures = json.loads(Path(sys.argv[1]).read_text())
    a, b = Client(), Client()
    status, catalog, _ = a.call('/products?per_page=100')
    assert status == 200
    def detail(product):
        status, data, _ = Client().call('/products/' + product['slug'])
        assert status == 200, f'Product detail failed: {product["slug"]}'
        assert data['data']['id'] == product['id']
    with concurrent.futures.ThreadPoolExecutor(max_workers=4) as pool:
        list(pool.map(detail, catalog['data']['items']))
    status, _, _ = a.call('/search?q=StoreFuse')
    assert status == 200, 'Image-less product search failed'
    print('PASS all local product details and image-less product search')
    a.bootstrap()
    status, _, _ = a.call('/account')
    assert status == 401, f'Anonymous account returned {status}'
    for client, account in zip([a, b], fixtures['accounts']):
        client.bootstrap()
        status, body, _ = client.call('/auth/login', {**account, 'remember': True})
        assert status == 200, f'Login failed: {body.get("error")}'
        status, body, _ = client.call('/auth/me')
        assert status == 200 and body['data']['logged_in'], 'Login nonce does not survive next request'
        assert int(body['data']['id']) == account['id']
        client.wp = None  # Restart: cookies survive but nonce memory does not.
        assert client.bootstrap()['logged_in'], 'Cold-start session bootstrap lost authentication'
        status, _, _ = client.call('/account')
        assert status == 200, 'Authenticated read failed'
    print('PASS authenticated reads, two isolated customers, and cold-start nonce recovery')

    status, body, _ = a.call('/cart/add', {'product_id': fixtures['products']['simple'], 'quantity': 1})
    assert status == 200, f'Add to cart: {body.get("error")}'
    address = {'first_name': 'Local', 'last_name': 'Audit', 'email': fixtures['accounts'][0]['email'],
               'phone': '9999999999', 'country': 'IN', 'state': 'MH', 'city': 'Mumbai',
               'postcode': '400001', 'address_1': '1 Test Street', 'address_2': '', 'company': ''}
    payload = {'billing': address, 'shipping': address, 'payment_method': 'cod',
               'shipping_method': [], 'terms': True, 'ship_to_different_address': False}
    status, quote, _ = a.call('/checkout/quote', payload)
    assert status == 200, f'Quote: {quote.get("error")}'
    packages = quote['data']['packages']
    assert packages and all(p['methods'] for p in packages), 'No test shipping rate'
    status, result, _ = a.call('/checkout', payload, {'Idempotency-Key': uuid.uuid4().hex})
    assert status == 422 or status == 400, f'Missing shipping validation returned {status}: {result.get("error")}'
    payload['shipping_method'] = [p['methods'][0]['id'] for p in packages]
    status, quote, _ = a.call('/checkout/quote', payload)
    assert status == 200
    payload['quote_token'] = quote['data']['quote_token']
    key = uuid.uuid4().hex
    def checkout_once(_):
        parallel = Client()
        for cookie in a.cookies:
            parallel.cookies.set_cookie(cookie)
        parallel.wp, parallel.wc = a.wp, a.wc
        return parallel.call('/checkout', payload, {'Idempotency-Key': key})
    with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
        outcomes = list(pool.map(checkout_once, range(2)))
    successes = [result for result in outcomes if result[0] == 200]
    assert successes, f'Concurrent checkout failed: {[r[1].get("error") for r in outcomes]}'
    assert len({r[1]['data']['order_id'] for r in successes}) == 1, 'Concurrent requests created different orders'
    assert all(r[0] in (200, 409, 429) for r in outcomes), 'Unexpected concurrent checkout failure'
    status, order, _ = successes[0]
    assert status == 200, f'Checkout failed: {order.get("error")}'
    order_key = order['data']['order_key']
    status, replay, _ = a.call('/checkout', payload, {'Idempotency-Key': key})
    assert status == 200 and replay['data']['order_key'] == order_key, 'Empty-cart replay failed'
    status, _, _ = b.call('/orders/' + str(order['data']['order_id']))
    assert status == 403, 'Cross-customer order read was not denied'
    status, _, _ = b.call('/orders/' + order_key)
    assert status == 403, 'Cross-customer order-key read was not denied'
    print('PASS delivery validation, COD checkout, empty-cart recovery and order ownership')

    # Native cookies must become HttpOnly browser cookies through a one-time POST.
    status, _, _ = a.call('/cart/add', {'product_id': fixtures['products']['virtual'], 'quantity': 1})
    assert status == 200
    status, handoff, _ = a.call('/checkout/redirect-url', {})
    assert status == 200, f'Handoff issue failed: {handoff.get("error")}'
    token = handoff['data']['handoff_token']
    browser = Client()
    req = urllib.request.Request(handoff['data']['handoff_url'],
                                 data=urllib.parse.urlencode({'token': token}).encode(),
                                 headers={'Content-Type': 'application/x-www-form-urlencoded'})
    response = browser.opener.open(req, timeout=40)
    response.read()
    assert browser.bootstrap()['logged_in'], 'Handoff lost authenticated customer'
    status, browser_cart, _ = browser.call('/cart')
    assert status == 200 and browser_cart['data']['items'], 'Handoff lost cart'
    auth_cookies = [c for c in browser.cookies if c.name.startswith('wordpress_logged_in')]
    assert auth_cookies and all(c.has_nonstandard_attr('HttpOnly') for c in auth_cookies), 'Handoff dropped HttpOnly'
    try:
        browser.opener.open(req, timeout=40)
        raise AssertionError('Handoff token accepted a second time')
    except urllib.error.HTTPError as error:
        assert error.code == 403
    print('PASS simultaneous checkout, HttpOnly browser handoff, and single-use token')

    status, _, headers = a.call('/status', headers={'Origin': 'https://untrusted.example'})
    assert 'Access-Control-Allow-Origin' not in headers, 'Denied CORS origin was reflected'
    status, _, _ = a.call('/auth/logout', {})
    assert status == 200, 'Logout failed'
    a.wp = None
    assert not a.bootstrap()['logged_in'], 'Logout retained authentication'
    print('PASS denied CORS and logout')


if __name__ == '__main__':
    main()
