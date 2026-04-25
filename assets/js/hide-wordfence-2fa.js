(function($) {
    'use strict';

    $(document).ready(function() {
        $('.subsubsub li a').each(function() {
            var $item = $(this).closest('li');
            var href = $(this).attr('href') || '';

            if (
                $item.hasClass('wfls-active') ||
                $item.hasClass('wfls-inactive') ||
                href.indexOf('2fa-active') !== -1 ||
                href.indexOf('2fa-inactive') !== -1 ||
                href.indexOf('wf2fa=active') !== -1 ||
                href.indexOf('wf2fa=inactive') !== -1 ||
                href.indexOf('wf-2fa-active') !== -1 ||
                href.indexOf('wf-2fa-inactive') !== -1 ||
                href.indexOf('wfls-active') !== -1 ||
                href.indexOf('wfls-inactive') !== -1
            ) {
                $item.hide();
            }
        });
    });
})(jQuery);
