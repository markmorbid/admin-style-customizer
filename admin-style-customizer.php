<?php
/**
 * Plugin Name: Admin Style Customizer
 * Description: Dynamic Login & Admin Bar Customizer (Exact Layouts).
 * Version: 5.1.0
 * Author: Satoshisea
 */

if (!defined('ABSPATH')) exit;

define('ASC_VERSION', '5.1.0');
define('ASC_PATH', plugin_dir_path(__FILE__));
define('ASC_URL', plugin_dir_url(__FILE__));

class ASC_Plugin {
    private static $instance = null;
    public $defaults = [];

    public static function get_instance() {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        $this->defaults = [
            'login_custom_enable' => false,
            'login_template' => 'flat', // 'flat' (Orange) or 'glass' (Dark)
            
            // Branding
            'login_logo' => '',
            'login_logo_width' => 270,
            'login_logo_height' => 180,
            'login_badge_text' => '', // "Site Admin"
            'login_sub_text' => '',   // "Degent Club"
            
            // Colors & Vars
            'login_brand_color' => '#f7941a',
            'login_alt_color' => '#2e9dd1',
            'login_bg_color' => '#f7941a',
            'login_bg_image' => '',
            'login_bg_overlay' => '#000000',
            'login_bg_opacity' => 0,
            
            // Form
            'login_form_bg' => '#ffffff',
            'login_form_opacity' => 100,
            'login_form_border' => '#222222',
            'login_form_border_width' => 2,
            'login_form_radius' => 0,
            'login_form_blur' => 0,
            
            // Admin Bar
            'adminbar_bgcolor' => '#1d2327',
            'adminbar_text' => '#FFFFFF',
            'adminbar_accent' => '#f7941a',
            'adminbar_height' => '45px',
            'adminbar_hidden_nodes' => [],
            
            'login_custom_css' => '',
        ];

        add_action('customize_register', [$this, 'register_customizer']);
        add_action('customize_preview_init', [$this, 'preview_scripts']);
        add_action('customize_controls_enqueue_scripts', [$this, 'controls_scripts']);
        
        add_action('wp_head', [$this, 'output_adminbar_styles'], 999);
        add_action('admin_head', [$this, 'output_admin_styles'], 999);
        
        // Login Styles (Live & Mock)
        add_action('login_head', [$this, 'output_login_styles'], 999);
        add_action('wp_head', [$this, 'output_login_styles'], 999); 
        add_action('login_enqueue_scripts', [$this, 'enqueue_assets']);
        
        add_action('wp_before_admin_bar_render', [$this, 'hide_adminbar_items'], 999);
        add_action('template_redirect', [$this, 'render_login_mock_page']);

        add_action('wp_ajax_asc_export', [$this, 'ajax_export']);
        add_action('wp_ajax_asc_import', [$this, 'ajax_import']);
        add_action('wp_ajax_asc_reset', [$this, 'ajax_reset']);
    }

    public function get($key) {
        return get_theme_mod('asc_' . $key, $this->defaults[$key] ?? '');
    }

    public function enqueue_assets() {
        wp_enqueue_style('asc-fonts', 'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&family=Montserrat:wght@400;600&display=swap');
    }

    public function preview_scripts() {
        wp_enqueue_script('asc-preview', ASC_URL . 'js/preview.js', ['jquery', 'customize-preview'], ASC_VERSION, true);
    }

    public function controls_scripts() {
        wp_enqueue_script('asc-controls', ASC_URL . 'js/customizer.js', ['jquery', 'customize-controls'], ASC_VERSION, true);
        wp_localize_script('asc-controls', 'ASC', [
            'ajax' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('asc_nonce'),
            'mockUrl' => site_url('?asc_login_preview=1'),
            'homeUrl' => home_url()
        ]);
        if(file_exists(ASC_PATH . 'customizer.css')) wp_enqueue_style('asc-controls-css', ASC_URL . 'customizer.css');
    }

