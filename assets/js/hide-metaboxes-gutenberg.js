(function() {
    'use strict';

    var config = window.securityToolsGutenbergMetaboxes || {};
    var panels = Array.isArray(config.panels) ? config.panels : [];
    var metaboxes = Array.isArray(config.metaboxes) ? config.metaboxes : [];

    function hidePanels() {
        if (typeof wp === 'undefined' || !wp.data || !wp.data.dispatch) {
            return;
        }

        var dispatch = wp.data.dispatch('core/edit-post');
        if (!dispatch || !dispatch.removeEditorPanel) {
            return;
        }

        panels.forEach(function(panel) {
            try {
                dispatch.removeEditorPanel(panel);
            } catch (error) {
                // Ignore panels that are unavailable in the current editor state.
            }
        });
    }

    function hideMetaboxContainers() {
        metaboxes.forEach(function(id) {
            var element = document.getElementById(id);
            if (element) {
                element.style.display = 'none';
            }

            var elementDiv = document.getElementById(id + 'div');
            if (elementDiv) {
                elementDiv.style.display = 'none';
            }
        });
    }

    function hideAll() {
        hidePanels();
        hideMetaboxContainers();
    }

    if (typeof wp !== 'undefined' && wp.domReady) {
        wp.domReady(hideAll);
    }

    setTimeout(hideAll, 500);
    setTimeout(hideAll, 1500);
})();
