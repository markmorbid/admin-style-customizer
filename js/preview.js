(function($, api) {
    'use strict';

    // This runs inside the preview iframe
    // Since we use 'refresh' transport, this is mainly for future live preview support

    api.bind('preview-ready', function() {
        // Preview is loaded
        console.log('ASC: Preview ready');
    });

})(jQuery, wp.customize);
