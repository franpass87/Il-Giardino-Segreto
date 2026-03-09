<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Frontend;

use IGS\Ecommerce\Helper\Locale;
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
        if (!$shopPages) {
            return;
        }
        $css = '
            .loop-tour-dates, .loop-tour-duration, .loop-tour-country {
                font-size: 0.9em; color: #555; margin-top: 0.3em;
            }
            .woocommerce ul.products li.product .woocommerce-loop-product__title {
                line-height: 1.4em; min-height: calc(1.4em * 3); margin-bottom: 0.5em; overflow: visible;
            }
            .woocommerce ul.products li.product { border-radius: 10px; overflow: hidden; }
            .woocommerce ul.products li.product a { display: block; border-radius: 10px; }
            .woocommerce ul.products li.product { position: relative; overflow: hidden; }
            .woocommerce ul.products li.product .full-card-link {
                position: absolute; top: 0; left: 0; width: 100%; height: 100%;
                z-index: 10; text-indent: -9999px;
            }
        ';
        wp_add_inline_style('woocommerce-general', $css);
    }

    public function renderLoopMeta(): void
    {
        global $product;
        if (!$product instanceof WC_Product) {
            return;
        }

        $isIt = Locale::isIt();
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
                $label = $isIt
                    ? (($days === 1) ? '1 giorno' : sprintf('%s giorni', number_format_i18n($days)))
                    : (($days === 1) ? '1 day' : sprintf('%s days', number_format_i18n($days)));
                echo '<div class="loop-tour-duration">' . esc_html($label) . '</div>';
                $valid = true;
            }
        }

        if (!$valid) {
            echo '<div class="loop-tour-dates">' . ($isIt ? 'Date non disponibili' : 'Dates not available') . '</div>';
            echo '<div class="loop-tour-duration">' . ($isIt ? 'Durata non disponibile' : 'Duration not available') . '</div>';
        }

        if (!empty($paese)) {
            echo '<div class="loop-tour-country">' . esc_html($paese) . '</div>';
        } else {
            echo '<div class="loop-tour-country">' . ($isIt ? 'Paese non specificato' : 'Country not specified') . '</div>';
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
        echo '<a href="' . $url . '" class="full-card-link" aria-label="' . $title . '">' . esc_html('Vai al tour') . '</a>';
    }
}
