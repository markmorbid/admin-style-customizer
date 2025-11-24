(function($, api) {
    'use strict';

    // This runs inside the preview iframe
    // Since we use 'refresh' transport, this is mainly for future live preview support

    api.bind('preview-ready', function() {
        // Preview is loaded
        console.log('ASC: Preview ready');

        // Helper: Convert Hex + Opacity(0-100) to RGBA
        function hexToRgba(hex, opacity) {
            if (!hex) return 'transparent';
            hex = hex.replace('#', '');
            var r = parseInt(hex.substring(0, 2), 16);
            var g = parseInt(hex.substring(2, 4), 16);
            var b = parseInt(hex.substring(4, 6), 16);
            return 'rgba(' + r + ',' + g + ',' + b + ',' + (opacity / 100) + ')';
        }

        // Generic CSS Var Binder
        function bindRaw(setting, cssVar, suffix) {
            wp.customize('asc_' + setting, function(val) {
                val.bind(function(to) {
                    var final = suffix ? to + suffix : to;
                    document.documentElement.style.setProperty('--asc-' + cssVar, final);
                });
            });
        }

        // Live update functions
        var updateOverlay = function() {
            var c = wp.customize('asc_bg_overlay_color').get();
            var o = wp.customize('asc_bg_overlay_opacity').get();
            document.documentElement.style.setProperty('--asc-bg-overlay', hexToRgba(c, o));
        };
        wp.customize('asc_bg_overlay_color', v => v.bind(updateOverlay));
        wp.customize('asc_bg_overlay_opacity', v => v.bind(updateOverlay));

        var updateFormBg = function() {
            var c = wp.customize('asc_form_bg_color').get();
            var o = wp.customize('asc_form_bg_opacity').get();
            document.documentElement.style.setProperty('--asc-form-bg', hexToRgba(c, o));
        };
        wp.customize('asc_form_bg_color', v => v.bind(updateFormBg));
        wp.customize('asc_form_bg_opacity', v => v.bind(updateFormBg));
        
        var updateInputBg = function() {
            var c = wp.customize('asc_input_bg_color').get();
            var o = wp.customize('asc_input_bg_opacity').get();
            document.documentElement.style.setProperty('--asc-input-bg', hexToRgba(c, o));
        };
        wp.customize('asc_input_bg_color', v => v.bind(updateInputBg));
        wp.customize('asc_input_bg_opacity', v => v.bind(updateInputBg));

        var updateShadow = function() {
            var x = wp.customize('asc_shadow_x').get();
            var y = wp.customize('asc_shadow_y').get();
            var b = wp.customize('asc_shadow_blur').get();
            var s = wp.customize('asc_shadow_spread').get();
            var c = wp.customize('asc_shadow_color').get();
            var o = wp.customize('asc_shadow_opacity').get();
            var val = `${x}px ${y}px ${b}px ${s}px ${hexToRgba(c, o)}`;
            document.documentElement.style.setProperty('--asc-shadow', val);
        };
        wp.customize('asc_shadow_x', v => v.bind(updateShadow));
        wp.customize('asc_shadow_y', v => v.bind(updateShadow));
        wp.customize('asc_shadow_blur', v => v.bind(updateShadow));
        wp.customize('asc_shadow_spread', v => v.bind(updateShadow));
        wp.customize('asc_shadow_color', v => v.bind(updateShadow));
        wp.customize('asc_shadow_opacity', v => v.bind(updateShadow));

        var updateBtn = function() {
            var style = wp.customize('asc_btn_style').get();
            var bg;
            if(style === 'flat') {
                bg = wp.customize('asc_btn_flat_color').get();
            } else {
                var s = wp.customize('asc_btn_grad_start').get();
                var e = wp.customize('asc_btn_grad_end').get();
                bg = `linear-gradient(to right, ${s}, ${e})`;
            }
            document.documentElement.style.setProperty('--asc-btn-bg', bg);
        };
        wp.customize('asc_btn_style', v => v.bind(updateBtn));
        wp.customize('asc_btn_flat_color', v => v.bind(updateBtn));
        wp.customize('asc_btn_grad_start', v => v.bind(updateBtn));
        wp.customize('asc_btn_grad_end', v => v.bind(updateBtn));

        // Simple Bindings
        bindRaw('bg_color', 'bg-color');
        bindRaw('logo_width', 'logo-width', 'px');
        bindRaw('logo_text_color', 'logo-text-color');
        bindRaw('form_blur', 'form-blur', 'px');
        bindRaw('form_radius', 'form-radius', 'px');
        bindRaw('form_border_color', 'form-border-color'); // Logic handled in css via border-color
        bindRaw('label_color', 'label-color');
        bindRaw('input_text_color', 'input-text');
        bindRaw('input_border_color', 'input-border-color'); // Logic handled in css
        bindRaw('input_focus_color', 'input-focus');
        bindRaw('btn_text_color', 'btn-text');
        bindRaw('btn_radius', 'btn-radius', 'px');

        // Images
        wp.customize('asc_bg_image', function(val) {
            val.bind(function(to) {
                document.documentElement.style.setProperty('--asc-bg-image', to ? 'url(' + to + ')' : 'none');
            });
        });
        wp.customize('asc_logo_image', function(val) {
            val.bind(function(to) {
                document.documentElement.style.setProperty('--asc-logo-url', to ? 'url(' + to + ')' : 'none');
            });
        });
    });

})(jQuery, wp.customize);



