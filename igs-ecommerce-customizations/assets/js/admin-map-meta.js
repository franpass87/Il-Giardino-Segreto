jQuery(function ($) {
    const settings = window.igsMapMeta || {};
    const container = $('#igs-map-stops');
    const template = $('#igs-map-stop-template').html();
    const addButton = $('.igs-add-map-stop');

    function refreshIndices() {
        container.find('.igs-map-stop').each(function (index) {
            $(this)
                .find('input, textarea')
                .each(function () {
                    const name = $(this).attr('name');

                    if (!name) {
                        return;
                    }

                    $(this).attr('name', name.replace(/mappa_tappe\[[^\]]+]/g, 'mappa_tappe[' + index + ']'));
                });
        });
    }

    function appendStop(data = {}) {
        const placeholder = '__index__';
        const html = template.replace(new RegExp(placeholder, 'g'), container.children().length);
        const node = $(html);

        node.find('input[name*="[nome]"]').val(data.nome || '');
        node.find('input[name*="[lat]"]').val(data.lat || '');
        node.find('input[name*="[lon]"]').val(data.lon || '');
        node.find('textarea[name*="[descrizione]"]').val(data.descrizione || '');

        container.append(node);
        refreshIndices();
    }

    if (container.children().length === 0) {
        appendStop();
    }

    addButton.on('click', function (event) {
        event.preventDefault();
        appendStop();
    });

    container.on('click', '.igs-remove-map-stop', function (event) {
        event.preventDefault();

        if (!window.confirm(settings.removeConfirm || '')) {
            return;
        }

        $(this).closest('.igs-map-stop').remove();
        refreshIndices();
    });

    container.on('click', '.igs-find-coordinates', function (event) {
        event.preventDefault();

        const button = $(this);
        const stop = button.closest('.igs-map-stop');
        const nameField = stop.find('input[name*="[nome]"]');
        const latField = stop.find('input[name*="[lat]"]');
        const lonField = stop.find('input[name*="[lon]"]');
        const query = (nameField.val() || '').trim();

        if (!query) {
            window.alert(settings.emptyQuery || '');
            return;
        }

        button.prop('disabled', true).text(settings.fetching || '...');

        $.post(settings.ajaxUrl, {
            action: 'igs_lookup_coordinates',
            nonce: settings.nonce,
            query,
        })
            .done(function (response) {
                if (response.success && response.data) {
                    latField.val(response.data.lat || '');
                    lonField.val(response.data.lon || '');
                } else {
                    const message = response && response.data && response.data.message ? response.data.message : settings.noResults || '';
                    window.alert(message);
                }
            })
            .fail(function () {
                window.alert(settings.errorMessage || '');
            })
            .always(function () {
                button.prop('disabled', false).text(settings.findLabel || '');
            });
    });
});
