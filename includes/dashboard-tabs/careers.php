<?php

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="nak-hr-wiki-toolbar">
    <div></div>
    <a class="nak-hr-action-button" href="<?php echo esc_url(add_query_arg([
        'nak_section' => 'careers',
        'career_form' => 1,
    ], get_permalink() ?: '')); ?>"><?php esc_html_e('Add Position', 'nooralkhalij-hr-system'); ?></a>
</div>

<?php if (empty($items)) : ?>
    <div class="nak-hr-dashboard-empty">
        <h3><?php esc_html_e('No positions found', 'nooralkhalij-hr-system'); ?></h3>
        <p><?php esc_html_e('There are no career positions yet.', 'nooralkhalij-hr-system'); ?></p>
    </div>
<?php else : ?>
    <div class="nak-hr-wiki-list">
        <?php foreach ($items as $item) : ?>
            <div class="nak-hr-wiki-item">
                <div class="nak-hr-wiki-item-head">
                    <div class="nak-hr-wiki-meta <?php echo $item->is_active ? '' : 'nak-hr-wiki-meta--inactive'; ?>">
                        <?php echo esc_html($item->is_active ? __('Active', 'nooralkhalij-hr-system') : __('Inactive', 'nooralkhalij-hr-system')); ?>
                    </div>
                    <div class="nak-hr-wiki-actions">
                        <a href="<?php echo esc_url(add_query_arg([
                            'nak_section' => 'careers',
                            'career_edit' => $item->id,
                            'career_form' => 1,
                        ], get_permalink() ?: '')); ?>"><?php esc_html_e('Edit', 'nooralkhalij-hr-system'); ?></a>
                        <form method="post">
                            <?php wp_nonce_field('nak_hr_career_action', 'nak_hr_career_nonce'); ?>
                            <input type="hidden" name="career_action" value="toggle">
                            <input type="hidden" name="career_id" value="<?php echo esc_attr($item->id); ?>">
                            <button type="submit" class="nak-hr-link-button"><?php echo esc_html($item->is_active ? __('Deactivate', 'nooralkhalij-hr-system') : __('Activate', 'nooralkhalij-hr-system')); ?></button>
                        </form>
                        <form method="post" onsubmit="return confirm('<?php echo esc_js(__('Delete this position?', 'nooralkhalij-hr-system')); ?>');">
                            <?php wp_nonce_field('nak_hr_career_action', 'nak_hr_career_nonce'); ?>
                            <input type="hidden" name="career_action" value="delete">
                            <input type="hidden" name="career_id" value="<?php echo esc_attr($item->id); ?>">
                            <button type="submit" class="nak-hr-link-button nak-hr-link-button--danger"><?php esc_html_e('Delete', 'nooralkhalij-hr-system'); ?></button>
                        </form>
                    </div>
                </div>
                <h3><?php echo esc_html($item->title); ?></h3>
                <p><?php echo esc_html(wp_trim_words((string) $item->details, 30)); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
