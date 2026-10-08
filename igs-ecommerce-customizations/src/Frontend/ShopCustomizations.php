<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Frontend;

class ShopCustomizations
{
    public function register(): void
    {
        add_action('template_redirect', [$this, 'removeShopBreadcrumb']);
        add_filter('woocommerce_page_title', [$this, 'filterPageTitle']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueStyles'], 20);
    }

    public function removeShopBreadcrumb(): void
    {
        if (is_shop()) {
            remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
        }
    }

    public function filterPageTitle(string $title): string
    {
        if (is_shop()) {
            return __('Destinazioni fuori dai sentieri battuti', 'igs-ecommerce');
        }
        return $title;
    }

    public function enqueueStyles(): void
    {
        $css = '';
        if (is_shop()) {
            $css .= '.woocommerce-products-header h1.page-title,.woocommerce-page .page-title,.woocommerce .page-title{text-align:center!important;margin-top:35px}nav.woocommerce-breadcrumb{display:none!important}';
        }
        $css .= '
            #header-outer .cart-menu,
            #header-outer .cart-outer,
            #header-outer .cart-contents,
            #header-outer li.menu-item-type-woocommerce-cart,
            #mobile-cart-link,
            #mobile-cart-trigger,
            body #header-outer .mobile-cart,
            .nectar-mobile-cart,
            #mobile-menu .cart-icon,
            #header-outer .nectar-header-text-content > .cart-menu-wrap { display: none !important; }
            #full_width_portfolio .project-title.parallax-effect .section-title,
            #full_width_portfolio .project-title { padding-bottom: 0; padding-top: 50px; }
            .nectar-post-grid-wrap[data-style=content_under_image] .nectar-post-grid[data-card=yes][data-text-align=center] .nectar-post-grid-item .content { padding: 35px 16px; }
            .woocommerce-checkout #payment ul.wc_payment_methods li.wc_payment_method input[type="radio"] {
                display: inline-block !important;
                opacity: 1 !important;
                position: static !important;
                width: auto !important;
                height: auto !important;
                margin-right: 10px !important;
                vertical-align: middle !important;
            }
            .wc-block-components-checkout-return-to-cart-button { display: none; }
            .is-mobile .wc-block-checkout__actions .wc-block-components-checkout-return-to-cart-button { display: none; }
        ';
        $css .= '
            @media (max-width: 768px) {
                #ajax-content-wrap .vc_row.right_padding_50px .row_col_wrap_12,
                .nectar-global-section .vc_row.right_padding_50px .row_col_wrap_12 { padding-right: 25px; }
                #ajax-content-wrap .vc_row.left_padding_50px .row_col_wrap_12,
                .nectar-global-section .vc_row.left_padding_50px .row_col_wrap_12 { padding-left: 25px; }
            }
            @media (max-width: 690px) {
                .wpb_row.full-width-content .woocommerce .nectar-woo-flickity,
                body .wpb_row:not(.full-width-content) .woocommerce .nectar-woo-flickity:not([data-controls=arrows-overlaid]) { padding-bottom: 25px; }
                .full-width-section > .col.span_12.dark .swiper-slide[data-color-scheme="light"] .content h2,
                .full-width-content > .col.span_12.dark .swiper-slide[data-color-scheme="light"] .content h2,
                .full-width-section > .col.span_12.dark .swiper-slide[data-color-scheme="light"] .content .ns-heading-el,
                .full-width-content > .col.span_12.dark .swiper-slide[data-color-scheme="light"] .content .ns-heading-el,
                .full-width-section > .col.span_12.dark .swiper-slide[data-color-scheme="light"] .content h1,
                .full-width-content > .col.span_12.dark .swiper-slide[data-color-scheme="light"] .content h1,
                .full-width-section > .col.span_12.dark .swiper-slide[data-color-scheme="light"] .content h3,
                .full-width-content > .col.span_12.dark .swiper-slide[data-color-scheme="light"] .content h3 {
                    line-height: 41px !important;
                    color: #fff;
                    font-size: 35px !important;
                }
            }
            /* Righe loghi "Segnalato da" e "I Nostri Clienti": il carosello Salient
               fissava la propria altezza prima che le immagini lazy fossero caricate
               e le tagliava a metà. Ora le righe sono statiche: qui il logo viene
               solo contenuto in altezza, resta intero e la riga non diventa enorme. */
            .clients.no-carousel > div { display: flex; align-items: center; justify-content: center; }
            .clients.no-carousel > div img { max-height: 90px; width: auto; height: auto; margin: 0 auto; }
            @media (max-width: 690px) {
                .clients.no-carousel > div img { max-height: 60px; }
            }
            /* Prefisso "da" / "from" del prezzo: deve stare sotto la cifra, non sopra.
               Senza regola ereditava i 25px di .price mentre l importo ne ha 19. */
            .price .igs-price-from {
                font-size: .62em;
                font-weight: 400;
                letter-spacing: .02em;
                opacity: .78;
            }
            .nectar-slider-wrap[data-full-width="false"]:not([data-parallax="true"]) .swiper-slide .content {
                padding: 0 100px;
                text-shadow: 2px 2px 5px black;
            }
        ';
        if ($css !== '') {
            wp_add_inline_style('woocommerce-general', $css);
        }
    }
}
