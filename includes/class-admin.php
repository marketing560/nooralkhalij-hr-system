<?php

namespace NoorAlKhalij\HRSystem;

if (!defined('ABSPATH')) {
    exit;
}

class Admin
{
    public static function register_menu(): void
    {
        add_menu_page(
            __('HR System', 'nooralkhalij-hr-system'),
            __('HR System', 'nooralkhalij-hr-system'),
            'manage_options',
            'nak-hr-system',
            [self::class, 'render_shortcode_page'],
            'dashicons-groups',
            58
        );
    }

    public static function render_shortcode_page(): void
    {
        $shortcodes = [
            [
                'tag' => '[nak_hr_signup]',
                'description' => __('Displays the company signup form for creating new users with @infinityecn.com emails only.', 'nooralkhalij-hr-system'),
            ],
        ];
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Noor Al Khalij HR System', 'nooralkhalij-hr-system'); ?></h1>
            <p><?php esc_html_e('Use these shortcodes on any page to render plugin features.', 'nooralkhalij-hr-system'); ?></p>

            <table class="widefat striped" style="max-width: 980px; margin-top: 20px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Shortcode', 'nooralkhalij-hr-system'); ?></th>
                        <th><?php esc_html_e('Description', 'nooralkhalij-hr-system'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shortcodes as $shortcode) : ?>
                        <tr>
                            <td><code><?php echo esc_html($shortcode['tag']); ?></code></td>
                            <td><?php echo esc_html($shortcode['description']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