    // --- CUSTOMIZER REGISTRATION ---
    public function register_customizer($wp_customize) {
        $wp_customize->add_panel('asc_panel', ['title' => 'Admin Style Customizer', 'priority' => 30]);

        // 1. Admin Bar
        $this->add_section($wp_customize, 'asc_sec_adminbar', 'Admin Bar Styles');
        $this->add_color($wp_customize, 'asc_sec_adminbar', 'adminbar_bgcolor', 'Background');
        $this->add_color($wp_customize, 'asc_sec_adminbar', 'adminbar_text', 'Text Color');
        $this->add_color($wp_customize, 'asc_sec_adminbar', 'adminbar_accent', 'Accent Color');
        $this->add_text($wp_customize, 'asc_sec_adminbar', 'adminbar_height', 'Height');

        $this->add_section($wp_customize, 'asc_sec_adminbar_menu', 'Admin Bar Visibility');
        require_once ASC_PATH . 'includes/class-adminbar-control.php';
        $wp_customize->add_setting('asc_adminbar_hidden_nodes', ['default' => [], 'transport' => 'refresh']);
        $wp_customize->add_control(new ASC_AdminBar_Checklist_Control($wp_customize, 'asc_adminbar_hidden_nodes', [
            'label' => 'Hide Items', 'section' => 'asc_sec_adminbar_menu'
        ]));

        // 2. Login Main
        $this->add_section($wp_customize, 'asc_sec_login', 'Login - Main');
        $wp_customize->add_setting('asc_login_custom_enable', ['default' => false, 'transport' => 'refresh']);
        $wp_customize->add_control('asc_login_custom_enable', ['type' => 'checkbox', 'label' => 'Enable Custom Login', 'section' => 'asc_sec_login']);
        
        $wp_customize->add_setting('asc_login_template', ['default' => 'flat', 'transport' => 'refresh']);
        $wp_customize->add_control('asc_login_template', [
            'type' => 'select', 'label' => 'Template Base', 'section' => 'asc_sec_login',
            'choices' => ['flat' => 'Flat Orange (Hashpower)', 'glass' => 'Dark Glass (Degent)']
        ]);

        // Branding
        $this->add_image($wp_customize, 'asc_sec_login', 'login_logo', 'Logo Image');
        $this->add_num($wp_customize, 'asc_sec_login', 'login_logo_width', 'Logo Width (px)');
        $this->add_text($wp_customize, 'asc_sec_login', 'login_badge_text', 'Top Badge Text (Optional)');
        $this->add_text($wp_customize, 'asc_sec_login', 'login_sub_text', 'Bottom Text (Optional)');

        // 3. Login Styles
        $this->add_section($wp_customize, 'asc_sec_login_styles', 'Login - Styles');
        $this->add_color($wp_customize, 'asc_sec_login_styles', 'login_brand_color', 'Main Accent Color');
        $this->add_color($wp_customize, 'asc_sec_login_styles', 'login_alt_color', 'Secondary Accent');
        $this->add_color($wp_customize, 'asc_sec_login_styles', 'login_bg_color', 'Page Background');
        $this->add_image($wp_customize, 'asc_sec_login_styles', 'login_bg_image', 'Page Image');
        $this->add_color($wp_customize, 'asc_sec_login_styles', 'login_bg_overlay', 'Overlay Color');
        $this->add_range($wp_customize, 'asc_sec_login_styles', 'login_bg_opacity', 'Overlay Opacity');
        
        $this->add_color($wp_customize, 'asc_sec_login_styles', 'login_form_bg', 'Form Background');
        $this->add_range($wp_customize, 'asc_sec_login_styles', 'login_form_opacity', 'Form Opacity');
        $this->add_color($wp_customize, 'asc_sec_login_styles', 'login_form_border', 'Form Border Color');
        $this->add_num($wp_customize, 'asc_sec_login_styles', 'login_form_border_width', 'Border Width (px)');
        $this->add_num($wp_customize, 'asc_sec_login_styles', 'login_form_radius', 'Border Radius (px)');
        $this->add_num($wp_customize, 'asc_sec_login_styles', 'login_form_blur', 'Backdrop Blur (px)');

        // Custom CSS
        $wp_customize->add_setting('asc_login_custom_css', ['default' => '', 'transport' => 'refresh', 'sanitize_callback' => function($c){return $c;}]);
        $wp_customize->add_control('asc_login_custom_css', [
            'type' => 'textarea', 'label' => 'Custom CSS', 'section' => 'asc_sec_login_styles',
            'input_attrs' => ['style' => 'font-family:monospace; min-height:200px; white-space:pre; overflow-x:scroll;']
        ]);

        // Tools
        require_once ASC_PATH . 'includes/class-tools-control.php';
        $this->add_section($wp_customize, 'asc_sec_tools', 'Tools & Preview');
        $wp_customize->add_setting('asc_tools_dummy', ['sanitize_callback' => '__return_true']);
        $wp_customize->add_control(new ASC_Tools_Control($wp_customize, 'asc_tools_dummy', ['section' => 'asc_sec_tools', 'label' => 'Tools']));
    }

