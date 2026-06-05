<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Frontend;

/**
 * Prende il controllo dell'intera scheda prodotto per i tour gestiti dal plugin:
 * sostituisce il template WooCommerce content-single-product.php (riassunto + tab)
 * con templates/content-single-tour.php, che rende HTML pulito da TourLayout.
 *
 * Lasciando intatti header, footer e contenitore del tema (interviene solo sulla
 * "content part"), evita i wrapper rotti tipici del template_include totale.
 *
 * I prodotti non ancora migrati (senza programma nei meta) restano sul template
 * standard del tema, così le pagine WPBakery EN non si svuotano.
 */
class TourTemplate
{
    public function register(): void
    {
        add_filter('wc_get_template_part', [$this, 'overrideContentTemplate'], 20, 3);
    }

    /**
     * @param string $template Percorso template risolto da WooCommerce.
     * @param string $slug     Slug della template part (es. "content").
     * @param string $name     Nome della template part (es. "single-product").
     */
    public function overrideContentTemplate($template, $slug, $name): string
    {
        if ($slug !== 'content' || $name !== 'single-product') {
            return (string) $template;
        }
        if (!$this->isManagedTour()) {
            return (string) $template;
        }
        $custom = IGS_DIR . 'templates/content-single-tour.php';

        return is_readable($custom) ? $custom : (string) $template;
    }

    /** Un tour è "gestito" quando ha il programma nei meta (è stato migrato dal WPBakery). */
    private function isManagedTour(): bool
    {
        $id = get_the_ID();
        if (!$id) {
            return false;
        }
        $programma = get_post_meta($id, '_igs_tour_programma', true);

        return is_array($programma) && !empty($programma);
    }
}
