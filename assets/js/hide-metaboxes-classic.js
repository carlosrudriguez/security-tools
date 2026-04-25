(function() {
    'use strict';

    var config = window.securityToolsHiddenMetaboxes || {};
    var hiddenMetaboxes = Array.isArray(config.ids) ? config.ids : [];

    function findLabelFor(id) {
        var labels = document.getElementsByTagName('label');

        for (var i = 0; i < labels.length; i++) {
            if (labels[i].getAttribute('for') === id + '-hide') {
                return labels[i];
            }
        }

        return null;
    }

    function hideMetaboxes() {
        hiddenMetaboxes.forEach(function(id) {
            var metabox = document.getElementById(id);
            if (metabox) {
                metabox.style.display = 'none';
            }

            var metaboxDiv = document.getElementById(id + 'div');
            if (metaboxDiv) {
                metaboxDiv.style.display = 'none';
            }

            var checkbox = document.getElementById(id + '-hide');
            if (checkbox) {
                checkbox.style.display = 'none';
            }

            var label = findLabelFor(id);
            if (label) {
                label.style.display = 'none';
            }
        });
    }

    hideMetaboxes();
    setTimeout(hideMetaboxes, 500);
    setTimeout(hideMetaboxes, 1500);
    setTimeout(hideMetaboxes, 3000);

    if (typeof MutationObserver === 'undefined') {
        return;
    }

    var postBody = document.getElementById('post-body');
    if (!postBody) {
        return;
    }

    var observer = new MutationObserver(function() {
        hideMetaboxes();
    });

    observer.observe(postBody, { childList: true, subtree: true });

    setTimeout(function() {
        observer.disconnect();
    }, 10000);
})();
