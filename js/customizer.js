(function($, api) {
    $(document).ready(function() {
        $(document).on('click', '#asc-btn-preview-login', function(e) {
            e.preventDefault(); api.previewer.previewUrl(ASC.mockUrl);
        });
        $(document).on('click', '#asc-btn-preview-home', function(e) {
            e.preventDefault(); api.previewer.previewUrl(ASC.homeUrl);
        });
        
        // Import/Export handlers here (Standard)
        $(document).on('click', '#asc-btn-reset', function(e){ 
            if(confirm('Reset all?')) $.post(ASC.ajax, {action:'asc_reset', nonce:ASC.nonce}, function(){ location.reload(); }); 
        });
    });
})(jQuery, wp.customize);