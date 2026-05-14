<?php

if (!defined('ABSPATH')) {
    exit;
}
?>
<ul class="nak-hr-dashboard-list">
    <li><strong><?php esc_html_e('Name:', 'nooralkhalij-hr-system'); ?></strong> <?php echo esc_html($user->display_name ?: $user->user_login); ?></li>
    <li><strong><?php esc_html_e('Email:', 'nooralkhalij-hr-system'); ?></strong> <?php echo esc_html($user->user_email); ?></li>
    <li><strong><?php esc_html_e('Role:', 'nooralkhalij-hr-system'); ?></strong> <?php echo esc_html(implode(', ', $role_labels)); ?></li>
</ul>
