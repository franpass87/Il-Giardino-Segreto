jQuery(function ($) {
    const container = $('#igs-date-ranges');
    const addButton = $('.igs-add-date-range');

    const settings = window.igsProductMeta || {};

    function initDatepicker(scope) {
        scope.find('.igs-date-field').datepicker({
            dateFormat: settings.dateFormat || 'dd/mm/yy',
            minDate: 0,
        });
    }

    initDatepicker(container);

    addButton.on('click', function () {
        const row = $('<div />', { class: 'igs-date-range-row' });

        $('<input />', {
            type: 'text',
            name: 'date_ranges[start][]',
            class: 'igs-date-field igs-date-start',
            placeholder: settings.startLabel || '',
            autocomplete: 'off',
        }).appendTo(row);

        $('<input />', {
            type: 'text',
            name: 'date_ranges[end][]',
            class: 'igs-date-field igs-date-end',
            placeholder: settings.endLabel || '',
            autocomplete: 'off',
        }).appendTo(row);

        $('<button />', {
            type: 'button',
            class: 'button button-link igs-remove-date-range',
            text: settings.removeLabel || '×',
        }).appendTo(row);

        container.append(row);
        initDatepicker(row);
    });

    container.on('click', '.igs-remove-date-range', function (event) {
        event.preventDefault();

        if (container.children().length > 0 && window.confirm(settings.confirmRemove || '')) {
            $(this).closest('.igs-date-range-row').remove();
        }
    });
});
