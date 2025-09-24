jQuery(function ($) {
    const settings = window.igsBookingModal || {};
    const modal = $('#igs-booking-modal');
    const ctaButton = $('#igs-booking-cta').find('button');
    const dialog = modal.find('.igs-booking-modal__dialog');
    const optionsWrapper = $('#igs-booking-options');
    const quantityInput = $('#igs-booking-quantity');
    const totalEl = $('#igs-booking-total');
    const infoMessage = $('#igs-booking-info-success');
    const bookingView = $('.igs-booking-view[data-view="booking"]');
    const infoView = $('.igs-booking-view[data-view="info"]');
    const html = $('html');

    let selectedVariation = null;
    let unitPrice = 0;

    function formatPrice(value) {
        const locale = settings.locale || 'it-IT';
        const formatter = new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: settings.currency || 'EUR',
            minimumFractionDigits: 2,
        });

        return formatter.format(value);
    }

    function getVariations() {
        let fromDataset = optionsWrapper.data('options');

        if (!Array.isArray(fromDataset)) {
            const attr = optionsWrapper.attr('data-options');
            if (attr) {
                try {
                    fromDataset = JSON.parse(attr);
                } catch (error) {
                    fromDataset = [];
                }
            }
        }

        if (Array.isArray(fromDataset) && fromDataset.length) {
            return fromDataset;
        }
        return settings.variations || [];
    }

    function renderOptions() {
        const variations = getVariations();

        optionsWrapper.empty();

        selectedVariation = null;
        unitPrice = 0;

        variations.forEach(function (variation, index) {
            const optionId = 'igs-variation-' + variation.id;
            const wrapper = $('<label />', {
                class: 'igs-booking-option',
                for: optionId,
            });

            const input = $('<input />', {
                type: 'radio',
                id: optionId,
                name: 'variation_id',
                value: variation.id,
                'data-price': variation.price,
            });

            if (0 === index) {
                input.prop('checked', true);
                selectedVariation = variation.id;
                unitPrice = parseFloat(variation.price) || 0;
            }

            const label = $('<span />', {
                text: variation.label,
            });

            const price = $('<span />', {
                class: 'igs-booking-option__price',
                text: formatPrice(parseFloat(variation.price) || 0),
            });

            wrapper.append(input, label, price);
            optionsWrapper.append(wrapper);
        });

        updateTotal();
    }

    function updateTotal() {
        const quantity = parseInt(quantityInput.val(), 10) || 1;
        const total = Math.max(0, unitPrice) * quantity;
        totalEl.text(formatPrice(total));
    }

    function openModal() {
        if (!modal.length) {
            return;
        }

        renderOptions();
        switchView('booking');
        infoMessage.prop('hidden', true);
        modal.addClass('is-visible').attr('aria-hidden', 'false');
        html.addClass('igs-modal-open');
        dialog.attr('tabindex', '-1').focus();
    }

    function closeModal() {
        modal.removeClass('is-visible').attr('aria-hidden', 'true');
        html.removeClass('igs-modal-open');
    }

    function switchView(view) {
        if ('info' === view) {
            bookingView.attr('hidden', true);
            infoView.attr('hidden', false);
        } else {
            infoView.attr('hidden', true);
            bookingView.attr('hidden', false);
        }
    }

    ctaButton.on('click', openModal);

    modal.on('click', function (event) {
        if ($(event.target).is('.igs-booking-modal__close') || event.target === this) {
            closeModal();
        }
    });

    $(document).on('keydown', function (event) {
        if (27 === event.which && modal.hasClass('is-visible')) {
            closeModal();
        }
    });

    optionsWrapper.on('change', 'input[name="variation_id"]', function () {
        selectedVariation = $(this).val();
        unitPrice = parseFloat($(this).data('price')) || 0;
        updateTotal();
    });

    $('.igs-booking-quantity__button').on('click', function () {
        const direction = $(this).data('direction');
        let quantity = parseInt(quantityInput.val(), 10) || 1;

        if ('up' === direction) {
            quantity += 1;
        } else if (quantity > 1) {
            quantity -= 1;
        }

        quantityInput.val(quantity);
        updateTotal();
    });

    $('[data-view-target]').on('click', function () {
        const target = $(this).data('view-target');
        switchView(target);
    });

    $('#igs-booking-submit').on('click', function () {
        const button = $(this);

        const variations = getVariations();

        if ((selectedVariation === null || selectedVariation === undefined || selectedVariation === '') && variations.length) {
            window.alert(settings.i18n.selectOption || '');
            return;
        }

        button.prop('disabled', true).text(settings.i18n.loading || '');

        $.post(settings.ajaxUrl, {
            action: 'igs_add_to_cart',
            nonce: settings.addToCartNonce,
            tour_id: settings.productId,
            variation_id: selectedVariation,
            quantity: quantityInput.val(),
        })
            .done(function (response) {
                if (response.success) {
                    window.location.href = settings.checkoutUrl;
                } else {
                    window.alert((response && response.data && response.data.message) || settings.i18n.addError || '');
                    button.prop('disabled', false).text(settings.i18n.addToCart || '');
                }
            })
            .fail(function () {
                window.alert(settings.i18n.networkError || '');
                button.prop('disabled', false).text(settings.i18n.addToCart || '');
            });
    });

    $('#igs-info-submit').on('click', function () {
        const button = $(this);
        const name = $('#igs-info-name').val();
        const email = $('#igs-info-email').val();

        if (!name || !email) {
            window.alert(settings.i18n.validationInfo || '');
            return;
        }

        button.prop('disabled', true).text(settings.i18n.sending || '');

        $.post(settings.ajaxUrl, {
            action: 'igs_tour_info',
            nonce: settings.infoNonce,
            tour_id: settings.productId,
            info_name: name,
            info_email: email,
            info_comment: $('#igs-info-message').val(),
        })
            .done(function (response) {
                if (response.success) {
                    $('#igs-info-form')[0].reset();
                    infoMessage.prop('hidden', false);
                } else {
                    window.alert((response && response.data && response.data.message) || settings.i18n.infoError || '');
                }
            })
            .fail(function () {
                window.alert(settings.i18n.infoNetwork || '');
            })
            .always(function () {
                button.prop('disabled', false).text(settings.i18n.send || '');
            });
    });
});
