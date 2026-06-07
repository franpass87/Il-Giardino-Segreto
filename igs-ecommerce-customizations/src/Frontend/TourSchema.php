<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Frontend;

use WC_Product;

/**
 * Dati strutturati JSON-LD (schema.org Product + Offer) sulle schede tour, per i
 * rich result Google con prezzo/disponibilità. FP SEO emette già lo schema di sito
 * (Organization/TravelAgency/Breadcrumb) ma non quello di prodotto: qui lo aggiungiamo
 * a livello prodotto, senza duplicati.
 */
class TourSchema
{
    public function register(): void
    {
        add_action('wp_head', [$this, 'render'], 30);
    }

    public function render(): void
    {
        if (!is_product()) {
            return;
        }
        global $product;
        if (!$product instanceof WC_Product) {
            $product = wc_get_product(get_queried_object_id());
        }
        if (!$product instanceof WC_Product) {
            return;
        }

        $price = $this->minPrice($product);
        $imgId = $product->get_image_id();
        $excerpt = trim(wp_strip_all_tags((string) $product->get_short_description()));
        if ($excerpt === '') {
            $excerpt = trim(wp_strip_all_tags((string) $product->get_description()));
        }

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => wp_strip_all_tags(get_the_title()),
            'url' => get_permalink($product->get_id()),
            'brand' => [
                '@type' => 'Organization',
                'name' => 'Il Giardino Segreto Garden Tours',
            ],
        ];
        if ($excerpt !== '') {
            $data['description'] = $this->truncate($excerpt, 320);
        }
        if ($imgId) {
            $imgUrl = wp_get_attachment_image_url($imgId, 'large');
            if ($imgUrl) {
                $data['image'] = $imgUrl;
            }
        }
        if ($price > 0) {
            $data['offers'] = [
                '@type' => 'Offer',
                'price' => (string) $price,
                'priceCurrency' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'EUR',
                'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url' => get_permalink($product->get_id()),
            ];
        }

        echo "\n" . '<script type="application/ld+json">'
            . wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            . '</script>' . "\n";
    }

    /** Prezzo minimo: minimo delle varianti per i variabili, prezzo diretto per i simple. */
    private function minPrice(WC_Product $product): float
    {
        if ($product->is_type('variable') && method_exists($product, 'get_variation_price')) {
            $min = $product->get_variation_price('min', true);

            return is_numeric($min) ? (float) $min : 0.0;
        }
        $p = $product->get_price();

        return is_numeric($p) ? (float) $p : 0.0;
    }

    private function truncate(string $s, int $max): string
    {
        if (function_exists('mb_strlen') && mb_strlen($s) > $max) {
            return rtrim(mb_substr($s, 0, $max - 1)) . '…';
        }

        return $s;
    }
}
