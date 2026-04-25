(function($) {
    'use strict';

    $(document).ready(function() {
        function hideThemeMenus() {
            $('#menu-appearance .wp-submenu a').each(function() {
                var href = $(this).attr('href') || '';
                var text = $(this).text().toLowerCase();

                if (href.indexOf('widgets.php') !== -1) {
                    return;
                }

                if (
                    href.indexOf('customize.php') !== -1 ||
                    href.indexOf('site-editor.php') !== -1 ||
                    text.indexOf('customize') !== -1 ||
                    text.indexOf('editor') !== -1
                ) {
                    $(this).closest('li').hide();
                }
            });
        }

        hideThemeMenus();
        setTimeout(hideThemeMenus, 100);
    });
})(jQuery);
