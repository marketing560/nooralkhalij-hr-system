<?php

namespace NoorAlKhalij\HRSystem;

if (!defined('ABSPATH')) {
    exit;
}

require_once NAK_HR_PLUGIN_DIR . 'includes/class-admin.php';
require_once NAK_HR_PLUGIN_DIR . 'includes/class-signup-shortcode.php';

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

    public static function activate(): void
    {
        self::register_roles();
    }

    public static function register_roles(): void
    {
        add_role(
            'nak_employee',
            __('Employee', 'nooralkhalij-hr-system'),
            [
                'read' => true,
            ]
        );
    }

    private function __construct()
    {
        add_action('init', [$this, 'register_shortcodes']);
        add_action('init', [self::class, 'register_roles']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_shortcodes(): void
    {
        Signup_Shortcode::register();
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
}
