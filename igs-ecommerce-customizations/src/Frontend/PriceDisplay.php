<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Frontend;

use WC_Product;

class PriceDisplay
{
    public function register(): void
    {
        add_filter('woocommerce_get_price_html', [$this, 'filterPriceHtml'], 100, 2);
    }

    public function filterPriceHtml(string $price, WC_Product $product): string
    {
        if ($product->is_type('variable')) {
            $minPrice = $product->get_variation_price('min', true);
            if (is_numeric($minPrice) && $minPrice > 0) {
                return _x('da ', 'price prefix', 'igs-ecommerce') . wc_price($minPrice, ['decimals' => 0]);
            }
            return '<span class="no-price"></span>';
        }

        $val = $product->get_price();
        if (is_numeric($val) && (float) $val > 0) {
            return wc_price((float) $val, ['decimals' => 0]);
        }

        return '<span class="no-price">' . esc_html__('info in arrivo', 'igs-ecommerce') . '</span>';
    }
}
