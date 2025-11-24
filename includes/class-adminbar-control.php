<?php
if (!class_exists('WP_Customize_Control')) return;

class ASC_AdminBar_Checklist_Control extends WP_Customize_Control {
    public $type = 'asc_checklist';
    public function render_content() {
        global $wp_admin_bar;
        if (!is_object($wp_admin_bar)) { echo '<p>Admin bar not loaded in this context.</p>'; return; }
        if (empty($wp_admin_bar->get_nodes())) { @do_action('admin_bar_menu', $wp_admin_bar); }
        $nodes = $wp_admin_bar->get_nodes();
        $saved = $this->value(); if(!is_array($saved)) $saved = [];
        ?>
        <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
        <div style="max-height:300px; overflow-y:auto; background:#fff; border:1px solid #ddd; padding:10px;">
            <?php if($nodes): foreach($nodes as $node): if(!$node->parent): ?>
            <label style="display:flex; align-items:center; margin-bottom:5px;">
                <input type="checkbox" value="<?php echo esc_attr($node->id); ?>" class="asc-hidden-node" <?php checked(in_array($node->id, $saved)); ?>>
                <span style="margin-left:5px; font-size:12px;"><?php echo esc_html(strip_tags($node->title)?:$node->id); ?></span>
            </label>
            <?php endif; endforeach; else: echo "No items."; endif; ?>
        </div>
        <input type="hidden" <?php $this->link(); ?> id="asc_hidden_input" value="<?php echo esc_attr(json_encode($saved)); ?>">
        <script>jQuery(document).ready(function($){$('.asc-hidden-node').on('change',function(){var c=[];$('.asc-hidden-node:checked').each(function(){c.push($(this).val())});$('#asc_hidden_input').val(JSON.stringify(c)).trigger('change')})});</script>
        <?php
    }
}