<?php
/**
 * Template contenuto scheda tour.
 *
 * Sostituisce content-single-product.php SOLO per i tour gestiti dal plugin
 * (quelli con il programma nei meta). Genera l'intera pagina del tour in HTML
 * pulito tramite TourLayout — senza il riassunto/tab WooCommerce del tema né
 * alcun contenuto WPBakery. Header, footer e contenitore di Salient restano
 * intatti (questo file è solo la parte interna del content wrapper).
 *
 * @var WC_Product $product
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

global $product;
if (!$product instanceof WC_Product) {
    $product = wc_get_product(get_the_ID());
}
if (!$product instanceof WC_Product) {
    return;
}
?>
<article class="igs-tour-single" id="igs-tour-<?php the_ID(); ?>">
    <?php
    // Layout "Editoriale a rail fisso": rail con prezzo/prenota sempre visibili +
    // colonna racconto (copertina, descrizione, programma, galleria, info).
    (new \IGS\Ecommerce\Frontend\TourEditorial())->render($product);
    ?>
</article>
<?php
// La barra di prenotazione sticky + il modal (varianti, quantità, checkout,
// richiesta info) sono renderizzati da BookingModal su wp_footer: niente da fare qui.