    // Helpers
    private function add_section($wp, $id, $title) { $wp->add_section($id, ['title'=>$title, 'panel'=>'asc_panel']); }
    private function add_color($wp, $sec, $id, $lbl) {
        $wp->add_setting('asc_'.$id, ['default'=>'', 'transport'=>'refresh', 'sanitize_callback'=>'sanitize_hex_color']);
        $wp->add_control(new WP_Customize_Color_Control($wp, 'asc_'.$id, ['label'=>$lbl, 'section'=>$sec]));
    }
    private function add_num($wp, $sec, $id, $lbl) {
        $wp->add_setting('asc_'.$id, ['default'=>'', 'transport'=>'refresh', 'sanitize_callback'=>'absint']);
        $wp->add_control('asc_'.$id, ['type'=>'number', 'label'=>$lbl, 'section'=>$sec]);
    }
    private function add_text($wp, $sec, $id, $lbl) {
        $wp->add_setting('asc_'.$id, ['default'=>'', 'transport'=>'refresh', 'sanitize_callback'=>'sanitize_text_field']);
        $wp->add_control('asc_'.$id, ['type'=>'text', 'label'=>$lbl, 'section'=>$sec]);
    }
    private function add_range($wp, $sec, $id, $lbl) {
        $wp->add_setting('asc_'.$id, ['default'=>'', 'transport'=>'refresh', 'sanitize_callback'=>'absint']);
        $wp->add_control('asc_'.$id, ['type'=>'range', 'label'=>$lbl, 'section'=>$sec, 'input_attrs'=>['min'=>0, 'max'=>100, 'step'=>1]]);
    }
    private function add_image($wp, $sec, $id, $lbl) {
        $wp->add_setting('asc_'.$id, ['default'=>'', 'transport'=>'refresh']);
        $wp->add_control(new WP_Customize_Image_Control($wp, 'asc_'.$id, ['label'=>$lbl, 'section'=>$sec]));
    }
    private function rgba($hex, $opacity) {
        if(empty($hex)) return 'transparent';
        $hex = str_replace('#','',$hex);
        if(strlen($hex)==3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        $r = hexdec(substr($hex,0,2)); $g = hexdec(substr($hex,2,2)); $b = hexdec(substr($hex,4,2));
        return "rgba($r, $g, $b, ".($opacity/100).")";
    }

    // Admin Bar Outputs
    public function output_adminbar_styles() {
        if (!is_admin_bar_showing()) return;
        echo "<style id='asc-vars'>:root {
            --login-wp-adminbar-bgcolor: {$this->get('adminbar_bgcolor')};
            --login-wp-adminbar-text: {$this->get('adminbar_text')};
            --login-wp-adminbar-accent: {$this->get('adminbar_accent')};
            --login-wp-adminbar-height: {$this->get('adminbar_height')};
            --login-wp-adminbar-items-padding: 10px;
            --login-border-radius: 7px;
            --login-border-color-2: rgba(255,255,255,0.1);
            --login-darkbg: #080808;
            --login-darkbg-transp: rgba(11, 11, 13, 0.9);
        }</style>";
        if(file_exists(ASC_PATH . 'css/adminbar.css')) echo '<style>' . file_get_contents(ASC_PATH . 'css/adminbar.css') . '</style>';
    }
    public function output_admin_styles() {
        $this->output_adminbar_styles();
        if(file_exists(ASC_PATH . 'css/wpadmin.css')) echo '<style>' . file_get_contents(ASC_PATH . 'css/wpadmin.css') . '</style>';
    }
    public function hide_adminbar_items() {
        global $wp_admin_bar; $hidden = $this->get('adminbar_hidden_nodes');
        if(!empty($hidden) && is_array($hidden)) foreach($hidden as $n) $wp_admin_bar->remove_node($n);
    }

