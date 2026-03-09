<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Admin;

class GlobalStringsSettings
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_init', [$this, 'init']);
        add_filter('gettext', [$this, 'applyReplacementsGettext'], 20, 3);
        add_filter('ngettext', [$this, 'applyReplacementsNgettext'], 20, 5);
    }

    public function addMenu(): void
    {
        add_options_page(
            'Gestione Testo Globale',
            'Gestione Testo',
            'manage_options',
            'gw-global-strings',
            [$this, 'renderPage']
        );
    }

    public function init(): void
    {
        register_setting('gw_global_strings_group', 'gw_string_replacements_global', [
            'sanitize_callback' => 'wp_kses_post',
        ]);

        add_settings_section(
            'gw_global_strings_section',
            'Regole di Sostituzione Testo',
            [$this, 'sectionCallback'],
            'gw-global-strings'
        );

        add_settings_field(
            'gw_string_replacements_global_field',
            'Inserisci le tue regole',
            [$this, 'fieldCallback'],
            'gw-global-strings',
            'gw_global_strings_section'
        );
    }

    public function sectionCallback(): void
    {
        echo '<p>Inserisci una regola per riga. Separa il testo originale dal nuovo testo usando il simbolo della pipe "|".</p>';
        echo '<strong>Esempio:</strong> <code>Prodotti correlati | Ti potrebbe interessare anche</code>';
    }

    public function fieldCallback(): void
    {
        $options = get_option('gw_string_replacements_global', '');
        echo '<textarea name="gw_string_replacements_global" rows="10" cols="80" style="width: 100%;">' . esc_textarea($options) . '</textarea>';
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('gw_global_strings_group');
                do_settings_sections('gw-global-strings');
                submit_button('Salva Modifiche');
                ?>
            </form>
        </div>
        <?php
    }

    public function applyReplacementsGettext(string $translatedText, string $text, string $domain): string
    {
        $replacements = get_option('gw_string_replacements_global', '');
        if (empty($replacements)) {
            return $translatedText;
        }

        static $rules = null;
        if ($rules === null) {
            $rules = [];
            $lines = explode("\n", $replacements);
            foreach ($lines as $line) {
                if (strpos($line, '|') !== false) {
                    $parts = array_map('trim', explode('|', $line, 2));
                    if (!empty($parts[0])) {
                        $rules[$parts[0]] = $parts[1] ?? '';
                    }
                }
            }
        }

        return $rules[$translatedText] ?? $translatedText;
    }

    public function applyReplacementsNgettext(string $translated, string $single, string $plural, int $number, string $domain): string
    {
        return $this->applyReplacementsGettext($translated, $single, $domain);
    }
}
