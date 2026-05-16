<?php

namespace NoorAlKhalij\HRSystem;

if (!defined('ABSPATH')) {
    exit;
}

class Login_Shortcode
{
    private const SHORTCODE = 'nak_hr_login';

    public static function register(): void
    {
        add_shortcode(self::SHORTCODE, [self::class, 'render']);
    }

    public static function render(): string
    {
        if (is_user_logged_in()) {
            return '<div class="nak-hr-auth-shell"><div class="nak-hr-auth-card"><div class="nak-hr-alert nak-hr-alert--success">' . esc_html__('You are already logged in.', 'nooralkhalij-hr-system') . '</div></div></div>';
        }

        wp_enqueue_style('nak-hr-frontend');

        $messages = self::handle_submission();
        $defaults = [
            'login' => '',
        ];

        if (!empty($_POST['nak_hr_login']) && is_array($_POST['nak_hr_login'])) {
            $defaults = wp_parse_args(wp_unslash($_POST['nak_hr_login']), $defaults);
        }

        ob_start();
        ?>
        <div class="nak-hr-auth-shell">
            <div class="nak-hr-auth-card">
                <div class="nak-hr-auth-copy">
                    <h2><?php esc_html_e('Login', 'nooralkhalij-hr-system'); ?></h2>
                    <p><?php esc_html_e('Sign in using your company account to access the HR system.', 'nooralkhalij-hr-system'); ?></p>
                </div>

                <?php foreach ($messages as $message) : ?>
                    <div class="nak-hr-alert nak-hr-alert--<?php echo esc_attr($message['type']); ?>">
                        <?php echo esc_html($message['text']); ?>
                    </div>
                <?php endforeach; ?>

                <form method="post" class="nak-hr-auth-form" novalidate>
                    <?php wp_nonce_field('nak_hr_login_action', 'nak_hr_login_nonce'); ?>

                    <label>
                        <span><?php esc_html_e('Username or email', 'nooralkhalij-hr-system'); ?></span>
                        <input type="text" name="nak_hr_login[login]" value="<?php echo esc_attr($defaults['login']); ?>" required>
                    </label>

                    <label>
                        <span><?php esc_html_e('Password', 'nooralkhalij-hr-system'); ?></span>
                        <input type="password" name="nak_hr_login[password]" required>
                    </label>

                    <button type="submit"><?php esc_html_e('Login', 'nooralkhalij-hr-system'); ?></button>
                </form>
            </div>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    private static function handle_submission(): array
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            return [];
        }

        if (empty($_POST['nak_hr_login_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nak_hr_login_nonce'])), 'nak_hr_login_action')) {
            return [];
        }

        $data = isset($_POST['nak_hr_login']) && is_array($_POST['nak_hr_login'])
            ? wp_unslash($_POST['nak_hr_login'])
            : [];

        $login = sanitize_text_field($data['login'] ?? '');
        $password = (string) ($data['password'] ?? '');

        if ($login === '' || $password === '') {
            return [[
                'type' => 'error',
                'text' => __('Please enter both your login and password.', 'nooralkhalij-hr-system'),
            ]];
        }

        $user = get_user_by('email', $login);
        $user_login = $user ? $user->user_login : $login;

        $signon = wp_signon([
            'user_login' => $user_login,
            'user_password' => $password,
            'remember' => true,
        ], is_ssl());

        if (is_wp_error($signon)) {
            return [[
                'type' => 'error',
                'text' => __('Invalid login details. Please try again.', 'nooralkhalij-hr-system'),
            ]];
        }

        $interval_minutes = (int) get_option('nak_hr_quiz_interval_minutes', 0);
        $current_timestamp = current_time('timestamp');
        $next_allowed_at = (int) get_user_meta($signon->ID, 'nak_quiz_popup_next_allowed_at', true);
        $should_show_quiz = $next_allowed_at <= 0 || $current_timestamp >= $next_allowed_at;

        update_user_meta($signon->ID, 'nak_should_show_quiz_popup', $should_show_quiz ? 1 : 0);

        if ($should_show_quiz) {
            update_user_meta($signon->ID, 'nak_quiz_popup_next_allowed_at', $current_timestamp + ($interval_minutes * MINUTE_IN_SECONDS));
        }

        wp_safe_redirect(home_url('/my-account'));
        exit;
    }
}
