<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Booking;

use IGS\Ecommerce\Admin\EmailSettings;
use IGS\Ecommerce\Helper\Theme;
use WC_Product;

class BookingModal
{
    public function register(): void
    {
        add_action('wp_footer', [$this, 'renderModal']);
        add_action('wp_ajax_nopriv_gs_tour_add_to_cart', [$this, 'ajaxAddToCart']);
        add_action('wp_ajax_gs_tour_add_to_cart', [$this, 'ajaxAddToCart']);
        add_action('wp_ajax_nopriv_gs_handle_tour_info_request', [$this, 'ajaxInfoRequest']);
        add_action('wp_ajax_gs_handle_tour_info_request', [$this, 'ajaxInfoRequest']);
    }

    public function renderModal(): void
    {
        if (!is_product()) {
            return;
        }

        global $product;
        if (!($product instanceof \WC_Product)) {
            return;
        }

        $productId = $product->get_id();
        $productTitle = $product->get_title();
        $accent = Theme::accent();
        $accentRgb = Theme::accentRgb();

        $L = [
            'cta' => __('Scopri e Prenota', 'igs-ecommerce'),
            'closeAria' => __('Chiudi finestra', 'igs-ecommerce'),
            'choose' => __('Scegli la tua opzione:', 'igs-ecommerce'),
            'single' => __('Opzione unica', 'igs-ecommerce'),
            'noOptions' => __('Non ci sono opzioni di acquisto disponibili per questo prodotto.', 'igs-ecommerce'),
            'qty' => __('Numero persone:', 'igs-ecommerce'),
            'qtyMinus' => __('Diminuisci quantità', 'igs-ecommerce'),
            'qtyPlus' => __('Aumenta quantità', 'igs-ecommerce'),
            'total' => __('Totale', 'igs-ecommerce'),
            'toCheckout' => __('Procedi al Checkout', 'igs-ecommerce'),
            'toInfo' => __('Richiedi Informazioni', 'igs-ecommerce'),
            'thanks' => __('Grazie!', 'igs-ecommerce'),
            'infoSent' => __('La tua richiesta è stata inviata. Ti risponderemo al più presto.', 'igs-ecommerce'),
            'name' => __('Nome', 'igs-ecommerce'),
            'email' => __('Email', 'igs-ecommerce'),
            'yourReq' => __('La tua richiesta (opzionale)', 'igs-ecommerce'),
            'sendReq' => __('Invia Richiesta', 'igs-ecommerce'),
            'back' => __('Torna alla Prenotazione', 'igs-ecommerce'),
            'alertChoose' => __("Per favore, seleziona un'opzione prima di procedere.", 'igs-ecommerce'),
            'wait' => __('Attendi...', 'igs-ecommerce'),
            'commErr' => __('Errore di comunicazione. Riprova.', 'igs-ecommerce'),
            'genericErr' => __('Si è verificato un errore. Riprova.', 'igs-ecommerce'),
            'fillNameEmail' => __('Per favore, compila nome ed email.', 'igs-ecommerce'),
            'cartNotice' => __('Attenzione: procedendo al checkout, il carrello verrà svuotato e sostituito con questo tour.', 'igs-ecommerce'),
            'tabBooking' => __('Prenotazione', 'igs-ecommerce'),
            'tabInfo' => __('Richiedi info', 'igs-ecommerce'),
        ];
        $cartCount = function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;

        // Prezzo "da €X" mostrato nel banner (desktop).
        $ctaPriceHtml = '';
        if ($product->is_type('variable')) {
            $minPrice = $product->get_variation_price('min', true);
            if (is_numeric($minPrice) && (float) $minPrice > 0) {
                $ctaPriceHtml = sprintf(
                    /* translators: %s: prezzo minimo formattato */
                    esc_html__('da %s', 'igs-ecommerce'),
                    wc_price((float) $minPrice, ['decimals' => 0])
                );
            }
        } else {
            $simplePrice = (float) $product->get_price();
            if ($simplePrice > 0) {
                $ctaPriceHtml = wc_price($simplePrice, ['decimals' => 0]);
            }
        }
        ?>
        <style>
        :root{--brand-color:#0e5763;--brand-color-hover:#0a434c;--brand-accent:#8fb159;--background-light:#f6f8f8;--text-color:#2d3748;--text-muted:#64748b;--border-color:#e2e8f0;--font-main:'foundersgrotesk',sans-serif}
        /* ===== Banner ===== */
        #gs-fixed-cta{position:fixed;bottom:0;left:0;width:100%;z-index:99999;background:rgba(255,253,248,.96);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);box-shadow:0 -8px 30px rgba(38,36,31,.12);border-top:1px solid #e4ddcd;animation:gs-cta-up .5s cubic-bezier(.16,1,.3,1)}
        @keyframes gs-cta-up{from{transform:translateY(100%)}to{transform:translateY(0)}}
        .gs-cta-inner{max-width:1180px;margin:0 auto;padding:13px 28px;display:flex;align-items:center;justify-content:space-between;gap:24px}
        .gs-cta-info{display:flex;flex-direction:column;justify-content:center;line-height:1.12;gap:3px}
        .gs-cta-price{font-family:Georgia,"Times New Roman",serif;font-size:1.55rem;font-weight:700;color:#26241f}
        .gs-cta-price .amount,.gs-cta-price bdi{color:#26241f}
        .gs-cta-sub{font-size:.72rem;color:#7a7466;text-transform:uppercase;letter-spacing:.12em}
        #gs-open-modal{display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:15px 34px;font-family:var(--font-main);font-size:1.12rem;font-weight:700;color:#fff;background:<?php echo esc_attr($accent); ?>;border:none;border-radius:999px;cursor:pointer;box-shadow:0 8px 20px rgba(<?php echo esc_attr($accentRgb); ?>,.30);transition:transform .2s,box-shadow .25s,filter .2s;white-space:nowrap;text-shadow:none}
        #gs-open-modal:hover{transform:translateY(-2px);box-shadow:0 12px 28px rgba(<?php echo esc_attr($accentRgb); ?>,.42);filter:brightness(1.05)}
        #gs-open-modal:active{transform:translateY(0)}
        #gs-open-modal svg{width:18px;height:18px;flex:0 0 auto;transition:transform .2s}
        #gs-open-modal:hover svg{transform:translateX(4px)}
        /* ===== Modal ===== */
        #gs-tour-modal{display:none;position:fixed;inset:0;width:100%;height:100%;background:rgba(15,30,33,.55);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);z-index:100000;justify-content:center;align-items:center;padding:16px;font-family:var(--font-main)}
        .gs-modal-content{background:#fff;width:100%;max-width:500px;max-height:92vh;overflow-y:auto;border-radius:20px;box-shadow:0 30px 60px rgba(0,0,0,.28);position:relative;border-top:5px solid var(--brand-accent);transform:scale(.95) translateY(12px);opacity:0;transition:transform .4s cubic-bezier(.34,1.56,.64,1),opacity .3s}
        #gs-tour-modal.is-visible .gs-modal-content{transform:scale(1) translateY(0);opacity:1}
        .gs-modal-header{padding:22px 24px 6px;display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
        .gs-modal-header h3{margin:0;font-size:1.35rem;color:var(--brand-color);font-weight:700;line-height:1.25}
        .gs-close-modal{flex:0 0 auto;font-size:1.5rem;line-height:1;border:none;background:var(--background-light);cursor:pointer;color:#64748b;width:38px;height:38px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:background .2s,color .2s,transform .25s}
        .gs-close-modal:hover{color:#fff;background:var(--brand-color);transform:rotate(90deg)}
        .gs-modal-body{padding:18px 24px}
        #gs-tour-modal .info-view,#gs-tour-modal.info-view-active .booking-view{display:none}
        #gs-tour-modal.info-view-active .info-view,#gs-tour-modal .booking-view{display:block}
        .gs-form-group{margin-bottom:18px}
        .gs-form-group>label{margin-bottom:10px;font-size:.95rem;font-weight:600;color:var(--text-color);display:block}
        .variation-label{display:flex;align-items:center;gap:10px;font-size:1rem;margin-bottom:10px;cursor:pointer;padding:14px 16px;border:2px solid var(--border-color);border-radius:12px;transition:border-color .2s,background .2s,box-shadow .2s}
        .variation-label:hover{border-color:var(--brand-accent);background:var(--background-light)}
        .variation-label:has(input:checked){border-color:var(--brand-color);background:rgba(14,87,99,.06);box-shadow:0 2px 10px rgba(14,87,99,.10)}
        .variation-label .variation-price{margin-left:auto;font-weight:700;color:var(--brand-color)}
        .qty-control{display:flex;align-items:center;gap:12px}
        .qty-control button{background:var(--brand-color);color:#fff;border:none;width:42px;height:42px;font-size:1.4rem;line-height:1;border-radius:50%;cursor:pointer;transition:background .2s,transform .15s}
        .qty-control button:hover{background:var(--brand-color-hover);transform:scale(1.08)}
        .qty-control input{width:60px;height:42px;text-align:center;border:2px solid var(--border-color);border-radius:10px;font-size:1.15rem;font-weight:600;color:var(--text-color)}
        #tour-price-total{text-align:center;font-size:1.8rem;font-weight:700;color:var(--brand-color);margin:22px 0 4px;background:linear-gradient(135deg,var(--background-light) 0%,#eef3f3 100%);padding:16px;border-radius:14px;border:1px solid var(--border-color)}
        #info-form input[type="text"],#info-form input[type="email"],#info-form textarea{width:100%;padding:13px 14px;border:2px solid var(--border-color);border-radius:10px;font-size:1rem;font-family:var(--font-main);transition:border-color .2s,box-shadow .2s}
        #info-form input:focus,#info-form textarea:focus{outline:none;border-color:var(--brand-color);box-shadow:0 0 0 3px rgba(14,87,99,.15)}
        #info-form textarea{min-height:110px;resize:vertical}
        #info-success-message{display:none;padding:28px 24px;background:linear-gradient(135deg,#e8f5e9 0%,#c8e6c9 100%);color:#1e6a3c;border-radius:14px;text-align:center;border:1px solid rgba(30,106,60,.2);font-size:1.05rem}
        .gs-modal-footer{padding:16px 24px 24px;display:flex;flex-direction:column;gap:12px}
        .gs-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:15px 20px;border:none;border-radius:12px;cursor:pointer;font-size:1.05rem;font-weight:600;font-family:var(--font-main);transition:all .25s ease}
        .gs-btn-primary{background:linear-gradient(135deg,var(--brand-color) 0%,#13707f 100%);color:#fff;box-shadow:0 6px 16px rgba(14,87,99,.28)}
        .gs-btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(14,87,99,.40);filter:brightness(1.05)}
        .gs-btn-secondary{background:none;border:2px solid var(--border-color);color:var(--text-color)}
        .gs-btn-secondary:hover{background:var(--background-light);border-color:var(--brand-accent);color:var(--brand-color)}
        .gs-modal-tabs{display:flex;gap:6px;padding:8px 24px 0}
        .gs-tab{flex:1;padding:12px 16px;border:none;background:var(--background-light);cursor:pointer;font-size:.95rem;font-weight:500;color:#64748b;border-radius:12px 12px 0 0;transition:all .2s}
        .gs-tab:hover{color:var(--brand-color)}
        .gs-tab.active{font-weight:700;color:#fff;background:var(--brand-color)}
        .gs-cart-notice{margin:8px 24px 0;padding:12px 16px;background:#fff8e6;color:#8a6d00;font-size:.9rem;border-radius:10px;border:1px solid #ffe08a}
        .gs-form-errors{padding:12px 16px;background:#fdecee;color:#a3262f;border-radius:10px;margin-bottom:16px;display:none;border:1px solid #f5c2c7}
        .gs-form-errors:not(:empty){display:block}
        .required{color:#b32d2e}
        @media(max-width:768px){
        .gs-cta-inner{padding:10px 14px;gap:12px}
        .gs-cta-info{display:none}
        #gs-open-modal{width:100%;padding:15px;border-radius:12px;font-size:1.1rem;min-height:50px}
        #gs-tour-modal{align-items:flex-end;padding:0}
        .gs-modal-content{max-width:100%;max-height:90vh;border-radius:20px 20px 0 0;border-top:none}
        .gs-modal-header,.gs-modal-body,.gs-modal-footer{padding-left:18px;padding-right:18px}
        .gs-modal-tabs{padding-left:18px;padding-right:18px}
        .gs-btn,.gs-tab{min-height:46px}
        }
        </style>

        <div id="gs-fixed-cta">
            <div class="gs-cta-inner">
                <?php if ($ctaPriceHtml !== '') : ?>
                <div class="gs-cta-info">
                    <span class="gs-cta-price"><?php echo wp_kses_post($ctaPriceHtml); ?></span>
                    <span class="gs-cta-sub"><?php echo esc_html(get_the_title($productId)); ?></span>
                </div>
                <?php endif; ?>
                <button id="gs-open-modal">
                    <span><?php echo esc_html($L['cta']); ?></span>
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
        </div>

        <div id="gs-tour-modal" aria-hidden="true" aria-live="polite" aria-atomic="true">
            <div class="gs-modal-content" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr($productTitle); ?>">
                <div class="gs-modal-header">
                    <h3><?php echo esc_html($productTitle); ?></h3>
                    <button type="button" class="gs-close-modal" aria-label="<?php echo esc_attr($L['closeAria']); ?>">×</button>
                </div>
                <div class="gs-modal-tabs" role="tablist">
                    <button type="button" class="gs-tab active" data-view="booking" role="tab" aria-selected="true" aria-controls="gs-booking-panel"><?php echo esc_html($L['tabBooking']); ?></button>
                    <button type="button" class="gs-tab" data-view="info" role="tab" aria-selected="false" aria-controls="gs-info-panel"><?php echo esc_html($L['tabInfo']); ?></button>
                </div>
                <div class="booking-view" id="gs-booking-panel" role="tabpanel">
                    <?php if ($cartCount > 0) : ?>
                    <div class="gs-cart-notice" id="gs-cart-notice"><?php echo esc_html($L['cartNotice']); ?></div>
                    <?php endif; ?>
                    <div class="gs-modal-body">
                        <form id="tour-booking-form" onsubmit="return false;">
                            <div class="gs-form-group">
                                <label><?php echo esc_html($L['choose']); ?></label>
                                <?php
                                if ($product->is_type('variable')) {
                                    foreach ($product->get_available_variations() as $variation) {
                                        if ($variation['is_in_stock'] && $variation['display_price'] > 0) {
                                            ?>
                                            <label class="variation-label">
                                                <input type="radio" name="variation_id" value="<?php echo esc_attr($variation['variation_id']); ?>" data-price="<?php echo esc_attr($variation['display_price']); ?>">
                                                <?php echo esc_html(implode(' / ', $variation['attributes'])); ?>
                                                <span class="variation-price">(<?php echo wp_kses_post(wc_price($variation['display_price'])); ?>)</span>
                                            </label>
                                            <?php
                                        }
                                    }
                                } elseif ($product->is_type('simple')) {
                                    ?>
                                    <label class="variation-label">
                                        <input type="radio" name="variation_id" value="0" data-price="<?php echo esc_attr($product->get_price()); ?>" checked style="display:none;">
                                        <span><?php echo esc_html($L['single']); ?></span>
                                        <span class="variation-price">(<?php echo wp_kses_post(wc_price($product->get_price())); ?>)</span>
                                    </label>
                                    <?php
                                } else {
                                    echo '<p>' . esc_html($L['noOptions']) . '</p>';
                                }
                                ?>
                            </div>
                            <div class="gs-form-group">
                                <label for="tour-quantity"><?php echo esc_html($L['qty']); ?></label>
                                <div class="qty-control">
                                    <button type="button" class="qty-minus" aria-label="<?php echo esc_attr($L['qtyMinus']); ?>">−</button>
                                    <input type="text" id="tour-quantity" name="quantity" value="1" readonly>
                                    <button type="button" class="qty-plus" aria-label="<?php echo esc_attr($L['qtyPlus']); ?>">+</button>
                                </div>
                            </div>
                            <div id="tour-price-total"><?php echo wp_kses_post(wc_price(0)); ?></div>
                        </form>
                    </div>
                    <div class="gs-modal-footer">
                        <button type="button" id="submit-booking" class="gs-btn gs-btn-primary"><?php echo esc_html($L['toCheckout']); ?></button>
                        <button type="button" id="go-to-info" class="gs-btn gs-btn-secondary"><?php echo esc_html($L['toInfo']); ?></button>
                    </div>
                </div>
                <div class="info-view" id="gs-info-panel" role="tabpanel" aria-hidden="true">
                    <div class="gs-modal-body">
                        <div id="info-success-message"><strong><?php echo esc_html($L['thanks']); ?></strong><br><?php echo esc_html($L['infoSent']); ?></div>
                        <form id="info-form" onsubmit="return false;">
                            <input type="hidden" name="tour_id" value="<?php echo esc_attr((string) $productId); ?>">
                            <div id="info-form-errors" class="gs-form-errors" role="alert" aria-live="polite"></div>
                            <div class="gs-form-group">
                                <label for="info_name"><?php echo esc_html($L['name']); ?> <span class="required">*</span></label>
                                <input type="text" id="info_name" name="info_name" required aria-required="true" aria-invalid="false">
                            </div>
                            <div class="gs-form-group">
                                <label for="info_email"><?php echo esc_html($L['email']); ?> <span class="required">*</span></label>
                                <input type="email" id="info_email" name="info_email" required aria-required="true" aria-invalid="false">
                            </div>
                            <div class="gs-form-group">
                                <label for="info_comment"><?php echo esc_html($L['yourReq']); ?></label>
                                <textarea id="info_comment" name="info_comment"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="gs-modal-footer">
                        <button type="button" id="submit-info" class="gs-btn gs-btn-primary"><?php echo esc_html($L['sendReq']); ?></button>
                        <button type="button" id="back-to-booking" class="gs-btn gs-btn-secondary"><?php echo esc_html($L['back']); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(function($){
            var $modal = $('#gs-tour-modal');
            var $bookingForm = $('#tour-booking-form');
            var $infoForm = $('#info-form');
            var ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
            var checkoutUrl = '<?php echo esc_url(wc_get_checkout_url()); ?>';

            function updatePrice(){
                var $sel = $bookingForm.find('input[name="variation_id"]:checked');
                if(!$sel.length) return;
                var unit = parseFloat($sel.data('price')) || 0;
                var qty = parseInt($('#tour-quantity').val(), 10) || 1;
                $('#tour-price-total').html('<?php echo esc_js(html_entity_decode((string) get_woocommerce_currency_symbol(), ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?>' + (unit*qty).toFixed(2).replace('.', ','));
            }
            var lastFocused;
            function openModal(){
                if(!$bookingForm.find('input[name="variation_id"]:checked').length){
                    $bookingForm.find('input[name="variation_id"]').first().prop('checked', true);
                }
                updatePrice();
                $modal.removeClass('info-view-active');
                switchView(false);
                $('#info-success-message').hide();
                $('#info-form-errors').empty().hide();
                $infoForm.show();
                $('.info-view .gs-modal-footer').show();
                $modal.css('display','flex').addClass('is-visible').attr('aria-hidden','false');
                $modal.find('[aria-live]').attr('aria-live','polite');
                lastFocused = document.activeElement;
                setTimeout(function(){ $modal.find('button, input, [tabindex]:not([tabindex="-1"])').first().focus(); }, 50);
            }
            function closeModal(){
                $modal.removeClass('is-visible').attr('aria-hidden','true');
                setTimeout(function(){ $modal.hide(); if(lastFocused && lastFocused.focus) lastFocused.focus(); }, 300);
            }
            function trapFocus(e){
                if(e.key !== 'Tab' || !$modal.hasClass('is-visible')) return;
                var focusable = $modal.find('button, input, textarea, [href], [tabindex]:not([tabindex="-1"])').filter(':visible');
                var first = focusable.first()[0], last = focusable.last()[0];
                if(!first || !last) return;
                if(e.shiftKey){ if(document.activeElement === first){ e.preventDefault(); last.focus(); } }
                else{ if(document.activeElement === last){ e.preventDefault(); first.focus(); } }
            }

            $(document).on('keydown', function(e){ if(e.key === 'Escape' && $('#gs-tour-modal').hasClass('is-visible')){ e.preventDefault(); closeModal(); } });
            $(document).on('keydown', '#gs-tour-modal', trapFocus);
            function switchView(toInfo){
                if(toInfo){ $modal.addClass('info-view-active'); $modal.find('.gs-tab[data-view="info"]').addClass('active').attr('aria-selected','true'); $modal.find('.gs-tab[data-view="booking"]').removeClass('active').attr('aria-selected','false'); $('#gs-info-panel').attr('aria-hidden','false'); $('#gs-booking-panel').attr('aria-hidden','true'); }
                else{ $modal.removeClass('info-view-active'); $modal.find('.gs-tab[data-view="booking"]').addClass('active').attr('aria-selected','true'); $modal.find('.gs-tab[data-view="info"]').removeClass('active').attr('aria-selected','false'); $('#gs-booking-panel').attr('aria-hidden','false'); $('#gs-info-panel').attr('aria-hidden','true'); }
            }
            $(document).on('click', '#gs-open-modal', function(e){ e.preventDefault(); openModal(); });
            $(document).on('click', '#gs-tour-modal', function(e){ if(e.target === this) closeModal(); });
            $(document).on('click', '.gs-close-modal', function(){ closeModal(); });
            $(document).on('click', '.gs-tab', function(){ var v=$(this).data('view'); switchView(v==='info'); });
            $(document).on('click', '#go-to-info', function(e){ e.preventDefault(); switchView(true); });
            $(document).on('click', '#back-to-booking', function(e){ e.preventDefault(); switchView(false); });

            $bookingForm.on('change', 'input[name="variation_id"]', updatePrice);
            $(document).on('click', '.qty-plus', function(){ var v = parseInt($('#tour-quantity').val(),10)||1; $('#tour-quantity').val(v+1); updatePrice(); });
            $(document).on('click', '.qty-minus', function(){ var v = parseInt($('#tour-quantity').val(),10)||1; $('#tour-quantity').val(Math.max(1,v-1)); updatePrice(); });

            $(document).on('click', '#submit-booking', function(){
                var $btn = $(this);
                var vid = $bookingForm.find('input[name="variation_id"]:checked').val();
                if(typeof vid === 'undefined'){ alert('<?php echo esc_js($L['alertChoose']); ?>'); return; }
                $btn.prop('disabled', true).text('<?php echo esc_js($L['wait']); ?>');
                $.post(ajaxUrl, {
                    action: 'gs_tour_add_to_cart',
                    nonce: '<?php echo esc_js(wp_create_nonce('add_to_cart_nonce')); ?>',
                    tour_id: <?php echo (int) $productId; ?>,
                    variation_id: vid,
                    quantity: $('#tour-quantity').val()
                }).done(function(r){
                    if(r && r.success){ window.location.href = checkoutUrl; }
                    else{
                        alert((r && r.data && r.data.message) || '<?php echo esc_js($L['genericErr']); ?>');
                        $btn.prop('disabled', false).text('<?php echo esc_js($L['toCheckout']); ?>');
                    }
                }).fail(function(){
                    alert('<?php echo esc_js($L['commErr']); ?>');
                    $btn.prop('disabled', false).text('<?php echo esc_js($L['toCheckout']); ?>');
                });
            });

            $(document).on('click', '#submit-info', function(){
                var $btn = $(this);
                var $err = $('#info-form-errors');
                $err.empty().hide();
                $('#info_name, #info_email').removeAttr('aria-invalid');
                if($('#info_name').val()==='' || $('#info_email').val()===''){
                    $err.text('<?php echo esc_js($L['fillNameEmail']); ?>').show();
                    if($('#info_name').val()==='') $('#info_name').attr('aria-invalid','true').focus();
                    else if($('#info_email').val()==='') $('#info_email').attr('aria-invalid','true').focus();
                    return;
                }
                $btn.prop('disabled', true).text('<?php echo esc_js($L['wait']); ?>');
                $.post(ajaxUrl, {
                    action: 'gs_handle_tour_info_request',
                    nonce: '<?php echo esc_js(wp_create_nonce('tour_info_nonce')); ?>',
                    tour_id: <?php echo (int) $productId; ?>,
                    info_name: $('#info_name').val(),
                    info_email: $('#info_email').val(),
                    info_comment: $('#info_comment').val()
                }).done(function(r){
                    if(r && r.success){
                        $infoForm.hide();
                        $('.info-view .gs-modal-footer').hide();
                        $('#info-success-message').fadeIn();
                    }else{
                        $err.text((r && r.data && r.data.message) || '<?php echo esc_js($L['genericErr']); ?>').show();
                        $btn.prop('disabled', false).text('<?php echo esc_js($L['sendReq']); ?>');
                    }
                }).fail(function(){
                    $err.text('<?php echo esc_js($L['commErr']); ?>').show();
                    $btn.prop('disabled', false).text('<?php echo esc_js($L['sendReq']); ?>');
                });
            });
        });
        </script>
        <?php
    }

    public function ajaxAddToCart(): void
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'add_to_cart_nonce')) {
            wp_send_json_error(['message' => __('Verifica di sicurezza fallita.', 'igs-ecommerce')]);
        }
        if (!isset($_POST['tour_id']) || !isset($_POST['quantity'])) {
            wp_send_json_error(['message' => __('Dati mancanti.', 'igs-ecommerce')]);
        }

        $productId = absint($_POST['tour_id']);
        $quantity = max(1, absint($_POST['quantity']));
        $variationId = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;

        if (!function_exists('WC') || !WC()->cart) {
            wp_send_json_error(['message' => __('WooCommerce non è attivo.', 'igs-ecommerce')]);
        }

        WC()->cart->empty_cart();

        $added = ($variationId > 0)
            ? WC()->cart->add_to_cart($productId, $quantity, $variationId)
            : WC()->cart->add_to_cart($productId, $quantity);

        if ($added) {
            $product = wc_get_product($variationId > 0 ? $variationId : $productId);
            $unitPrice = $product instanceof WC_Product ? (float) $product->get_price() : 0.0;
            $currency = function_exists('get_woocommerce_currency') ? (string) get_woocommerce_currency() : 'EUR';

            do_action('fp_tracking_event', 'add_to_cart', [
                'item_id' => (string) ($variationId > 0 ? $variationId : $productId),
                'item_name' => $product instanceof WC_Product ? (string) $product->get_name() : '',
                'quantity' => $quantity,
                'price' => $unitPrice,
                'value' => $unitPrice * $quantity,
                'currency' => $currency,
                'source_plugin' => 'igs-ecommerce-customizations',
                'event_id' => 'igs_add_to_cart_' . $productId . '_' . time(),
            ]);

            wp_send_json_success(['message' => __('Prodotto aggiunto al carrello.', 'igs-ecommerce')]);
        } else {
            wp_send_json_error(['message' => __('Impossibile aggiungere il prodotto al carrello. Potrebbe non essere disponibile.', 'igs-ecommerce')]);
        }
    }

    public function ajaxInfoRequest(): void
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'tour_info_nonce')) {
            wp_send_json_error(['message' => __('Verifica di sicurezza fallita.', 'igs-ecommerce')]);
        }

        $tourId = isset($_POST['tour_id']) ? absint($_POST['tour_id']) : 0;
        $name = isset($_POST['info_name']) ? sanitize_text_field(wp_unslash($_POST['info_name'])) : '';
        $email = isset($_POST['info_email']) ? sanitize_email(wp_unslash($_POST['info_email'])) : '';
        $comment = isset($_POST['info_comment']) ? sanitize_textarea_field(wp_unslash($_POST['info_comment'])) : '';

        if (empty($name) || !is_email($email) || empty($tourId)) {
            wp_send_json_error(['message' => __('Per favore, compila correttamente nome ed email.', 'igs-ecommerce')]);
        }

        $recipients = EmailSettings::getRecipients();
        if ($recipients === '') {
            $recipients = get_option('admin_email');
        }
        $product = wc_get_product($tourId);
        $tourTitle = $product ? $product->get_name() : __('Tour non specificato', 'igs-ecommerce');
        $messaggio = $comment !== '' ? nl2br(esc_html($comment)) : '—';

        $subject = str_replace('{tour_title}', $tourTitle, EmailSettings::getSubjectTemplate());
        $body = str_replace(
            ['{tour_title}', '{nome}', '{email}', '{messaggio}'],
            [esc_html($tourTitle), esc_html($name), esc_html($email), $messaggio],
            EmailSettings::getBodyTemplate()
        );

        $safeName = str_replace(["\r", "\n", '<', '>'], '', $name);
        $headers = array_merge(EmailSettings::buildHeaders(), [
            'Reply-To: ' . $safeName . ' <' . $email . '>',
        ]);

        if (wp_mail($recipients, $subject, $body, $headers)) {
            do_action('fp_tracking_event', 'generate_lead', [
                'lead_type' => 'info_request',
                'tour_id' => $tourId,
                'tour_title' => (string) $tourTitle,
                'source_plugin' => 'igs-ecommerce-customizations',
                'event_id' => 'igs_info_request_' . $tourId . '_' . time(),
                'user_data' => [
                    'em' => $email,
                    'fn' => $name,
                ],
            ]);

            wp_send_json_success(['message' => __('Email inviata con successo.', 'igs-ecommerce')]);
            return;
        }

        wp_send_json_error([
            'message' => __('Impossibile inviare l\'email. Riprova più tardi.', 'igs-ecommerce'),
        ]);
    }
}
