jQuery(function ($) {
    function addComposerButtons() {
        const plugins = [];
        $('.plugin-card').each(function () {
            const $card = $(this),
                $install = $card.find('.install-now');
            if (!$install.length || $card.find('.bedrock-btn').length) return;
            const slug = $install.data('slug'),
                name = $card.find('.name').text().trim();
            if (!slug || !name) return;
            plugins.push({
                slug,
                name,
                card: $card,
                install: $install
            });
        });

        if (plugins.length === 0) return;

        $.ajax({
            url: bedrock_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'bedrock_check_composer',
                plugin_slugs: plugins.map(p => p.slug),
                nonce: bedrock_ajax.nonce
            },
            success: function (r) {
                plugins.forEach(plugin => {
                    const exists = r.data.existing_plugins.includes(plugin.slug);
                    if (exists) {
                        plugin.install.after($('<button>', {
                            class: 'button bedrock-btn disabled',
                            text: bedrock_ajax.added_text,
                            disabled: true
                        }));
                    } else {
                        plugin.install.after($('<button>', {
                            class: 'button bedrock-btn',
                            text: bedrock_ajax.add_text,
                            'data-slug': plugin.slug,
                            'data-name': plugin.name
                        }));
                    }
                });
            }
        });
    }

    addComposerButtons();

    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.addedNodes.length > 0) {
                $(mutation.addedNodes).find('.plugin-card').each(function () {
                    if (!$(this).find('.bedrock-btn').length) {
                        setTimeout(addComposerButtons, 100);
                        return false;
                    }
                });
            }
        });
    });

    observer.observe(document.querySelector('#plugin-filter'), {
        childList: true,
        subtree: true
    });

    $(document).on('click', '.bedrock-btn:not(.disabled)', function (e) {
        e.preventDefault();
        const $btn = $(this),
            slug = $btn.data('slug'),
            name = $btn.data('name');
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).text(bedrock_ajax.adding_text);
        $.ajax({
            url: bedrock_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'bedrock_composer_install',
                plugin_slug: slug,
                plugin_name: name,
                nonce: bedrock_ajax.nonce
            },
            success: function (r) {
                if (r.success) {
                    $btn.addClass('disabled').text(bedrock_ajax.added_text);
                    $('<div class="notice notice-success is-dismissible"><p>' + r.data.message + '</p></div>').insertAfter('.wp-header-end');
                } else {
                    $btn.prop('disabled', false).text(bedrock_ajax.add_text);
                    alert('Error: ' + r.data.message);
                }
            },
            error: () => {
                $btn.prop('disabled', false).text(bedrock_ajax.add_text);
                alert('Installation failed');
            }
        });
    });
});