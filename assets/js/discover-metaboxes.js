(function() {
    'use strict';

    var config = window.securityToolsMetaboxDiscovery || {};
    var postType = config.postType || 'post';
    var ajaxurl = config.ajaxurl || window.ajaxurl;

    if (!ajaxurl || !config.nonce) {
        return;
    }

    function discoverClassicMetaboxes(metaboxes) {
        var postboxes = document.querySelectorAll('.postbox');

        postboxes.forEach(function(box) {
            var id = box.id;
            if (!id) {
                return;
            }

            var title = '';
            var handleElement = box.querySelector('.hndle');
            if (handleElement) {
                var titleSpan = handleElement.querySelector('span');
                title = titleSpan ? titleSpan.textContent.trim() : handleElement.textContent.trim();
            }

            var context = 'normal';
            var parent = box.parentElement;
            if (parent) {
                if (parent.id === 'side-sortables' || parent.id.indexOf('side') !== -1) {
                    context = 'side';
                } else if (parent.id === 'advanced-sortables' || parent.id.indexOf('advanced') !== -1) {
                    context = 'advanced';
                }
            }

            if (title) {
                metaboxes.push({
                    id: id,
                    title: title,
                    context: context,
                    post_type: postType
                });
            }
        });
    }

    function discoverGutenbergMetaboxes(metaboxes) {
        if (typeof wp === 'undefined' || !wp.data || !wp.data.select) {
            return;
        }

        try {
            var editPost = wp.data.select('core/edit-post');
            if (!editPost || !editPost.getMetaBoxesPerLocation) {
                return;
            }

            ['normal', 'side', 'advanced'].forEach(function(location) {
                var boxes = editPost.getMetaBoxesPerLocation(location);
                if (!boxes || !boxes.length) {
                    return;
                }

                boxes.forEach(function(box) {
                    var exists = metaboxes.some(function(metabox) {
                        return metabox.id === box.id;
                    });

                    if (!exists && box.id && box.title) {
                        metaboxes.push({
                            id: box.id,
                            title: box.title,
                            context: location,
                            post_type: postType
                        });
                    }
                });
            });
        } catch (error) {
            // Gutenberg API not available or not ready on this screen.
        }
    }

    function sendMetaboxes(metaboxes) {
        if (!metaboxes.length) {
            return;
        }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', ajaxurl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send(
            'action=security_tools_discover_metaboxes' +
            '&nonce=' + encodeURIComponent(config.nonce) +
            '&post_type=' + encodeURIComponent(postType) +
            '&scan_mode=' + encodeURIComponent(config.scanMode || '0') +
            '&metaboxes=' + encodeURIComponent(JSON.stringify(metaboxes))
        );
    }

    function discoverMetaboxes() {
        var metaboxes = [];

        discoverClassicMetaboxes(metaboxes);
        discoverGutenbergMetaboxes(metaboxes);
        sendMetaboxes(metaboxes);
    }

    if (document.readyState === 'complete') {
        setTimeout(discoverMetaboxes, 1000);
    } else {
        window.addEventListener('load', function() {
            setTimeout(discoverMetaboxes, 1000);
        });
    }

    if (typeof wp !== 'undefined' && wp.domReady) {
        wp.domReady(function() {
            setTimeout(discoverMetaboxes, 2000);
        });
    }
})();
