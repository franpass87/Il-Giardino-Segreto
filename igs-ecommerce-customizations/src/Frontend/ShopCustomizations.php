<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Frontend;

use IGS\Ecommerce\Helper\Locale;

class ShopCustomizations
{
    public function register(): void
    {
        add_action('template_redirect', [$this, 'removeShopBreadcrumb']);
        add_filter('woocommerce_page_title', [$this, 'filterPageTitle']);
        add_action('wp_head', [$this, 'headStyles']);
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
            return Locale::isIt() ? 'Destinazioni fuori dai sentieri battuti' : 'Off-the-beaten-path destinations';
        }
        return $title;
    }

    public function headStyles(): void
    {
        if (!is_shop()) {
            return;
        }
        echo '<style>
            .woocommerce-products-header h1.page-title,
            .woocommerce-page .page-title,
            .woocommerce .page-title { text-align: center !important; margin-top: 35px; }
            nav.woocommerce-breadcrumb { display: none !important; }
        </style>';
    }
}
