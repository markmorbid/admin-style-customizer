<?php
/**
 * Plugin Name: Admin Style Customizer
 * Description: Professional Login & Admin Bar Customizer (Dual Template Engine).
 * Version: 5.5.0
 * Author: Satoshisea
 */

if (!defined('ABSPATH')) exit;

define('ASC_VERSION', '5.5.0');
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
            'login_template' => 'flat', // 'flat' or 'glass'
            
            // Branding
            'login_logo' => '',
            'login_logo_width' => 180,
            
            // Colors & Backgrounds
            'login_brand_color' => '#f7941a',
            'login_alt_color' => '#2e9dd1',
            'login_bg_color' => '#f7941a',
            'login_bg_image' => '',
            'login_bg_overlay' => '#000000',
            'login_bg_opacity' => 0,
            
            // Form Container
            'login_form_bg' => '#ffffff',
            'login_form_opacity' => 100,
            'login_border_color' => '#2a2a2d',
            'login_border_width' => 2,
            'login_border_radius' => 0,
            
            // Text
            'login_text_color' => '#333333',
            'login_input_bg' => '#ffffff',
            
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
        
        // Outputs
        add_action('wp_head', [$this, 'output_adminbar_styles'], 999);
        add_action('admin_head', [$this, 'output_admin_styles'], 999);
        add_action('login_head', [$this, 'output_login_styles'], 999);
        add_action('wp_head', [$this, 'output_login_styles'], 999); // Inject into Mock
        add_action('login_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Logic
        add_action('wp_before_admin_bar_render', [$this, 'hide_adminbar_items'], 999);
        add_action('template_redirect', [$this, 'render_login_mock_page']);

        // AJAX
        add_action('wp_ajax_asc_export', [$this, 'ajax_export']);
        add_action('wp_ajax_asc_import', [$this, 'ajax_import']);
        add_action('wp_ajax_asc_reset', [$this, 'ajax_reset']);
    }

    public function get($key) {
        return get_theme_mod('asc_' . $key, $this->defaults[$key] ?? '');
    }

    public function enqueue_assets() {
        wp_enqueue_style('asc-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Space+Grotesk:wght@300;500;700&display=swap');
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
            'choices' => ['flat' => 'Orange Flat (Solid)', 'glass' => 'Dark Glass (Translucent)']
        ]);

        $this->add_image($wp_customize, 'asc_sec_login', 'login_logo', 'Logo Image');
        $this->add_num($wp_customize, 'asc_sec_login', 'login_logo_width', 'Logo Width (px)');

        // 3. Colors
        $this->add_section($wp_customize, 'asc_sec_login_styles', 'Login - Colors & Styles');
        $this->add_color($wp_customize, 'asc_sec_login_styles', 'login_brand_color', 'Primary Brand Color');
        $this->add_color($wp_customize, 'asc_sec_login_styles', 'login_alt_color', 'Secondary Accent');
        $this->add_color($wp_customize, 'asc_sec_login_styles', 'login_bg_color', 'Background Color');
        $this->add_image($wp_customize, 'asc_sec_login_styles', 'login_bg_image', 'Background Image');
        $this->add_color($wp_customize, 'asc_sec_login_styles', 'login_bg_overlay', 'Overlay Color');
        $this->add_range($wp_customize, 'asc_sec_login_styles', 'login_bg_opacity', 'Overlay Opacity');
        
        $this->add_color($wp_customize, 'asc_sec_login_styles', 'login_form_bg', 'Form Background');
        $this->add_range($wp_customize, 'asc_sec_login_styles', 'login_form_opacity', 'Form Opacity');
        
        $this->add_color($wp_customize, 'asc_sec_login_styles', 'login_border_color', 'Border Color');
        $this->add_num($wp_customize, 'asc_sec_login_styles', 'login_border_width', 'Border Width (px)');
        $this->add_num($wp_customize, 'asc_sec_login_styles', 'login_border_radius', 'Border Radius (px)');
        
        $this->add_color($wp_customize, 'asc_sec_login_styles', 'login_text_color', 'Text/Label Color');
        $this->add_color($wp_customize, 'asc_sec_login_styles', 'login_input_bg', 'Input Background');

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
        $wp->add_setting('asc_'.$id, ['default'=>$this->defaults[$id]??'', 'transport'=>'refresh', 'sanitize_callback'=>'sanitize_text_field']);
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

    // --- THE CSS GENERATOR (DUAL SELECTOR + TEMPLATE SWITCHER) ---
    public function output_login_styles() {
        if(!$this->get('login_custom_enable')) return;

        $s = function($k){ return $this->get($k); };
        $tpl = $s('login_template');

        // 1. Template Defaults & Smart Variables
        if ($tpl === 'glass') {
            // Dark Glass Defaults (Degent Style)
            $d_brand = '#2efc86'; $d_alt = '#238DCE'; $d_bg = '#080808'; $d_text = '#cccccc';
            $d_form_bg = '#141414'; $d_form_op = 60; $d_border = '#2a2a2d'; $d_border_w = 1; $d_radius = 8;
            $d_img = 'url("data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' version=\'1.1\' width=\'1440\' height=\'560\' viewBox=\'0 0 1440 560\'%3e%3crect width=\'1440\' height=\'560\' fill=\'black\'/%3e%3c/svg%3e")';
            $d_overlay = 'rgba(0,0,0,0.7)';
            
            $css_rules = $this->get_glass_css(); // Load Specific Glass Rules
        } else {
            // Flat Orange Defaults (Hashpower Style)
            $d_brand = '#f7941a'; $d_alt = '#2e9dd1'; $d_bg = '#f7941a'; $d_text = '#333333';
            $d_form_bg = '#ffffff'; $d_form_op = 100; $d_border = '#2a2a2d'; $d_border_w = 2; $d_radius = 0;
            $d_img = 'none'; $d_overlay = 'transparent';
            
            $css_rules = $this->get_flat_css(); // Load Specific Flat Rules
        }

        // User Overrides
        $brand = $s('login_brand_color') ?: $d_brand;
        $alt = $s('login_alt_color') ?: $d_alt;
        $bg_color = $s('login_bg_color') ?: $d_bg;
        $bg_img = $s('login_bg_image') ? 'url('.$s('login_bg_image').')' : $d_img;
        $overlay = $this->rgba($s('login_bg_overlay'), $s('login_bg_opacity')) ?: $d_overlay;
        
        $form_bg_raw = $s('login_form_bg') ?: $d_form_bg;
        $form_op = ($o = $s('login_form_opacity')) !== '' ? $o : $d_form_op;
        $form_bg_final = $this->rgba($form_bg_raw, $form_op);
        
        $border_c = $s('login_border_color') ?: $d_border;
        $border_w = ($bw = $s('login_border_width')) !== '' ? $bw.'px' : $d_border_w.'px';
        $radius = ($r = $s('login_border_radius')) !== '' ? $r.'px' : $d_radius.'px';
        
        $text_c = $s('login_text_color') ?: $d_text;
        $input_bg = $s('login_input_bg') ?: '#ffffff';

        // Logo
        $logo_url = $s('login_logo') ?: 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="#ccc"/></svg>');
        $logo_w = ($w = $s('login_logo_width')) ? $w.'px' : '250px';

        // 2. VARS
        $vars = "
            --asc-brand: $brand;
            --asc-alt: $alt;
            --asc-bg-color: $bg_color;
            --asc-bg-image: $bg_img;
            --asc-overlay: $overlay;
            --asc-form-bg: $form_bg_final;
            --asc-border-c: $border_c;
            --asc-border-w: $border_w;
            --asc-radius: $radius;
            --asc-text: $text_c;
            --asc-input-bg: $input_bg;
            --asc-logo: url('$logo_url');
            --asc-logo-w: $logo_w;
            --asc-font: 'Space Grotesk', sans-serif;
        ";

        // 3. RENDER
        echo "<style id='asc-login-vars'>:root{{$vars}}</style>";
        echo "<style id='asc-login-core'>{$css_rules}</style>";
        
        // 4. CUSTOM CSS
        if($custom = $this->get('login_custom_css')) echo "<style id='asc-login-custom'>$custom</style>";
    }

    // --- CSS TEMPLATES (Dual Selectors) ---

    private function get_flat_css() {
        return "
        /* HASHPOWER FLAT LAYOUT */
        /* Background */
        body.login, body.asc-login {
            font-family: var(--asc-font);
            background: var(--asc-bg-color);
            display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0;
        }
        
        /* Logo */
        .login h1, .asc-login h1.asc-wp-login-logo { width: 100%; text-align: center; margin-bottom: 15px; }
        .login h1 a, .asc-login h1.asc-wp-login-logo a {
            background-image: var(--asc-logo) !important;
            width: var(--asc-logo-w) !important; height: 150px !important;
            background-size: contain !important;
            background-position: center !important;
            margin: 0 auto !important;
            display: block; pointer-events: none;
        }

        /* Form */
        .login form, .asc-login #asc-loginform {
            background: var(--asc-form-bg);
            border: var(--asc-border-w) solid var(--asc-border-c);
            padding: 26px 24px 34px;
            box-shadow: 7px 8px 0 0 rgba(42,42,45,0.5);
            margin-top: 20px;
            border-radius: var(--asc-radius);
        }

        /* Label */
        .login label, .asc-login .asc-label {
            font-size: 14px; line-height: 1.5; margin-bottom: 3px; display: block;
            color: var(--asc-text); font-weight: 600;
        }

        /* Inputs */
        .login input[type='text'], .login input[type='password'], .asc-login .asc-input {
            border-radius: var(--asc-radius);
            border: var(--asc-border-w) solid var(--asc-border-c);
            font-size: 15px; padding: 7px 10px; margin: 0 0 7px 0;
            background: var(--asc-input-bg); color: var(--asc-text);
            min-height: 48px; width: 100%;
        }
        .login input:focus, .asc-login .asc-input:focus {
            border-color: var(--asc-brand);
            box-shadow: 0 0 0 1px var(--asc-brand);
            outline: 2px solid transparent;
        }

        /* Button */
        .wp-core-ui .button-primary, .asc-login .asc-button-primary {
            background: var(--asc-brand);
            border: var(--asc-border-w) solid var(--asc-border-c);
            border-radius: var(--asc-radius);
            color: var(--asc-border-c); /* Text color matches border in Flat theme */
            font-size: 18px; font-weight: bold; text-transform: uppercase;
            padding: 10px; width: 100%; height: auto;
            box-shadow: inset 0 0 0 0 var(--asc-brand), 5px 5px 0 0 rgba(0,0,0,0.25);
            transition: all 0.3s;
        }
        .wp-core-ui .button-primary:hover, .asc-login .asc-button-primary:hover {
            color: var(--asc-brand) !important;
            box-shadow: inset 300px 0 0 0 #fff, 5px 5px 0 0 rgba(0,0,0,0.45);
        }

        /* Links */
        .login #nav a, .login #backtoblog a, .asc-login #asc-nav a, .asc-login #asc-backtoblog a {
            color: var(--asc-border-c) !important;
            background: #fff;
            border: 2px solid var(--asc-border-c);
            border-radius: var(--asc-radius);
            padding: 10px 11px; font-weight: 700; font-size: 11px; text-transform: uppercase;
            text-decoration: none; display: inline-block;
            box-shadow: 5px 5px 0 0 rgba(0,0,0,0.25);
        }
        
        /* Popup Fix */
        .pum-overlay { display: none !important; }
        ";
    }

    private function get_glass_css() {
        return "
        /* DEGENT GLASS LAYOUT */
        /* Background */
        body.login, body.asc-login {
            background-color: var(--asc-bg-color);
            background-image: var(--asc-bg-image);
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            font-family: var(--asc-font);
            height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; position: relative;
        }
        body.login::before, body.asc-login::before {
            content: ''; position: absolute; inset: 0; background: var(--asc-overlay); z-index: -1;
        }

        /* Logo */
        .login h1 a, .asc-login h1.asc-wp-login-logo a {
            background-image: var(--asc-logo) !important;
            background-size: contain !important;
            width: var(--asc-logo-w) !important; height: 150px !important;
            margin: 0 auto 15px !important;
            display: block; pointer-events: none;
        }

        /* Form */
        .login form, .asc-login #asc-loginform {
            background: var(--asc-form-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: var(--asc-border-w) solid var(--asc-border-c);
            border-radius: var(--asc-radius);
            padding: 30px; margin: 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        /* Labels */
        .login label, .asc-login .asc-label {
            font-size: 14px; line-height: 1.5; margin-bottom: 5px; display: inline-flex; gap: 8px;
            color: var(--asc-brand) !important;
            font-weight: 500; text-transform: none;
        }

        /* Inputs */
        .login input[type='text'], .login input[type='password'], .asc-login .asc-input {
            background: rgba(0,0,0,0.3);
            border: var(--asc-border-w) solid #333;
            border-radius: var(--asc-radius);
            padding: 14px 10px; font-size: 14px; color: #fff; width: 100%;
            margin: 0 0 15px 0;
        }
        .login input:focus, .asc-login .asc-input:focus {
            border-color: var(--asc-brand);
            box-shadow: 0 0 0 1px var(--asc-brand);
            outline: none;
        }

        /* Button */
        .wp-core-ui .button-primary, .asc-login .asc-button-primary {
            background: linear-gradient(to bottom right, var(--asc-brand), var(--asc-alt));
            border: var(--asc-border-w) solid #fff;
            border-radius: var(--asc-radius);
            color: #000; font-size: 18px; font-weight: bold; text-transform: uppercase;
            padding: 0 2rem; width: 100%; height: auto; min-height: 45px; line-height: 2.3;
            text-shadow: none; box-shadow: none; cursor: pointer;
        }
        .wp-core-ui .button-primary:hover, .asc-login .asc-button-primary:hover {
            filter: brightness(1.1);
            box-shadow: inset 500px 0 0 0 rgba(255,255,255,0.1);
        }

        /* Links */
        .login #nav a, .login #backtoblog a, .asc-login #asc-nav a, .asc-login #asc-backtoblog a {
            color: var(--asc-brand) !important;
            text-decoration: none; font-weight: 500; font-size: 14px;
            padding: 7px; border-radius: var(--asc-radius);
            transition: all 300ms;
        }
        .login #nav a:hover, .asc-login #asc-nav a:hover {
            background: var(--asc-brand); color: #000 !important;
        }
        
        .pum-overlay { display: none !important; }
        ";
    }

    // --- MOCKUP PAGE RENDERER ---
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
                <style>.asc-login-wrapper { display: flex; justify-content: center; align-items: center; min-height: 100vh; }</style>
            </head>
            <body class="asc-wp-core-ui asc-login">
                <div class="asc-login-wrapper">
                    <div class="asc-login" id="asc-login">
                        <h1 class="asc-wp-login-logo">
                            <a href="#" title="WordPress Demo Site">WordPress Demo Site</a>
                        </h1>

                        <form name="asc-loginform" id="asc-loginform" action="#" method="post">
                            <div class="asc-form-group">
                                <label for="asc-user-login" class="asc-label">Username or Email Address</label>
                                <input type="text" name="log" id="asc-user-login" class="asc-input" value="" size="20">
                            </div>

                            <div class="asc-form-group">
                                <label for="asc-user-pass" class="asc-label">Password</label>
                                <input type="password" name="pwd" id="asc-user-pass" class="asc-input" value="" size="20">
                            </div>

                            <div class="asc-forgetmenot" style="margin:15px 0; display:flex; align-items:center;">
                                <input name="rememberme" type="checkbox" id="asc-rememberme" value="forever" style="margin-right:5px;">
                                <label for="asc-rememberme" class="asc-label" style="margin:0; font-weight:400; text-transform:none; display:inline;">Remember Me</label>
                            </div>

                            <p class="asc-submit">
                                <button type="submit" name="wp-submit" id="asc-wp-submit" class="asc-button-primary">Log In</button>
                            </p>
                        </form>

                        <p id="asc-nav" style="text-align:center; margin-top:20px;"><a href="#">Lost your password?</a></p>
                        <p id="asc-backtoblog" style="text-align:center;"><a href="#">← Back to WordPress Demo Site</a></p>
                    </div>
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
