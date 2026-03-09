<?php
/**
 * DEV TOOL: Import semi-automatico da contenuto WPBakery verso metabox IGS.
 * Estrattori:
 * - [toggle] → Programma del Tour e "Tutto quello che devi sapere"
 * - [vc_gallery images="..."] → Galleria prodotto
 * - [image_with_animation image_url="..."] + shortcode → Caratteristiche (icona)
 *
 * Uso: visitare (da loggato admin):
 *      .../dev-tools/import-from-wpbakery.php?product_id=123   (singolo)
 *      .../dev-tools/import-from-wpbakery.php?all=1            (tutti)
 *      Aggiungere &dry_run=1 per anteprima senza salvare.
 * Richiede login admin.
 *
 * @package IGS_Ecommerce
 */

$wp_load = dirname(__DIR__, 4) . '/wp-load.php';
if (!file_exists($wp_load)) {
    die('WordPress non trovato.');
}
require_once $wp_load;

if (!current_user_can('manage_options')) {
    wp_die('Accesso negato.');
}

$productId = isset($_GET['product_id']) ? absint($_GET['product_id']) : 0;
$all = isset($_GET['all']) && $_GET['all'] === '1';
$dryRun = isset($_GET['dry_run']) && $_GET['dry_run'] === '1';

$products = [];
if ($productId > 0) {
    $p = get_post($productId);
    if ($p && $p->post_type === 'product') {
        $products[] = $p;
    }
} elseif ($all) {
    $products = get_posts([
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'any',
    ]);
}

if (empty($products)) {
    wp_die('Nessun prodotto da importare. Usa ?product_id=123 o ?all=1');
}

header('Content-Type: text/html; charset=utf-8');
echo '<pre style="font-family:monospace;white-space:pre-wrap;">';
echo "=== Import WPBakery → IGS ===\n";
if ($dryRun) {
    echo "(DRY RUN - nessun salvataggio)\n";
}
echo "\n";