    // --- THE CORE LOGIC: PREPEND "asc-" PREFIX TO PRESERVE EXACT LAYOUT ---
    public function output_login_styles() {
        if(!$this->get('login_custom_enable')) return;

        $s = function($k){ return $this->get($k); };
        $tpl = $s('login_template');

        // 1. VARIABLES SETUP (Based on Template Selection)
        if ($tpl === 'glass') {
            // DARK GLASS DEFAULTS (Degent)
            $d_brand = '#2efc86'; $d_alt = '#238DCE'; $d_bg = '#080808'; $d_text = '#ffffff';
            $d_form_bg = '#141414'; $d_form_op = 60; $d_blur = 10; $d_radius = 8; $d_border = 'rgba(255,255,255,0.1)'; $d_border_w = 1;
            $d_shadow = '0 10px 30px rgba(0,0,0,0.5)';
            $d_img = 'url("https://degent.club/wp-content/uploads/2025/10/degens_grid-1500x844.jpg")';
            $d_overlay = 'rgba(0,0,0,0.7)';
        } else {
            // FLAT ORANGE DEFAULTS (Hashpower)
            $d_brand = '#f7941a'; $d_alt = '#2e9dd1'; $d_bg = '#f7941a'; $d_text = '#333333';
            $d_form_bg = '#ffffff'; $d_form_op = 100; $d_blur = 0; $d_radius = 0; $d_border = '#222222'; $d_border_w = 2;
            $d_shadow = '7px 8px 0 0 rgba(42,42,45,0.5)';
            $d_img = 'none';
            $d_overlay = 'transparent';
        }

        // Overrides
        $brand = $s('login_brand_color') ?: $d_brand;
        $alt = $s('login_alt_color') ?: $d_alt;
        $bg_color = $s('login_bg_color') ?: $d_bg;
        $bg_image = $s('login_bg_image') ? 'url('.$s('login_bg_image').')' : $d_img;
        $overlay = $this->rgba($s('login_bg_overlay'), $s('login_bg_opacity')) ?: $d_overlay;
        
        $form_bg = $this->rgba($s('login_form_bg'), $s('login_form_opacity')) ?: ($s('login_form_bg') ? $this->rgba($s('login_form_bg'), 100) : $this->rgba($d_form_bg, $d_form_op));
        $blur = ($b = $s('login_form_blur')) !== '' ? $b.'px' : $d_blur.'px';
        $radius = ($r = $s('login_border_radius')) !== '' ? $r.'px' : $d_radius.'px';
        $border_col = $s('login_form_border') ?: $d_border;
        $border_w = ($bw = $s('login_form_border_width')) !== '' ? $bw.'px' : $d_border_w.'px';

        // Logo
        $logo = $s('login_logo') ?: 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="#ccc"/></svg>');
        $logo_w = ($w = $s('login_logo_width')) ? $w.'px' : '270px';
        $logo_h = ($h = $s('login_logo_height')) ? $h.'px' : '180px';

        // 2. CSS BLOCK (Applied to BOTH .login AND .asc-login to ensure exact match)
        // I am using the exact Flexbox/Layout rules you provided.
        $css = "
        :root {
            --asc-main: $brand;
            --asc-alt: $alt;
            --asc-bg: $bg_color;
            --asc-bg-img: $bg_image;
            --asc-overlay: $overlay;
            --asc-form-bg: $form_bg;
            --asc-border-c: $border_col;
            --asc-border-w: $border_w;
            --asc-radius: $radius;
            --asc-blur: $blur;
            --asc-shadow: $d_shadow;
            --asc-text: $d_text;
            --asc-logo: url('$logo');
            --asc-logo-w: $logo_w;
            --asc-logo-h: $logo_h;
            --asc-font: 'Space Grotesk', sans-serif;
        }

        /* LAYOUT & BACKGROUND */
        body.login, body.asc-login {
            background-color: var(--asc-bg);
            background-image: var(--asc-bg-img);
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            font-family: var(--asc-font);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            padding: 0;
            position: relative;
        }
        body.login:before, body.asc-login:before {
            content: ''; position: absolute; inset: 0; background: var(--asc-overlay); z-index: -1;
        }

        /* CONTAINER */
        #login, .asc-login {
            width: clamp(250px, 90vw, 370px);
            padding: 0;
            margin: auto;
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            align-content: center;
            justify-content: center;
            align-items: center;
            gap: 10px;
            z-index: 10;
            position: relative;
        }

