<?php
if (!class_exists('WP_Customize_Control')) return;
class ASC_Tools_Control extends WP_Customize_Control {
    public $type = 'asc_tools';
    public function render_content() {
        ?>
        <div style="padding:10px; background:#fff; border:1px solid #ddd;">
            <h3>Preview</h3>
            <button class="button button-primary" id="asc-btn-preview-login" style="width:100%; margin-bottom:10px;">Preview Login Mock</button>
            <button class="button" id="asc-btn-preview-home" style="width:100%;">Preview Site (Admin Bar)</button>
            <hr>
            <h3>Data</h3>
            <button class="button" id="asc-btn-export">Export</button>
            <button class="button button-link-delete" id="asc-btn-reset" style="float:right;">Reset</button>
            <textarea id="asc-import-textarea" style="width:100%; margin-top:10px;"></textarea>
            <button class="button" id="asc-btn-import" style="margin-top:5px;">Import</button>
        </div>
        <?php
    }
}