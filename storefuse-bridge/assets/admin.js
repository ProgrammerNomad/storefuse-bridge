/* global jQuery, sfbAdmin, wp */
(function ($) {
    'use strict';

    // ── Color pickers ────────────────────────────────────────────────────
    $(function () {
        $('.sfb-color-picker').wpColorPicker();
    });

    // ── Copy API URL ─────────────────────────────────────────────────────
    $(document).on('click', '.sfb-copy-btn', function () {
        var targetId = $(this).data('target');
        var text     = $('#' + targetId).text();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function () {
                var $btn = $('.sfb-copy-btn');
                $btn.text('Copied!');
                setTimeout(function () { $btn.text('Copy'); }, 2000);
            });
        }
    });

    // ── Flush cache ──────────────────────────────────────────────────────
    $(document).on('click', '#sfb-flush-cache', function () {
        var $btn    = $(this);
        var $result = $('#sfb-flush-result');

        $btn.prop('disabled', true).text('Flushing…');
        $result.text('');

        $.post(sfbAdmin.flushUrl, {
            action : 'storefuse_bridge_flush_cache',
            nonce  : sfbAdmin.nonce
        }, function (res) {
            $btn.prop('disabled', false).text('Flush All Cache');
            if (res.success) {
                $result.css('color', '#16a34a').text('Done: ' + res.data.message);
            } else {
                $result.css('color', '#dc2626').text('Error flushing cache.');
            }
            setTimeout(function () { $result.text(''); }, 4000);
        });
    });

    // ── Hero image media picker ──────────────────────────────────────────
    var heroFrame;
    $(document).on('click', '#sfb-hero-upload', function (e) {
        e.preventDefault();
        if (heroFrame) { heroFrame.open(); return; }
        heroFrame = wp.media({
            title  : 'Select Hero Image',
            button : { text: 'Use this image' },
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

    // ── Trust badges editor ──────────────────────────────────────────────
    function serializeBadges() {
        var badges = [];
        $('#sfb-trust-badges .sfb-trust-badge-row').each(function () {
            var $row = $(this);
            badges.push({
                icon        : $row.find('input[name*="[icon]"]').val(),
                title       : $row.find('input[name*="[title]"]').val(),
                description : $row.find('input[name*="[description]"]').val()
            });
        });
        $('#sfb-trust-badges-json').val(JSON.stringify(badges));
    }

    $(document).on('input', '#sfb-trust-badges input', serializeBadges);

    $(document).on('click', '#sfb-add-badge', function () {
        var idx  = $('#sfb-trust-badges .sfb-trust-badge-row').length;
        var html = '<div class="sfb-trust-badge-row" data-index="' + idx + '">' +
            '<input type="text" name="sfb_badges[' + idx + '][icon]" class="small-text" placeholder="Icon" title="Icon" />' +
            '<input type="text" name="sfb_badges[' + idx + '][title]" class="regular-text" placeholder="Title" title="Title" />' +
            '<input type="text" name="sfb_badges[' + idx + '][description]" class="regular-text" placeholder="Description" title="Description" />' +
            '<button type="button" class="button sfb-remove-badge">✕</button>' +
            '</div>';
        $('#sfb-trust-badges').append(html);
        serializeBadges();
    });

    $(document).on('click', '.sfb-remove-badge', function () {
        $(this).closest('.sfb-trust-badge-row').remove();
        serializeBadges();
    });

    // Serialize before form submit
    $('form').on('submit', serializeBadges);

}(jQuery));
