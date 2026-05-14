<?php

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="nak-hr-wiki-toolbar">
    <form method="get" class="nak-hr-inline-search">
        <input type="hidden" name="nak_section" value="infinity-wiki">
        <input type="search" name="wiki_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search question text', 'nooralkhalij-hr-system'); ?>">
        <button type="submit" class="nak-hr-action-button"><?php esc_html_e('Search', 'nooralkhalij-hr-system'); ?></button>
        <?php if ($search !== '') : ?>
            <a class="nak-hr-action-button nak-hr-action-button--secondary" href="<?php echo esc_url(add_query_arg('nak_section', 'infinity-wiki', get_permalink() ?: '')); ?>"><?php esc_html_e('Clear', 'nooralkhalij-hr-system'); ?></a>
        <?php endif; ?>
    </form>

    <?php if ($is_master) : ?>
        <button
            type="button"
            class="nak-hr-action-button"
            data-wiki-open
            data-ajax-url="<?php echo esc_url($ajax_url); ?>"
            data-nonce="<?php echo esc_attr($modal_nonce); ?>"
        >
            <?php esc_html_e('Add Question', 'nooralkhalij-hr-system'); ?>
        </button>
    <?php endif; ?>
</div>

<?php if (empty($items)) : ?>
    <div class="nak-hr-dashboard-empty">
        <h3><?php esc_html_e('No questions found', 'nooralkhalij-hr-system'); ?></h3>
        <p><?php echo esc_html($search !== '' ? __('No questions matched your search.', 'nooralkhalij-hr-system') : __('There are no questions yet.', 'nooralkhalij-hr-system')); ?></p>
    </div>
<?php else : ?>
    <div class="nak-hr-wiki-list" data-wiki-list>
        <?php foreach ($items as $item) : ?>
            <?php
            $choices = json_decode((string) $item->choices, true);
            $answer = '';

            if (is_array($choices) && isset($choices[(int) $item->answer_key])) {
                $answer = (string) $choices[(int) $item->answer_key];
            }
            ?>
            <div class="nak-hr-wiki-item" data-question-id="<?php echo esc_attr($item->id); ?>">
                <div class="nak-hr-wiki-item-head">
                    <div class="nak-hr-wiki-meta"><?php echo esc_html(Plugin::get_employee_roles()[$item->role] ?? $item->role); ?></div>
                    <?php if ($is_master) : ?>
                        <div class="nak-hr-wiki-actions">
                            <button
                                type="button"
                                class="nak-hr-link-button"
                                data-wiki-open
                                data-question-id="<?php echo esc_attr($item->id); ?>"
                                data-ajax-url="<?php echo esc_url($ajax_url); ?>"
                                data-nonce="<?php echo esc_attr($modal_nonce); ?>"
                            >
                                <?php esc_html_e('Edit', 'nooralkhalij-hr-system'); ?>
                            </button>
                            <button
                                type="button"
                                class="nak-hr-link-button nak-hr-link-button--danger"
                                data-wiki-delete
                                data-question-id="<?php echo esc_attr($item->id); ?>"
                                data-ajax-url="<?php echo esc_url($ajax_url); ?>"
                                data-nonce="<?php echo esc_attr($modal_nonce); ?>"
                            >
                                <?php esc_html_e('Delete', 'nooralkhalij-hr-system'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                <h3><?php echo esc_html($item->question); ?></h3>
                <p><strong><?php esc_html_e('Answer:', 'nooralkhalij-hr-system'); ?></strong> <?php echo esc_html($answer); ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <?php
    echo paginate_links([
        'base' => add_query_arg([
            'nak_section' => 'infinity-wiki',
            'wiki_search' => $search,
            'wiki_paged' => '%#%',
        ], get_permalink() ?: ''),
        'format' => '',
        'prev_text' => __('&laquo; Previous', 'nooralkhalij-hr-system'),
        'next_text' => __('Next &raquo;', 'nooralkhalij-hr-system'),
        'total' => $total_pages,
        'current' => $current_page,
        'type' => 'plain',
    ]);
    ?>
<?php endif; ?>