foreach ($products as $post) {
    $content = $post->post_content;
    $pid = $post->ID;
    echo "Prodotto #{$pid}: " . esc_html($post->post_title) . "\n";

    $stats = ['programma' => 0, 'dettagli' => 0, 'gallery' => 0, 'caratteristiche' => 0];

    // 1. vc_gallery images
    $galleryIds = [];
    if (preg_match('/\[vc_gallery[^\]]*images="([^"]*)"/', $content, $m)) {
        $galleryIds = array_filter(array_map('absint', preg_split('/\s*,\s*/', $m[1])));
        if (!empty($galleryIds) && !$dryRun) {
            update_post_meta($pid, '_product_image_gallery', implode(',', $galleryIds));
        }
        $stats['gallery'] = count($galleryIds);
    }
    if ($stats['gallery'] > 0) {
        echo "  - Galleria: " . $stats['gallery'] . " immagini\n";
    }

    // 2. toggles
    $programma = [];
    $cosaPortare = '';
    $documenti = '';
    $quotaComprende = '';
    $quotaNonComprende = '';
    $voli = '';

    if (preg_match_all('/\[toggle[^\]]*title="([^"]*)"[^\]]*\](.*?)\[\/toggle\]/s', $content, $toggles, PREG_SET_ORDER)) {
        foreach ($toggles as $t) {
            $title = trim($t[1]);
            $body = trim($t[2]);
            $body = preg_replace('/\[vc_column_text[^\]]*\](.*)\[\/vc_column_text\]/s', '$1', $body);
            $body = trim(strip_tags($body));

            if (preg_match('/^(?:Giorno|Day)\s+(\d+)(?:\s*[-–]\s*(.*))?$/ui', $title, $dayMatch)) {
                $programma[] = [
                    'num' => (int) $dayMatch[1],
                    'titolo' => isset($dayMatch[2]) && $dayMatch[2] !== '' ? trim($dayMatch[2]) : $title,
                    'contenuto' => $body,
                ];
            } elseif (stripos($title, 'Cosa portare') !== false) {
                $cosaPortare = $body;
            } elseif (stripos($title, 'Documenti') !== false) {
                $documenti = $body;
            } elseif (stripos($title, 'quota comprende') !== false && stripos($title, 'non') === false) {
                $quotaComprende = $body;
            } elseif (stripos($title, 'quota non comprende') !== false) {
                $quotaNonComprende = $body;
            } elseif (stripos($title, 'Voli') !== false) {
                $voli = $body;
            }
        }
    }

    if (!empty($programma)) {
        if (!$dryRun) {
            update_post_meta($pid, '_igs_tour_programma', $programma);
        }
        $stats['programma'] = count($programma);
        echo "  - Programma: " . count($programma) . " giorni\n";
    }
    if ($cosaPortare !== '' && !$dryRun) {
        update_post_meta($pid, '_igs_tour_cosa_portare', $cosaPortare);
    }
    if ($documenti !== '' && !$dryRun) {
        update_post_meta($pid, '_igs_tour_documenti', $documenti);
    }
    if ($quotaComprende !== '' && !$dryRun) {
        update_post_meta($pid, '_igs_tour_quota_comprende', $quotaComprende);
    }
    if ($quotaNonComprende !== '' && !$dryRun) {
        update_post_meta($pid, '_igs_tour_quota_non_comprende', $quotaNonComprende);
    }
    if ($voli !== '' && !$dryRun) {
        update_post_meta($pid, '_igs_tour_voli', $voli);
    }

    // 3. caratteristiche: cerca blocchi con image_with_animation + shortcode (stesso vc_column)
    $shortcodeToCar = [
        'protagonista_tour' => ['it' => 'Pianta', 'en' => 'Plant', 'meta' => '_protagonista_tour', 'subtitle' => true],
        'livello_culturale' => ['it' => 'Cultura', 'en' => 'Culture', 'meta' => '_livello_culturale', 'rating' => true],
        'livello_passeggiata' => ['it' => 'Passeggiata', 'en' => 'Walking', 'meta' => '_livello_passeggiata', 'rating' => true],
        'livello_piuma' => ['it' => 'Comfort', 'en' => 'Comfort', 'meta' => '_livello_piuma', 'rating' => true],
        'livello_esclusivita' => ['it' => 'Esclusività', 'en' => 'Exclusivity', 'meta' => '_livello_esclusivita', 'rating' => true],
    ];

    $caratteristiche = [];
    foreach ($shortcodeToCar as $shortcode => $cfg) {
        // Pattern 1: image_with_animation ... [shortcode] nello stesso blocco
        $p1 = '/\[image_with_animation[^\]]*image_url="(\d+)"[^\]]*\][\s\S]*?\[' . preg_quote($shortcode) . '\]/';
        // Pattern 2: [shortcode] seguito da blocco con image (ordine inverso)
        $p2 = '/image_url="(\d+)"[^\]]*\][\s\S]{0,800}?\[' . preg_quote($shortcode) . '\]/';
        $imgId = 0;
        if (preg_match($p1, $content, $m)) {
            $imgId = absint($m[1]);
        } elseif (preg_match($p2, $content, $m)) {
            $imgId = absint($m[1]);
        }
        if ($imgId > 0) {
            $car = [
                'icon' => '',
                'icon_image' => $imgId,
                'it' => $cfg['it'],
                'en' => $cfg['en'],
                'subtitle_it' => '',
                'subtitle_en' => '',
                'rating' => 0,
            ];
            if (!empty($cfg['subtitle'])) {
                $sub = get_post_meta($pid, $cfg['meta'], true);
                if (is_string($sub) && $sub !== '') {
                    $car['subtitle_it'] = $sub;
                    $car['subtitle_en'] = $sub;
                }
            }
            if (!empty($cfg['rating'])) {
                $r = get_post_meta($pid, $cfg['meta'], true);
                $car['rating'] = max(0, min(5, absint($r)));
            }
            $caratteristiche[] = $car;
            $stats['caratteristiche']++;
        }
    }
    if (!empty($caratteristiche) && !$dryRun) {
        update_post_meta($pid, '_igs_tour_caratteristiche', $caratteristiche);
    }

    if ($stats['caratteristiche'] > 0) {
        echo "  - Caratteristiche: " . $stats['caratteristiche'] . " card\n";
    }
    $dettagliCount = ($cosaPortare !== '' ? 1 : 0) + ($documenti !== '' ? 1 : 0) + ($quotaComprende !== '' ? 1 : 0)
        + ($quotaNonComprende !== '' ? 1 : 0) + ($voli !== '' ? 1 : 0);
    if ($dettagliCount > 0) {
        echo "  - Dettagli viaggio: " . $dettagliCount . " sezioni\n";
    }

    echo "\n";
}

echo "=== Fine import ===\n";
echo "</pre>";
echo '<p><a href="' . esc_url(admin_url('edit.php?post_type=product')) . '">← Torna ai prodotti</a></p>';
