<?php

namespace NoorAlKhalij\HRSystem;

if (!defined('ABSPATH')) {
    exit;
}

require_once NAK_HR_PLUGIN_DIR . 'includes/class-admin.php';
require_once NAK_HR_PLUGIN_DIR . 'includes/class-signup-shortcode.php';
require_once NAK_HR_PLUGIN_DIR . 'includes/class-login-shortcode.php';
require_once NAK_HR_PLUGIN_DIR . 'includes/class-auth-gateway-shortcode.php';
require_once NAK_HR_PLUGIN_DIR . 'includes/class-dashboard-shortcode.php';

class Plugin
{
    private static ?Plugin $instance = null;

    public static function instance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function get_employee_roles(): array
    {
        return [
            'nak_employee' => __('Employee', 'nooralkhalij-hr-system'),
            'nak_sales' => __('Sales', 'nooralkhalij-hr-system'),
            'nak_retention' => __('Retention', 'nooralkhalij-hr-system'),
            'nak_qc' => __('QC', 'nooralkhalij-hr-system'),
            'nak_marketing' => __('Marketing', 'nooralkhalij-hr-system'),
        ];
    }

    public static function activate(): void
    {
        self::register_roles();
    }

    public static function register_roles(): void
    {
        foreach (self::get_employee_roles() as $role_key => $role_label) {
            add_role(
                $role_key,
                $role_label,
                [
                    'read' => true,
                ]
            );
        }
    }

    private function __construct()
    {
        add_action('init', [$this, 'register_shortcodes']);
        add_action('init', [self::class, 'register_roles']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('after_setup_theme', [$this, 'maybe_hide_admin_bar']);
    }

    public function register_shortcodes(): void
    {
        Signup_Shortcode::register();
        Login_Shortcode::register();
        Auth_Gateway_Shortcode::register();
        Dashboard_Shortcode::register();
    }

    public function register_admin_menu(): void
    {
        Admin::register_menu();
    }

    public function enqueue_assets(): void
    {
        wp_register_style(
            'nak-hr-frontend',
            NAK_HR_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            NAK_HR_VERSION
        );
    }

    public function maybe_hide_admin_bar(): void
    {
        if (!is_user_logged_in() || current_user_can('manage_options')) {
            return;
        }

        $user = wp_get_current_user();
        $employee_roles = array_keys(self::get_employee_roles());

        if (!empty(array_intersect($employee_roles, (array) $user->roles))) {
            show_admin_bar(false);
        }
    }
}
