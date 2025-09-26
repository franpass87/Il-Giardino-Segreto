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
    let selectedAttributes = {};
    let unitPrice = 0;
    let lastFocusedElement = null;

    function setActiveOption(element) {
        if (!element || !element.length) {
            return;
        }

        optionsWrapper.find('.igs-booking-option').removeClass('is-active');
        element.closest('.igs-booking-option').addClass('is-active');
    }

    function setFocusState(element, toggle) {
        if (!element || !element.length) {
            return;
        }

        element.closest('.igs-booking-option').toggleClass('is-focused', Boolean(toggle));
    }

    function normaliseAttributes(attributes) {
        if (!attributes || typeof attributes !== 'object') {
            return {};
        }

        const result = {};

        Object.keys(attributes).forEach(function (key) {
            if (typeof key !== 'string') {
                return;
            }

            const value = attributes[key];

            if (value === null || typeof value === 'undefined') {
                return;
            }

            result[key] = String(value);
        });

        return result;
    }

    function formatPrice(value) {
        const locale = settings.locale || 'it-IT';
        const decimals = typeof settings.decimals === 'number' ? settings.decimals : 2;
        const formatter = new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: settings.currency || 'EUR',
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
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

    function getFocusableElements(container) {
        if (!container || !container.length) {
            return $();
        }

        return container
            .find(
                'a[href], area[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            )
            .filter(':visible');
    }

    function focusFirstElement(container) {
        const focusable = getFocusableElements(container);

        if (focusable.length) {
            focusable.first().trigger('focus');
        }
    }

    function renderOptions() {
        const variations = getVariations();

        optionsWrapper.empty();
        selectedVariation = null;
        selectedAttributes = {};
        unitPrice = 0;

        variations.forEach(function (variation, index) {
            const optionId = 'igs-variation-' + variation.id;
            const wrapper = $('<label />', {
                class: 'igs-booking-option',
                for: optionId,
            });

            const attributes = normaliseAttributes(variation.attributes);

            const input = $('<input />', {
                type: 'radio',
                id: optionId,
                name: 'variation_id',
                value: variation.id,
                'data-price': variation.price,
            });

            input.data('attributes', attributes);

            if (0 === index) {
                input.prop('checked', true);
                selectedVariation = String(variation.id);
                selectedAttributes = attributes;
                unitPrice = parseFloat(variation.price) || 0;
            }

            const label = $('<span />', {
                text: variation.label,
            });

            const price = $('<span />', {
                class: 'igs-booking-option__price',
                text: typeof variation.price_text === 'string' && variation.price_text.length
                    ? variation.price_text
                    : formatPrice(parseFloat(variation.price) || 0),
            });

            wrapper.append(input, label, price);
            optionsWrapper.append(wrapper);
        });

        setActiveOption(optionsWrapper.find('input[name="variation_id"]:checked'));
        updateTotal();
    }

    function updateTotal() {
        const quantity = parseInt(quantityInput.val(), 10) || 1;
        const total = Math.max(0, parseFloat(unitPrice) || 0) * quantity;
        totalEl.text(formatPrice(total));
    }

    function openModal() {
        if (!modal.length) {
            return;
        }

        lastFocusedElement = document.activeElement;
        renderOptions();
        switchView('booking');
        infoMessage.prop('hidden', true);
        modal.addClass('is-visible').attr('aria-hidden', 'false');
        if (ctaButton.length) {
            ctaButton.attr('aria-expanded', 'true');
        }
        html.addClass('igs-modal-open');
        dialog.attr('tabindex', '-1').focus();
        window.requestAnimationFrame(function () {
            focusFirstElement(bookingView);
        });
    }

    function closeModal() {
        modal.removeClass('is-visible').attr('aria-hidden', 'true');
        html.removeClass('igs-modal-open');
        if (ctaButton.length) {
            ctaButton.attr('aria-expanded', 'false');
        }
        if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
            window.requestAnimationFrame(function () {
                lastFocusedElement.focus();
            });
        }
    }

    function switchView(view) {
        if ('info' === view) {
            bookingView.attr('hidden', true);
            infoView.attr('hidden', false);
        } else {
            infoView.attr('hidden', true);
            bookingView.attr('hidden', false);
        }

        window.requestAnimationFrame(function () {
            if ('info' === view) {
                focusFirstElement(infoView);
            } else {
                focusFirstElement(bookingView);
            }
        });
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

    dialog.on('keydown', function (event) {
        if (event.key !== 'Tab') {
            return;
        }

        const focusable = getFocusableElements(dialog);

        if (!focusable.length) {
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey) {
            if (document.activeElement === first) {
                event.preventDefault();
                $(last).trigger('focus');
            }
        } else if (document.activeElement === last) {
            event.preventDefault();
            $(first).trigger('focus');
        }
    });

    optionsWrapper.on('change', 'input[name="variation_id"]', function () {
        selectedVariation = String($(this).val());
        unitPrice = parseFloat($(this).data('price')) || 0;
        selectedAttributes = normaliseAttributes($(this).data('attributes'));
        setActiveOption($(this));
        updateTotal();
    });

    optionsWrapper.on('focus', 'input[name="variation_id"]', function () {
        const element = $(this);
        setActiveOption(element);
        setFocusState(element, true);
    });

    optionsWrapper.on('blur', 'input[name="variation_id"]', function () {
        setFocusState($(this), false);
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

        if (variations.length && (null === selectedVariation || '' === selectedVariation)) {
            window.alert(settings.i18n.selectOption || '');
            return;
        }

        button.prop('disabled', true).text(settings.i18n.loading || '');

        $.post(settings.ajaxUrl, {
            action: 'igs_add_to_cart',
            nonce: settings.addToCartNonce,
            tour_id: settings.productId,
            variation_id: variations.length ? selectedVariation : '',
            quantity: quantityInput.val(),
            variation: selectedAttributes,
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
                    window.requestAnimationFrame(function () {
                        infoMessage.trigger('focus');
                    });
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
