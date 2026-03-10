<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Frontend;

use IGS\Ecommerce\Helper\CountryFlags;
use WC_Product;

class ProductLoop
{
    public function register(): void
    {
        add_action('init', [$this, 'removeAddToCart']);
        add_filter('woocommerce_loop_add_to_cart_link', '__return_empty_string', 10);
        add_action('wp_enqueue_scripts', [$this, 'enqueueStyles']);
        add_action('woocommerce_after_shop_loop_item_title', [$this, 'renderLoopMeta'], 15);
        add_action('woocommerce_after_shop_loop_item', [$this, 'renderFullCardLink'], 20);
    }

    public function removeAddToCart(): void
    {
        remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
    }

    public function enqueueStyles(): void
    {
        $shopPages = is_shop() || is_product_taxonomy() || is_product_category() || is_product_tag();

        $cssShop = $shopPages ? '
            .woocommerce ul.products li.product .woocommerce-loop-product__title {
                line-height: 1.4em; min-height: calc(1.4em * 3); margin-bottom: 0.5em; overflow: visible;
            }
        ' : '';

        $cssGlobal = '
            .loop-tour-dates, .loop-tour-country {
                font-size: 22px;
                color: #555;
                margin-top: 10px;
                text-align: center;
                font-weight: bold;
                font-family: \'the-seasons-regular\';
            }
            .loop-tour-dates, .loop-tour-duration, .loop-tour-country {
                font-size: 20px;
                color: #555;
                margin-top: 10px;
                text-align: center;
            }
            .loop-tour-duration {
                background: linear-gradient(135deg, #0b5764 0%, #0e6b7a 100%);
                color: white;
                padding-top: 6px;
                padding-bottom: 2px;
            }
            .loop-tour-country {
                font-size: 20px;
                color: #ffffff;
                margin-top: 0;
                text-align: center;
                background: linear-gradient(135deg, #7a9e4a 0%, #8fb159 100%);
                padding-bottom: 6px;
                padding-top: 2px;
            }
            .tour-date-loop {
                font-family: \'the-seasons-regular\';
                font-weight: bold !important;
                text-align: center !important;
                background: #e0e9eb !important;
                padding-bottom: 0 !important;
                margin-bottom: 0 !important;
                margin-top: 20px !important;
                border-radius: 15px !important;
                font-size: 15px !important;
            }
            .tour-date-single {
                font-family: \'the-seasons-regular\';
                font-weight: bold !important;
                text-align: center !important;
                background: #e0e9eb !important;
                padding: 5px !important;
                margin-bottom: 0 !important;
                margin-top: 20px !important;
                border-radius: 15px !important;
                font-size: 20px !important;
            }
            .woocommerce ul.products li.product {
                border-radius: 12px;
                overflow: hidden;
                position: relative;
                cursor: pointer;
                transition: box-shadow .3s ease, transform .3s ease;
            }
            .woocommerce ul.products li.product:hover {
                box-shadow: 0 12px 32px rgba(11,87,100,.12);
                transform: translateY(-4px);
            }
            .woocommerce ul.products li.product a { display: block; border-radius: 10px; }
            .woocommerce ul.products li.product .full-card-link {
                position: absolute; top: 0; left: 0; width: 100%; height: 100%;
                z-index: 10; text-indent: -9999px;
            }
            .woocommerce ul.products { margin-bottom: 1.5em; align-items: start; }
            .woocommerce ul.products li.product .woocommerce-loop-product__title {
                text-align: center;
                font-weight: bold;
                font-size: 30px;
                line-height: 30px;
                padding-left: 10px;
                padding-right: 10px;
            }
            .woocommerce ul.products li.product .price,
            .woocommerce ul.products li.product .price ins,
            .woocommerce ul.products li.product .price ins .amount {
                font-size: 28px;
                line-height: 10px;
                font-weight: 600;
                text-align: center;
            }
            body .woocommerce .nectar-woo-flickity[data-item-shadow="1"] li.product.classic,
            body .woocommerce .nectar-woo-flickity[data-item-shadow="1"] li.product.text_on_hover {
                box-shadow: 0 3px 7px rgba(0,0,0,.07);
                background: white;
            }
            body .woocommerce .nectar-woo-flickity[data-item-shadow="1"] li.product.classic .price,
            body .woocommerce .nectar-woo-flickity[data-item-shadow="1"] li.product.classic .woocommerce-loop-product__title {
                padding: 4px 20px 3px;
                text-align: center;
                font-weight: bold;
                font-size: 25px;
                line-height: 26px;
                font-family: \'the-seasons-regular\';
            }
            .woocommerce ul.products[data-product-style]:not([data-n-desktop-columns=default]) li.product,
            .woocommerce ul.products[data-product-style]:not([data-n-desktop-small-columns=default]) li.product,
            .woocommerce ul.products[data-product-style]:not([data-n-phone-columns=default]) li.product,
            .woocommerce ul.products[data-product-style]:not([data-n-tablet-columns=default]) li.product {
                float: none !important;
                clear: none !important;
                box-shadow: rgba(0,0,0,.04) 0 1px 0, rgba(0,0,0,.05) 0 2px 7px, rgba(0,0,0,.06) 0 12px 22px;
            }
            .woocommerce .woocommerce-result-count { display: none !important; }
            .flickity-page-dots { display: none; }
        ';

        $css = trim($cssShop . $cssGlobal);
        if ($css !== '') {
            wp_add_inline_style('woocommerce-general', $css);
        }
    }

    public function renderLoopMeta(): void
    {
        global $product;
        if (!$product instanceof WC_Product) {
            return;
        }

        $ranges = get_post_meta($product->get_id(), '_date_ranges', true);
        $paese = get_post_meta($product->get_id(), '_paese_tour', true);
        $valid = false;

        if (is_array($ranges) && !empty($ranges)) {
            $r = $ranges[0];
            $start = isset($r['start']) ? \DateTime::createFromFormat('d/m/Y', $r['start']) : null;
            $end = isset($r['end']) ? \DateTime::createFromFormat('d/m/Y', $r['end']) : null;

            if ($start && $end && $end >= $start) {
                echo '<div class="loop-tour-dates">' . esc_html($r['start']) . ' → ' . esc_html($r['end']) . '</div>';
                $days = $start->diff($end)->days + 1;
                $label = sprintf(
                    _n('%s giorno', '%s giorni', $days, 'igs-ecommerce'),
                    number_format_i18n($days)
                );
                echo '<div class="loop-tour-duration">' . esc_html($label) . '</div>';
                $valid = true;
            }
        }

        if (!$valid) {
            echo '<div class="loop-tour-dates">' . esc_html__('Date non disponibili', 'igs-ecommerce') . '</div>';
            echo '<div class="loop-tour-duration">' . esc_html__('Durata non disponibile', 'igs-ecommerce') . '</div>';
        }

        if (!empty($paese)) {
            $display = CountryFlags::withFlag($paese);
            echo '<div class="loop-tour-country">' . esc_html($display) . '</div>';
        } else {
            echo '<div class="loop-tour-country">' . esc_html__('Paese non specificato', 'igs-ecommerce') . '</div>';
        }
    }

    public function renderFullCardLink(): void
    {
        global $product;
        if (!$product instanceof WC_Product) {
            return;
        }
        $url = esc_url(get_permalink($product->get_id()));
        $title = esc_attr(get_the_title($product->get_id()));
        $label = apply_filters('igs_loop_vai_al_tour_label', __('Vai al tour', 'igs-ecommerce'), $product);
        echo '<a href="' . $url . '" class="full-card-link" aria-label="' . $title . '">' . esc_html($label) . '</a>';
    }
}