        /* LOGO */
        .login h1, .asc-login h1.asc-wp-login-logo {
            width: 100%; display: flex; justify-content: center; position: relative;
        }
        .login h1 a, .asc-login h1.asc-wp-login-logo a {
            background-image: var(--asc-logo) !important;
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
            width: var(--asc-logo-w);
            height: var(--asc-logo-h);
            margin-bottom: 20px;
            pointer-events: none;
            display: block;
            text-indent: -9999px;
            overflow: visible;
        }
        
        /* BADGES (Optional) */
        .login h1 a:before, .asc-login h1.asc-wp-login-logo a:before {
            content: \"{$s('login_badge_text')}\";
            display: " . ($s('login_badge_text') ? 'flex' : 'none') . ";
            position: absolute; bottom: -30px; left: 50%; transform: translateX(-50%);
            background: rgba(255,255,255,0.1); padding: 5px 10px; border-radius: 20px;
            color: var(--asc-main); font-size: 12px; font-weight: bold; white-space: nowrap;
            backdrop-filter: blur(5px);
        }

        /* FORM BOX */
        .login form, .asc-login #asc-loginform {
            margin-top: 20px;
            padding: 30px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: stretch;
            gap: 10px;
            background: var(--asc-form-bg);
            border: var(--asc-border-w) solid var(--asc-border-c);
            border-radius: var(--asc-radius);
            box-shadow: var(--asc-shadow);
            backdrop-filter: blur(var(--asc-blur));
            -webkit-backdrop-filter: blur(var(--asc-blur));
            overflow: hidden;
            width: 100%;
        }

        /* INPUTS */
        .login label, .asc-login .asc-label {
            color: var(--asc-main);
            font-size: 13px; font-weight: 600; text-transform: uppercase;
            margin-bottom: 5px; display: block;
        }
        .login input[type='text'], .login input[type='password'], .asc-login .asc-input {
            background: rgba(255,255,255,0.1);
            border: var(--asc-border-w) solid var(--asc-border-c);
            border-radius: var(--asc-radius);
            color: var(--asc-text);
            padding: 12px; width: 100%; font-size: 16px;
            margin-bottom: 0;
            box-shadow: none;
        }
        .login input:focus, .asc-login .asc-input:focus {
            border-color: var(--asc-main);
            box-shadow: 0 0 0 1px var(--asc-main);
            outline: none;
        }

        /* BUTTONS */
        .wp-core-ui .button-primary, .asc-login .asc-button-primary {
            background: linear-gradient(to bottom right, var(--asc-main), var(--asc-alt));
            border: var(--asc-border-w) solid #fff;
            border-radius: var(--asc-radius);
            color: #000; font-weight: bold; text-transform: uppercase;
            padding: 12px; width: 100%; height: auto; min-height: 45px;
            text-shadow: none; box-shadow: none; cursor: pointer;
            margin-top: 10px;
        }
        .wp-core-ui .button-primary:hover, .asc-login .asc-button-primary:hover {
            filter: brightness(1.1);
            box-shadow: inset 0 0 0 50px rgba(255,255,255,0.1);
        }

        /* EXTRAS */
        .login #nav, .login #backtoblog, .asc-login #asc-nav, .asc-login #asc-backtoblog {
            margin: 10px 0 0; text-align: center; padding: 0;
        }
        .login #nav a, .login #backtoblog a, .asc-login #asc-nav a, .asc-login #asc-backtoblog a {
            color: var(--asc-text) !important;
            text-decoration: none; font-size: 12px;
            padding: 5px;
        }
        
        /* HIDE PUM OVERLAY */
        .asc-login .pum-overlay { display: none !important; }
        ";

        echo "<style id='asc-login-gen'>$css</style>";
        
        if($custom = $this->get('login_custom_css')) echo "<style id='asc-login-custom'>$custom</style>";
    }

    // --- RENDER MOCKUP (With Exact Classes from your file) ---
    public function render_login_mock_page() {
        if (isset($_GET['asc_login_preview']) && $_GET['asc_login_preview'] == 1) {
            if (!current_user_can('manage_options')) wp_die('Unauthorized');
            $this->clean_mock_assets();
            ?>
            <!DOCTYPE html>
            <html lang="en-US">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>ASC Login Preview</title>
                <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
                <?php wp_head(); ?>
            </head>
            <body class="asc-wp-core-ui asc-login">
                <div class="asc-login" id="asc-login">
                    <h1 class="asc-wp-login-logo">
                        <a href="#" title="WordPress Demo Site" tabindex="-1">WordPress Demo Site</a>
                    </h1>

                    <form name="asc-loginform" id="asc-loginform" action="#" method="post">
                        <div class="asc-form-group">
                            <label for="asc-user-login" class="asc-label">Username or Email</label>
                            <input type="text" name="log" id="asc-user-login" class="asc-input" value="" size="20">
                        </div>

                        <div class="asc-form-group" style="margin-top:15px;">
                            <label for="asc-user-pass" class="asc-label">Password</label>
                            <input type="password" name="pwd" id="asc-user-pass" class="asc-input" value="" size="20">
                        </div>

                        <div class="asc-forgetmenot" style="margin:15px 0; display:flex; align-items:center;">
                            <input name="rememberme" type="checkbox" id="asc-rememberme" value="forever" style="margin-right:5px;">
                            <label for="asc-rememberme" class="asc-label" style="margin:0; font-weight:400; text-transform:none;">Remember Me</label>
                        </div>

                        <p class="asc-submit">
                            <button type="submit" name="wp-submit" id="asc-wp-submit" class="asc-button-primary">Log In</button>
                        </p>
                    </form>

                    <p id="asc-nav" style="text-align:center; margin-top:20px;"><a href="#">Lost your password?</a></p>
                    <p id="asc-backtoblog" style="text-align:center;"><a href="#">← Back to WordPress Demo Site</a></p>
                </div>
                <?php wp_footer(); ?>
            </body>
            </html>
            <?php exit;
        }
    }

    private function clean_mock_assets() {
        add_filter('show_admin_bar', '__return_false');
        add_action('wp_enqueue_scripts', function() {
            global $wp_scripts, $wp_styles;
            $allowed = ['jquery', 'jquery-core', 'jquery-migrate', 'customize-preview', 'asc-fonts'];
            foreach ($wp_scripts->queue as $h) { if (!in_array($h, $allowed)) { wp_dequeue_script($h); wp_deregister_script($h); } }
            foreach ($wp_styles->queue as $h) { if($h !== 'asc-fonts') { wp_dequeue_style($h); wp_deregister_style($h); } }
            wp_enqueue_script('jquery');
            wp_enqueue_script('customize-preview');
        }, 9999);
    }

    // Ajax
    public function ajax_export() { check_ajax_referer('asc_nonce', 'nonce'); wp_send_json_success(['settings' => $this->get_all_mods()]); }
    public function ajax_import() { check_ajax_referer('asc_nonce', 'nonce'); $json = stripslashes($_POST['data']); $arr = json_decode($json, true); foreach($arr['settings'] as $k=>$v) set_theme_mod('asc_'.$k, $v); wp_send_json_success(); }
    public function ajax_reset() { check_ajax_referer('asc_nonce', 'nonce'); foreach(array_keys($this->defaults) as $k) remove_theme_mod('asc_'.$k); wp_send_json_success(); }
    private function get_all_mods() { $d=[]; foreach(array_keys($this->defaults) as $k) $d[$k] = $this->get($k); return $d; }
}
ASC_Plugin::get_instance();