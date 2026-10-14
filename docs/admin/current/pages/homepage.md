# Homepage

## Purpose

Merchandising content for `GET /homepage`: hero (web landing), featured categories, best sellers, new arrivals, promo banner. Flutter uses product sections + App Layout for section order/hide; hero is primarily for Next.js/web.

## Menu

| | |
|--|--|
| **Slug** | `storefuse-bridge-homepage` |
| **View** | `admin/views/page-homepage.php` |
| **v2** | Same slug, same page |

## v2 visibility

- **Hero card:** show for `nextjs` / `other`; for `flutter` hide fields + info box linking to App Layout  
- **App preview `<details>`:** Flutter only  

## Page layout

1. Description + link to General (announcement) + live `/homepage` link  
2. **Preview saved app content** (`<details>`) - `#sfb-app-preview`, `#sfb-preview-load`  
3. **Hero section**  
4. **Featured categories** (builder, max 6)  
5. **Best sellers**  
6. **New arrivals**  
7. **Promo banner**  

## Fields - Hero

| Label | Key | ID | API `hero.*` |
|-------|-----|-----|--------------|
| Badge text | `hero_badge_text` | `sfb_hero_badge` | `badge_text` |
| Headline | `hero_headline` | `sfb_hero_headline` | `headline` |
| Highlight | `hero_headline_highlight` | `sfb_hero_highlight` | `headline_highlight` |
| Subheadline | `hero_subheadline` | `sfb_hero_sub` | `subheadline` |
| Primary CTA label/href | `hero_cta_primary_label`, `hero_cta_primary_href` | - | `cta_primary_*` |
| Secondary CTA label/href | `hero_cta_secondary_label`, `hero_cta_secondary_href` | - | |
| Hero image | `hero_image_id` | `sfb-hero-image-id` | `image` (resolved) |
| Rating text | `hero_rating_text` | `sfb_hero_rating` | `rating_text` |
| Shipping text | `hero_shipping_text` | `sfb_hero_shipping` | `shipping_text` |

## Fields - Featured categories

Hidden JSON: `featured_categories`. Builder rows: category select, label, icon, color (`#sfb-featured-categories`, `#sfb-add-featured-cat`).

## Fields - Best sellers

| Key | ID | Notes |
|-----|-----|-------|
| `homepage_best_sellers_heading` | `sfb_bs_heading` | |
| `homepage_best_sellers_source` | `sfb_bs_source` | best-selling \| featured \| manual |
| `homepage_best_sellers_count` | `sfb_bs_count` | 1–24 |
| `homepage_best_sellers_ids` | `sfb_bs_ids` | CSV; `#sfb-product-selection` multi-select when manual |

## Fields - New arrivals

| Key | ID |
|-----|-----|
| `homepage_new_arrivals_heading` | `sfb_na_heading` |
| `homepage_new_arrivals_count` | `sfb_na_count` |
| `homepage_new_arrivals_category` | `sfb_na_cat` |

## Fields - Promo banner

| Key | ID |
|-----|-----|
| `homepage_promo_banner_enabled` | checkbox |
| `homepage_promo_banner_headline` | `sfb_promo_headline` |
| `homepage_promo_banner_body` | `sfb_promo_body` |
| `homepage_promo_banner_cta_label`, `_cta_href` | |
| `homepage_promo_banner_bg_color` | `sfb_promo_color` |

## JavaScript

- Hero: `#sfb-hero-upload`, `#sfb-hero-remove`, `#sfb-hero-preview` (wp.media)  
- Featured categories: serialize to JSON on change/submit  
- Collapsible cards on homepage (all but first collapsed)  
- Preview: GET `{apiBase}/homepage`  

## Flutter mapping

| Homepage API block | Flutter home section ID |
|--------------------|-------------------------|
| announcement_bar | `announcement_bar` |
| hero | `hero` (often hidden via App Layout) |
| featured_categories | `featured_categories` |
| trust_items | `trust_items` |
| best_sellers | `best_sellers` |
| new_arrivals | `new_arrivals` |
| promo_banner | `promo_banner` |

Order/hide: [mobile-app.md](mobile-app.md) → `app_ui_home_*`.
