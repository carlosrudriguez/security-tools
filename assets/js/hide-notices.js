(function($) {
    'use strict';

    $(document).ready(function() {
        var isSettingsPage = !!(window.securityToolsHideNotices && window.securityToolsHideNotices.isSettingsPage);

        function hideNotices() {
            var notices = $('.notice, .error, .updated, .update-nag');

            if (isSettingsPage) {
                notices.not('.security-tools-notice').hide();
                return;
            }

            notices.hide();
        }

        hideNotices();

        if (typeof MutationObserver === 'undefined' || !document.body) {
            return;
        }

        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    hideNotices();
                }
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    });
})(jQuery);
