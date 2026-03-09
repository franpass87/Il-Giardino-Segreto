<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Frontend;

use IGS\Ecommerce\Helper\Locale;
use WC_Product;

class PriceDisplay
{
    public function register(): void
    {
        add_filter('woocommerce_get_price_html', [$this, 'filterPriceHtml'], 100, 2);
    }

    public function filterPriceHtml(string $price, WC_Product $product): string
    {
        $isIt = Locale::isIt();

        if ($product->is_type('variable')) {
            $minPrice = $product->get_variation_price('min', true);
            if (is_numeric($minPrice) && $minPrice > 0) {
                $prefix = $isIt ? 'da ' : 'from ';
                return $prefix . wc_price($minPrice, ['decimals' => 0]);
            }
            return '<span class="no-price"></span>';
        }

        $val = $product->get_price();
        if (is_numeric($val) && (float) $val > 0) {
            return wc_price((float) $val, ['decimals' => 0]);
        }

        return '<span class="no-price">' . ($isIt ? 'info in arrivo' : 'info coming soon') . '</span>';
    }
}
