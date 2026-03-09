<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Admin;

use PHPMailer\PHPMailer\PHPMailer;

class EmailSettings
{
    private const OPTION_RECIPIENTS = 'igs_info_email_recipients';
    private const OPTION_FROM_NAME = 'igs_info_email_from_name';
    private const OPTION_FROM_EMAIL = 'igs_info_email_from_email';
    private const OPTION_SUBJECT = 'igs_info_email_subject';
    private const OPTION_BODY = 'igs_info_email_body';
    private const OPTION_SMTP_ENABLED = 'igs_smtp_enabled';
    private const OPTION_SMTP_HOST = 'igs_smtp_host';
    private const OPTION_SMTP_PORT = 'igs_smtp_port';
    private const OPTION_SMTP_USER = 'igs_smtp_user';
    private const OPTION_SMTP_PASS = 'igs_smtp_pass';
    private const OPTION_SMTP_ENCRYPTION = 'igs_smtp_encryption';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('phpmailer_init', [$this, 'configurePhpMailer'], 10, 1);
        add_action('wp_ajax_igs_send_test_email', [$this, 'ajaxSendTestEmail']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    public function addMenu(): void
    {
        add_options_page(
            __('Impostazioni Email IGS', 'igs-ecommerce'),
            'IGS Email',
            'manage_options',
            'igs-email-settings',
            [$this, 'renderPage']
        );
    }

    public function registerSettings(): void
    {
        register_setting('igs_email_group', self::OPTION_RECIPIENTS, [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitizeRecipients'],
        ]);
        register_setting('igs_email_group', self::OPTION_FROM_NAME, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting('igs_email_group', self::OPTION_FROM_EMAIL, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_email',
        ]);
        register_setting('igs_email_group', self::OPTION_SUBJECT, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting('igs_email_group', self::OPTION_BODY, [
            'type' => 'string',
            'sanitize_callback' => 'wp_kses_post',
        ]);
        register_setting('igs_email_group', self::OPTION_SMTP_ENABLED, [
            'type' => 'boolean',
            'sanitize_callback' => static fn ($v) => (bool) $v,
        ]);
        register_setting('igs_email_group', self::OPTION_SMTP_HOST, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting('igs_email_group', self::OPTION_SMTP_PORT, [
            'type' => 'integer',
            'sanitize_callback' => static fn ($v) => max(1, min(65535, absint($v))),
        ]);
        register_setting('igs_email_group', self::OPTION_SMTP_USER, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting('igs_email_group', self::OPTION_SMTP_PASS, [
            'type' => 'string',
            'sanitize_callback' => [$this, 'sanitizeSmtpPass'],
        ]);
        register_setting('igs_email_group', self::OPTION_SMTP_ENCRYPTION, [
            'type' => 'string',
            'sanitize_callback' => static fn ($v) => in_array($v, ['none', 'ssl', 'tls'], true) ? $v : 'tls',
        ]);
    }

    public function sanitizeRecipients(string $value): string
    {
        $emails = array_map('trim', explode(',', $value));
        $valid = [];
        foreach ($emails as $e) {
            if ($e !== '' && is_email($e)) {
                $valid[] = sanitize_email($e);
            }
        }
        return implode(', ', $valid);
    }

    public function sanitizeSmtpPass(mixed $value): string
    {
        if (!is_string($value) || $value === '') {
            return get_option(self::OPTION_SMTP_PASS, '');
        }
        return $value;
    }

    public function configurePhpMailer(PHPMailer $phpmailer): void
    {
        if (!get_option(self::OPTION_SMTP_ENABLED, false)) {
            return;
        }
        $host = get_option(self::OPTION_SMTP_HOST, '');
        if ($host === '') {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host = $host;
        $phpmailer->Port = (int) get_option(self::OPTION_SMTP_PORT, 587);
        $user = get_option(self::OPTION_SMTP_USER, '');
        $phpmailer->SMTPAuth = $user !== '';
        $phpmailer->Username = $user;
        $phpmailer->Password = get_option(self::OPTION_SMTP_PASS, '');
        $enc = get_option(self::OPTION_SMTP_ENCRYPTION, 'tls');
        $phpmailer->SMTPSecure = $enc === 'none' ? '' : $enc;
    }

    public function ajaxSendTestEmail(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti.', 'igs-ecommerce')]);
        }
        check_ajax_referer('igs_test_email_nonce', 'nonce');

        $to = isset($_POST['test_email']) ? sanitize_email(wp_unslash($_POST['test_email'])) : '';
        if (!is_email($to)) {
            wp_send_json_error(['message' => __('Indirizzo email non valido.', 'igs-ecommerce')]);
        }

        $subject = __('IGS – Test invio email', 'igs-ecommerce');
        $body = '<p>' . esc_html__('Questa è un\'email di test inviata dalle impostazioni IGS Ecommerce.', 'igs-ecommerce') . '</p>';
        $body .= '<p>' . esc_html__('Se la ricevi, l\'invio funziona correttamente.', 'igs-ecommerce') . '</p>';
        $body .= '<p><em>Inviato il ' . esc_html(wp_date('d/m/Y H:i')) . '</em></p>';

        $headers = $this->buildHeaders();
        $sent = wp_mail($to, $subject, $body, $headers);

        if ($sent) {
            wp_send_json_success(['message' => __('Email di test inviata con successo. Controlla la casella (anche spam).', 'igs-ecommerce')]);
        } else {
            wp_send_json_error(['message' => __('Invio fallito. Verifica la configurazione SMTP e i log del server.', 'igs-ecommerce')]);
        }
    }

    public function enqueueScripts(string $hook): void
    {
        if ($hook !== 'settings_page_igs-email-settings') {
            return;
        }
        wp_add_inline_script('jquery', "
            jQuery(function($){
                $('#igs-send-test').on('click', function(){
                    var \$btn = $(this);
                    var email = $('#igs_test_email').val();
                    if (!email) { alert('" . esc_js(__('Inserisci un indirizzo email.', 'igs-ecommerce')) . "'); return; }
                    \$btn.prop('disabled', true).text('" . esc_js(__('Invio...', 'igs-ecommerce')) . "');
                    $.post(ajaxurl, {
                        action: 'igs_send_test_email',
                        nonce: '" . esc_js(wp_create_nonce('igs_test_email_nonce')) . "',
                        test_email: email
                    }).done(function(r){
                        if (r && r.success) alert(r.data.message);
                        else alert((r && r.data && r.data.message) || '" . esc_js(__('Errore sconosciuto.', 'igs-ecommerce')) . "');
                    }).fail(function(){ alert('" . esc_js(__('Errore di comunicazione.', 'igs-ecommerce')) . "'); })
                    .always(function(){ \$btn.prop('disabled', false).text('" . esc_js(__('Invia email di test', 'igs-ecommerce')) . "'); });
                });
            });
        ");
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $recipients = get_option(self::OPTION_RECIPIENTS, get_option('admin_email'));
        $fromName = get_option(self::OPTION_FROM_NAME, wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
        $fromEmail = get_option(self::OPTION_FROM_EMAIL, get_option('admin_email'));
        $subject = get_option(self::OPTION_SUBJECT, 'Richiesta informazioni per il tour: {tour_title}');
        $body = get_option(self::OPTION_BODY, $this->getDefaultBody());
        $smtpEnabled = (bool) get_option(self::OPTION_SMTP_ENABLED, false);
        $smtpHost = get_option(self::OPTION_SMTP_HOST, '');
        $smtpPort = get_option(self::OPTION_SMTP_PORT, '587');
        $smtpUser = get_option(self::OPTION_SMTP_USER, '');
        $smtpPass = get_option(self::OPTION_SMTP_PASS, '');
        $smtpEnc = get_option(self::OPTION_SMTP_ENCRYPTION, 'tls');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Impostazioni Email IGS', 'igs-ecommerce'); ?></h1>
            <p><?php echo esc_html__('Configura dove inviare le richieste "Richiedi info" e personalizza mittente, oggetto e template dell\'email.', 'igs-ecommerce'); ?></p>

            <form action="options.php" method="post" id="igs-email-form">
                <?php settings_fields('igs_email_group'); ?>

                <h2 class="title"><?php echo esc_html__('Destinatari', 'igs-ecommerce'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="igs_info_email_recipients"><?php echo esc_html__('Email destinatarie', 'igs-ecommerce'); ?></label></th>
                        <td>
                            <input type="text" id="igs_info_email_recipients" name="<?php echo esc_attr(self::OPTION_RECIPIENTS); ?>"
                                   value="<?php echo esc_attr($recipients); ?>" class="regular-text">
                            <p class="description"><?php echo esc_html__('Indirizzi separati da virgola che riceveranno le richieste.', 'igs-ecommerce'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2 class="title"><?php echo esc_html__('Mittente (From)', 'igs-ecommerce'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="igs_info_email_from_name"><?php echo esc_html__('Nome mittente', 'igs-ecommerce'); ?></label></th>
                        <td>
                            <input type="text" id="igs_info_email_from_name" name="<?php echo esc_attr(self::OPTION_FROM_NAME); ?>"
                                   value="<?php echo esc_attr($fromName); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="igs_info_email_from_email"><?php echo esc_html__('Email mittente', 'igs-ecommerce'); ?></label></th>
                        <td>
                            <input type="email" id="igs_info_email_from_email" name="<?php echo esc_attr(self::OPTION_FROM_EMAIL); ?>"
                                   value="<?php echo esc_attr($fromEmail); ?>" class="regular-text">
                            <p class="description"><?php echo esc_html__('L\'indirizzo che apparirà come mittente.', 'igs-ecommerce'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2 class="title"><?php echo esc_html__('Template email', 'igs-ecommerce'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="igs_info_email_subject"><?php echo esc_html__('Oggetto', 'igs-ecommerce'); ?></label></th>
                        <td>
                            <input type="text" id="igs_info_email_subject" name="<?php echo esc_attr(self::OPTION_SUBJECT); ?>"
                                   value="<?php echo esc_attr($subject); ?>" class="large-text">
                            <p class="description"><?php echo esc_html__('Placeholder:', 'igs-ecommerce'); ?> <code>{tour_title}</code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="igs_info_email_body"><?php echo esc_html__('Corpo (HTML)', 'igs-ecommerce'); ?></label></th>
                        <td>
                            <textarea id="igs_info_email_body" name="<?php echo esc_attr(self::OPTION_BODY); ?>"
                                      rows="14" class="large-text code"><?php echo esc_textarea($body); ?></textarea>
                            <p class="description"><?php echo esc_html__('Placeholder:', 'igs-ecommerce'); ?> <code>{tour_title}</code>, <code>{nome}</code>, <code>{email}</code>, <code>{messaggio}</code></p>
                        </td>
                    </tr>
                </table>

                <h2 class="title"><?php echo esc_html__('SMTP', 'igs-ecommerce'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Usa SMTP', 'igs-ecommerce'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(self::OPTION_SMTP_ENABLED); ?>" value="1"
                                       <?php checked($smtpEnabled); ?>>
                                <?php echo esc_html__('Abilita invio tramite SMTP', 'igs-ecommerce'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="igs_smtp_host"><?php echo esc_html__('Host SMTP', 'igs-ecommerce'); ?></label></th>
                        <td>
                            <input type="text" id="igs_smtp_host" name="<?php echo esc_attr(self::OPTION_SMTP_HOST); ?>"
                                   value="<?php echo esc_attr($smtpHost); ?>" class="regular-text"
                                   placeholder="<?php echo esc_attr__('es. smtp.example.com', 'igs-ecommerce'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="igs_smtp_port"><?php echo esc_html__('Porta', 'igs-ecommerce'); ?></label></th>
                        <td>
                            <input type="number" id="igs_smtp_port" name="<?php echo esc_attr(self::OPTION_SMTP_PORT); ?>"
                                   value="<?php echo esc_attr($smtpPort); ?>" min="1" max="65535" style="width:80px">
                            <p class="description"><?php echo esc_html__('Di solito 587 (TLS) o 465 (SSL).', 'igs-ecommerce'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="igs_smtp_encryption"><?php echo esc_html__('Crittografia', 'igs-ecommerce'); ?></label></th>
                        <td>
                            <select id="igs_smtp_encryption" name="<?php echo esc_attr(self::OPTION_SMTP_ENCRYPTION); ?>">
                                <option value="none" <?php selected($smtpEnc, 'none'); ?>><?php echo esc_html__('Nessuna', 'igs-ecommerce'); ?></option>
                                <option value="ssl" <?php selected($smtpEnc, 'ssl'); ?>>SSL</option>
                                <option value="tls" <?php selected($smtpEnc, 'tls'); ?>>TLS</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="igs_smtp_user"><?php echo esc_html__('Username', 'igs-ecommerce'); ?></label></th>
                        <td>
                            <input type="text" id="igs_smtp_user" name="<?php echo esc_attr(self::OPTION_SMTP_USER); ?>"
                                   value="<?php echo esc_attr($smtpUser); ?>" class="regular-text" autocomplete="off">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="igs_smtp_pass"><?php echo esc_html__('Password', 'igs-ecommerce'); ?></label></th>
                        <td>
                            <input type="password" id="igs_smtp_pass" name="<?php echo esc_attr(self::OPTION_SMTP_PASS); ?>"
                                   value="<?php echo esc_attr($smtpPass); ?>" class="regular-text" autocomplete="new-password">
                            <p class="description"><?php echo esc_html__('Lascia vuoto per non modificare la password salvata.', 'igs-ecommerce'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Salva impostazioni', 'igs-ecommerce')); ?>
            </form>

            <hr>
            <h2 class="title"><?php echo esc_html__('Test invio', 'igs-ecommerce'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="igs_test_email"><?php echo esc_html__('Invia email di test a', 'igs-ecommerce'); ?></label></th>
                    <td>
                        <input type="email" id="igs_test_email" value="<?php echo esc_attr(get_option('admin_email')); ?>" class="regular-text">
                        <button type="button" id="igs-send-test" class="button"><?php echo esc_html__('Invia email di test', 'igs-ecommerce'); ?></button>
                        <p class="description"><?php echo esc_html__('Verifica che l\'invio (incluso SMTP se attivo) funzioni correttamente.', 'igs-ecommerce'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    private function getDefaultBody(): string
    {
        return '<html><body style="font-family: sans-serif;">
<h2>Nuova Richiesta Informazioni</h2>
<p>Hai ricevuto una nuova richiesta per il tour: <strong>{tour_title}</strong></p>
<ul>
<li><strong>Nome:</strong> {nome}</li>
<li><strong>Email:</strong> {email}</li>
</ul>
<h4>Messaggio:</h4>
<p style="border-left: 3px solid #eee; padding-left: 15px; font-style: italic;">{messaggio}</p>
</body></html>';
    }

    public static function buildHeaders(): array
    {
        $fromName = get_option(self::OPTION_FROM_NAME, wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
        $fromEmail = get_option(self::OPTION_FROM_EMAIL, get_option('admin_email'));
        if (!is_email($fromEmail)) {
            $fromEmail = get_option('admin_email');
        }
        return [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . wp_specialchars_decode($fromName, ENT_QUOTES) . ' <' . $fromEmail . '>',
        ];
    }

    public static function getRecipients(): string
    {
        $r = get_option(self::OPTION_RECIPIENTS, '');
        return $r !== '' ? $r : get_option('admin_email');
    }

    public static function getSubjectTemplate(): string
    {
        $s = get_option(self::OPTION_SUBJECT, '');
        return $s !== '' ? $s : 'Richiesta informazioni per il tour: {tour_title}';
    }

    public static function getBodyTemplate(): string
    {
        $b = get_option(self::OPTION_BODY, '');
        return $b !== '' ? $b : (new self())->getDefaultBody();
    }
}
