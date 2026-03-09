<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Core;

use IGS\Ecommerce\Admin\GardenMetabox;
use IGS\Ecommerce\Admin\GlobalStringsSettings;
use IGS\Ecommerce\Admin\MapMetabox;
use IGS\Ecommerce\Admin\PortfolioDateMetabox;
use IGS\Ecommerce\Admin\ProductColumns;
use IGS\Ecommerce\Admin\TourProductMetabox;
use IGS\Ecommerce\Booking\BookingModal;
use IGS\Ecommerce\Cart\ReturnToShop;
use IGS\Ecommerce\Frontend\PriceDisplay;
use IGS\Ecommerce\Frontend\ProductLoop;
use IGS\Ecommerce\Frontend\ShopCustomizations;
use IGS\Ecommerce\Frontend\TourLayout;
use IGS\Ecommerce\Frontend\WooCommerceDisabler;
use IGS\Ecommerce\Portfolio\PortfolioTitleFilter;
use IGS\Ecommerce\Shortcodes\GardenShortcodes;
use IGS\Ecommerce\Shortcodes\MapShortcode;

final class Plugin
{
    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void
    {
        (new WooCommerceDisabler())->register();
        (new TourProductMetabox())->register();
        (new GardenMetabox())->register();
        (new PortfolioDateMetabox())->register();
        (new MapMetabox())->register();
        (new ProductColumns())->register();
        (new GlobalStringsSettings())->register();

        (new PriceDisplay())->register();
        (new TourLayout())->register();
        (new ProductLoop())->register();
        (new ShopCustomizations())->register();

        (new GardenShortcodes())->register();
        (new MapShortcode())->register();

        (new BookingModal())->register();
        (new PortfolioTitleFilter())->register();
        (new ReturnToShop())->register();
    }
}
