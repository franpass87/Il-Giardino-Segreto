<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Cart;

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
        return __('Ritorna al sito web', 'igs-ecommerce');
    }
}
