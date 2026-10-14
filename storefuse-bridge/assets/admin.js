/* global jQuery, sfbAdmin, wp */
(function ($) {
    'use strict';

    var i18n = sfbAdmin.i18n || {};

    $(function () {
        $('.sfb-color-picker').wpColorPicker();
        initCheckoutPanels();
        initHomepageAccordions();
        var dirty = false;
        $('.sfb-admin form').on('change input', ':input', function () { dirty = true; });
        $('.sfb-admin form').on('submit', function () { dirty = false; });
        $(window).on('beforeunload', function (event) {
            if (dirty) { event.preventDefault(); event.originalEvent.returnValue = ''; }
        });
        $('#sfb-product-selection').on('change', function () {
            $('#sfb_bs_ids').val(($(this).val() || []).join(',')).trigger('change');
        });
        $('#sfb-preview-load').on('click', function () {
            var preview = $('#sfb-app-preview').empty().text('Loading saved content…');
            $.getJSON(sfbAdmin.apiBase + '/homepage').done(function (response) {
                var data = response.data || {}, hero = data.hero || {};
                preview.empty();
                if (data.announcement_bar && data.announcement_bar.enabled) {
                    $('<p>').text(data.announcement_bar.text || '').appendTo(preview);
                }
                $('<h2>').text(hero.headline || 'Welcome to your store').appendTo(preview);
                $('<p>').text(hero.subheadline || '').appendTo(preview);
                $('<p>').text(hero.cta_primary_label || 'Shop products').addClass('button').appendTo(preview);
                $('<h3>').text((data.best_sellers || {}).heading || 'Best Sellers').appendTo(preview);
                $('<p>').text('Product selection: ' + ((data.best_sellers || {}).source || 'best-selling')).appendTo(preview);
                $('<h3>').text((data.new_arrivals || {}).heading || 'New Arrivals').appendTo(preview);
            }).fail(function () { preview.text('Preview could not load. Check that the settings module is enabled.'); });
        });
    });

    function initHomepageAccordions() {
        if (!$('#sfb-app-preview').length) {
            return;
        }
        $('.sfb-card').not('details').addClass('sfb-collapsible').each(function (i) {
            if (i > 0) {
                $(this).addClass('sfb-collapsed');
            }
        });
        $('.sfb-collapsible > h2').each(function () { $(this).attr({tabindex: '0', role: 'button', 'aria-expanded': $(this).closest('.sfb-card').hasClass('sfb-collapsed') ? 'false' : 'true'}); });
        $(document).on('keydown', '.sfb-collapsible > h2', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); } });
        $(document).on('click', '.sfb-collapsible > h2', function () {
            $(this).attr('aria-expanded', $(this).closest('.sfb-card').hasClass('sfb-collapsed') ? 'true' : 'false');
            $(this).closest('.sfb-card').toggleClass('sfb-collapsed');
        });
    }

    function initCheckoutPanels() {
        var $radios = $('input[name="storefuse_bridge_settings[checkout_mode]"]');
        var $panelRedir = $('#sfb-panel-redirect');
        var $panelHead = $('#sfb-panel-headless');
        if (!$radios.length) {
            return;
        }
        function toggle() {
            var val = $('input[name="storefuse_bridge_settings[checkout_mode]"]:checked').val();
            $panelRedir.toggle(val === 'redirect');
            $panelHead.toggle(val === 'headless');
        }
        $radios.on('change', toggle);
        toggle();
    }

    $(document).on('click', '.sfb-copy-btn', function () {
        var targetId = $(this).data('target');
        var text = $('#' + targetId).text();
        if (navigator.clipboard && text) {
            navigator.clipboard.writeText(text);
            var $btn = $(this);
            var prev = $btn.text();
            $btn.text('Copied!');
            setTimeout(function () { $btn.text(prev); }, 2000);
        }
    });

    function postAjax(action, data, done) {
        data = data || {};
        data.action = action;
        data.nonce = sfbAdmin.nonce;
        $.post(sfbAdmin.flushUrl, data, done);
    }

    function flushGroup(group, $result, $btn, defaultLabel) {
        if ($btn) {
            $btn.prop('disabled', true);
        }
        if ($result) {
            $result.text(i18n.flushing || 'Flushing…');
        }
        postAjax('storefuse_bridge_flush_cache_group', { group: group }, function (res) {
            if ($btn) {
                $btn.prop('disabled', false);
                if (defaultLabel) {
                    $btn.text(defaultLabel);
                }
            }
            if (!$result) {
                return;
            }
            if (res.success) {
                $result.css('color', '#16a34a').text(res.data.message || i18n.flushDone);
            } else {
                $result.css('color', '#dc2626').text(i18n.flushError || 'Error');
            }
            setTimeout(function () { $result.text(''); }, 5000);
        });
    }

    $(document).on('click', '#sfb-flush-cache', function () {
        if (!confirm(i18n.confirmFlush || 'Flush all StoreFuse Bridge cache?')) {
            return;
        }
        var $btn = $(this);
        var group = $btn.data('group') || 'all';
        flushGroup(group, $('#sfb-flush-result'), $btn, 'Flush All Cache');
    });

    $(document).on('click', '.sfb-flush-group', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var group = $btn.data('group');
        var $result = $('#sfb-flush-group-result');
        if ($btn.attr('id') === 'sfb-flush-nav-cache') {
            $result = $('#sfb-flush-nav-result');
        }
        flushGroup(group, $result, $btn, null);
    });

    $(document).on('click', '#sfb-test-webhook', function () {
        var $btn = $(this);
        var $result = $('#sfb-test-webhook-result');
        $btn.prop('disabled', true);
        $result.text(i18n.testing || 'Testing…');
        postAjax('storefuse_bridge_test_webhook', {}, function (res) {
            $btn.prop('disabled', false);
            if (res.success) {
                $result.css('color', '#16a34a').text((i18n.testOk || 'OK') + ' HTTP ' + (res.data.status || ''));
            } else {
                var msg = (res.data && res.data.error) ? res.data.error : (i18n.testFail || 'Failed');
                $result.css('color', '#dc2626').text(msg);
            }
        });
    });

    $(document).on('click', '#sfb-clear-webhook-log', function () {
        if (!confirm('Clear the webhook delivery log?')) {
            return;
        }
        var $btn = $(this);
        $btn.prop('disabled', true);
        postAjax('storefuse_bridge_clear_webhook_log', {}, function (res) {
            if (res.success) {
                location.reload();
            } else {
                $btn.prop('disabled', false);
            }
        });
    });

    var heroFrame;
    $(document).on('click', '#sfb-hero-upload', function (e) {
        e.preventDefault();
        if (heroFrame) {
            heroFrame.open();
            return;
        }
        heroFrame = wp.media({
            title: 'Select Hero Image',
            button: { text: 'Use this image' },
            multiple: false
        });
        heroFrame.on('select', function () {
            var attachment = heroFrame.state().get('selection').first().toJSON();
            $('#sfb-hero-image-id').val(attachment.id);
            $('#sfb-hero-preview').attr('src', attachment.url).show();
            $('#sfb-hero-remove').show();
        });
        heroFrame.open();
    });

    $(document).on('click', '#sfb-hero-remove', function (e) {
        e.preventDefault();
        $('#sfb-hero-image-id').val('');
        $('#sfb-hero-preview').attr('src', '').hide();
        $(this).hide();
    });

    function serializeBadges() {
        var badges = [];
        $('#sfb-trust-badges .sfb-trust-badge-row').each(function () {
            var $row = $(this);
            badges.push({
                enabled: $row.find('.sfb-badge-enabled-cb').is(':checked'),
                icon: $row.find('input[name*="[icon]"]').val(),
                title: $row.find('input[name*="[title]"]').val(),
                description: $row.find('input[name*="[description]"]').val()
            });
        });
        $('#sfb-trust-badges-json').val(JSON.stringify(badges));
    }

    $(document).on('input change', '#sfb-trust-badges input', serializeBadges);

    $(document).on('click', '#sfb-add-badge', function () {
        var idx = $('#sfb-trust-badges .sfb-trust-badge-row').length;
        var html = '<div class="sfb-trust-badge-row" data-index="' + idx + '">' +
            '<label class="sfb-badge-enabled"><input type="checkbox" class="sfb-badge-enabled-cb" checked /></label>' +
            '<input type="text" name="sfb_badges[' + idx + '][icon]" class="small-text" placeholder="Icon" />' +
            '<input type="text" name="sfb_badges[' + idx + '][title]" class="regular-text" placeholder="Title" />' +
            '<input type="text" name="sfb_badges[' + idx + '][description]" class="regular-text" placeholder="Description" />' +
            '<button type="button" class="button sfb-remove-badge">✕</button></div>';
        $('#sfb-trust-badges').append(html);
        serializeBadges();
    });

    $(document).on('click', '.sfb-remove-badge', function () {
        $(this).closest('.sfb-trust-badge-row').remove();
        serializeBadges();
    });

    function serializeFeaturedCategories() {
        var items = [];
        $('#sfb-featured-categories .sfb-featured-cat-row').each(function () {
            var $row = $(this);
            var catId = $row.find('.sfb-cat-select').val();
            if (!catId) {
                return;
            }
            items.push({
                category_id: parseInt(catId, 10),
                label: $row.find('.sfb-cat-label').val(),
                icon: $row.find('.sfb-cat-icon').val(),
                color: $row.find('.sfb-cat-color').val()
            });
        });
        if (items.length > 6) {
            items = items.slice(0, 6);
        }
        $('#sfb-featured-categories-json').val(JSON.stringify(items));
    }

    $(document).on('change input', '#sfb-featured-categories input, #sfb-featured-categories select', serializeFeaturedCategories);

    $(document).on('click', '#sfb-add-featured-cat', function () {
        if ($('#sfb-featured-categories .sfb-featured-cat-row').length >= 6) {
            return;
        }
        var tpl = document.getElementById('sfb-featured-cat-template');
        if (!tpl || !tpl.content) {
            return;
        }
        $('#sfb-featured-categories').append(tpl.content.cloneNode(true));
        $('#sfb-featured-categories .sfb-featured-cat-row:last .sfb-color-picker').wpColorPicker();
        serializeFeaturedCategories();
    });

    $(document).on('click', '.sfb-remove-featured-cat', function () {
        $(this).closest('.sfb-featured-cat-row').remove();
        serializeFeaturedCategories();
    });

    $('form').on('submit', function () {
        serializeBadges();
        serializeFeaturedCategories();
    });

}(jQuery));
