<?php

namespace NoorAlKhalij\HRSystem;

if (!defined('ABSPATH')) {
    exit;
}

class Auth_Gateway_Shortcode
{
    private const SHORTCODE = 'nak_hr_auth';

    public static function register(): void
    {
        add_shortcode(self::SHORTCODE, [self::class, 'render']);
    }

    public static function render($atts = []): string
    {
        $atts = shortcode_atts([
            'variant' => 'card',
        ], $atts, self::SHORTCODE);

        wp_enqueue_style('nak-hr-frontend');

        if ($atts['variant'] === 'menu') {
            return self::render_menu_button();
        }

        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $logout_url = wp_logout_url(get_permalink() ?: home_url('/'));
            $dashboard_url = home_url('/my-account');

            ob_start();
            ?>
            <div class="nak-hr-auth-shell">
                <div class="nak-hr-auth-card">
                    <div class="nak-hr-auth-copy">
                        <h2><?php esc_html_e('Welcome back', 'nooralkhalij-hr-system'); ?></h2>
                        <p><?php echo esc_html(sprintf(__('You are signed in as %s.', 'nooralkhalij-hr-system'), $user->display_name ?: $user->user_login)); ?></p>
                    </div>

                    <div class="nak-hr-action-stack">
                        <a class="nak-hr-action-button nak-hr-action-button--primary" href="<?php echo esc_url($dashboard_url); ?>"><?php esc_html_e('Dashboard', 'nooralkhalij-hr-system'); ?></a>
                        <a class="nak-hr-action-button nak-hr-action-button--secondary" href="<?php echo esc_url($logout_url); ?>"><?php esc_html_e('Sign out', 'nooralkhalij-hr-system'); ?></a>
                    </div>
                </div>
            </div>
            <?php

            return (string) ob_get_clean();
        }

        $login_url = home_url('/login');
        $signup_url = home_url('/sign-up');

        ob_start();
        ?>
        <div class="nak-hr-auth-shell">
            <div class="nak-hr-auth-card">
                <div class="nak-hr-auth-copy">
                    <h2><?php esc_html_e('Welcome', 'nooralkhalij-hr-system'); ?></h2>
                    <p><?php esc_html_e('Login to your account or create a new one to access the HR system.', 'nooralkhalij-hr-system'); ?></p>
                </div>

                <div class="nak-hr-action-stack">
                    <a class="nak-hr-action-button nak-hr-action-button--primary" href="<?php echo esc_url($login_url); ?>"><?php esc_html_e('Login', 'nooralkhalij-hr-system'); ?></a>
                    <a class="nak-hr-action-button nak-hr-action-button--secondary" href="<?php echo esc_url($signup_url); ?>"><?php esc_html_e('Create account', 'nooralkhalij-hr-system'); ?></a>
                </div>
            </div>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    private static function render_menu_button(): string
    {
        if (is_user_logged_in()) {
            return '<a class="nak-hr-menu-button" href="' . esc_url(home_url('/my-account')) . '">' . esc_html__('My Account', 'nooralkhalij-hr-system') . '</a>';
        }

        return '<span class="nak-hr-menu-button-group">'
            . '<a class="nak-hr-menu-button" href="' . esc_url(home_url('/login')) . '">' . esc_html__('Login', 'nooralkhalij-hr-system') . '</a>'
            . '<a class="nak-hr-menu-button nak-hr-menu-button--secondary" href="' . esc_url(home_url('/sign-up')) . '">' . esc_html__('Sign up', 'nooralkhalij-hr-system') . '</a>'
            . '</span>';
    }
}
