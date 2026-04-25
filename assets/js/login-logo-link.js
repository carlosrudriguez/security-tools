(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        var logoLink = document.querySelector('#login h1 a, .login h1 a');

        if (!logoLink) {
            return;
        }

        logoLink.setAttribute('target', '_blank');
        logoLink.setAttribute('rel', 'noopener noreferrer');
    });
})();
