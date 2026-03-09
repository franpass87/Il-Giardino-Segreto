<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Cart;

use IGS\Ecommerce\Helper\Locale;

class ReturnToShop
{
    public function register(): void
    {
        add_filter('woocommerce_return_to_shop_redirect', [$this, 'redirectToHome']);
        add_filter('woocommerce_return_to_shop_text', [$this, 'filterText']);
    }

    public function redirectToHome(string $url): string
    {
        return home_url();
    }

    public function filterText(string $text): string
    {
        return Locale::isIt() ? 'Ritorna al sito web' : 'Return to website';
    }
}
