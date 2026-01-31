/**
 * Security Tools - Admin Scripts
 *
 * JavaScript functionality for the Security Tools settings page.
 * Handles table sorting, select all functionality, and dynamic interactions.
 *
 * @package    Security_Tools
 * @subpackage Assets/JS
 * @version    2.5
 * @author     Carlos Rodríguez
 */

(function() {
    'use strict';

    /**
     * Initialize when DOM is ready
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all tables with sorting and select-all functionality
        initializeTable('administrators');
        initializeTable('plugins');
        initializeTable('themes');
        initializeTable('widgets');
        initializeTable('admin-bar');
        initializeTable('metaboxes');

        // Initialize Media Library uploader for login logo (Branding page)
        initializeLoginLogoUploader();

        // Initialize Admin Bar CSS ID tokenfield (Admin Bar page)
        initializeAdminBarCssTokenfield();

        // Initialize Metabox Scanner (Elements page)
        initializeMetaboxScanner();
    });

    /**
     * ==========================================================================
     * MEDIA LIBRARY UPLOADER FOR LOGIN LOGO
     * ==========================================================================
     * Handles the WordPress Media Library integration for selecting a custom
     * login page logo on the Branding settings page.
     * @since 2.3
     */

    /**
     * Initialize the login logo media uploader
     *
     * Sets up click handlers for the upload and remove buttons,
     * and manages the WordPress Media Library modal.
     *
     * @since 2.3
     */
    function initializeLoginLogoUploader() {
        var uploadButton = document.getElementById('upload-login-logo');
        var removeButton = document.getElementById('remove-login-logo');
        var logoIdInput = document.getElementById('login_logo_id');
        var previewContainer = document.getElementById('login-logo-preview');

        // Exit if elements don't exist (not on Branding page)
        if (!uploadButton || !logoIdInput) {
            return;
        }

        var mediaFrame = null;

        /**
         * Handle upload button click
         * Opens the WordPress Media Library modal
         */
        uploadButton.addEventListener('click', function(e) {
            e.preventDefault();

            // If the media frame already exists, reopen it
            if (mediaFrame) {
                mediaFrame.open();
                return;
            }

            // Create the media frame
            // Note: We don't filter by type to allow SVG files
            // SVG files have mime type 'image/svg+xml' which may not be
            // included in WordPress's default 'image' type filter
            mediaFrame = wp.media({
                title: securityToolsMedia.title || 'Select Login Logo',
                button: {
                    text: securityToolsMedia.button || 'Use as Login Logo'
                },
                library: {
                    type: ['image', 'image/svg+xml'] // Include SVG explicitly
                },
                multiple: false // Single image selection
            });

            // Handle image selection
            mediaFrame.on('select', function() {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                
                // Update the hidden input with attachment ID
                logoIdInput.value = attachment.id;

                // Get the best available URL for preview
                // SVG files may not have 'sizes' property, so use 'url' directly
                var previewUrl = attachment.url;
                if (attachment.sizes && attachment.sizes.medium) {
                    previewUrl = attachment.sizes.medium.url;
                } else if (attachment.sizes && attachment.sizes.full) {
                    previewUrl = attachment.sizes.full.url;
                }

                // Update the preview image
                updateLogoPreview(previewUrl, previewContainer);

                // Update button texts
                uploadButton.textContent = securityToolsMedia.changeButton || 'Change Logo';
                
                // Show remove button
                if (removeButton) {
                    removeButton.style.display = 'inline-block';
                }
            });

            // Open the modal
            mediaFrame.open();
        });

        /**
         * Handle remove button click
         * Clears the logo selection
         */
        if (removeButton) {
            removeButton.addEventListener('click', function(e) {
                e.preventDefault();

                // Clear the hidden input
                logoIdInput.value = '';

                // Hide and clear the preview
                if (previewContainer) {
                    previewContainer.style.display = 'none';
                    previewContainer.innerHTML = '';
                }

                // Update button text and hide remove button
                uploadButton.textContent = securityToolsMedia.selectButton || 'Select Logo';
                removeButton.style.display = 'none';
            });
        }
    }

    /**
     * Update the logo preview image
     *
     * @since 2.3
     * @param {string} imageUrl - The URL of the selected image
     * @param {HTMLElement} container - The preview container element
     */
    function updateLogoPreview(imageUrl, container) {
        if (!container) {
            return;
        }

        // Create or update the preview image
        var img = container.querySelector('img');
        if (!img) {
            img = document.createElement('img');
            img.alt = 'Login logo preview';
            img.style.maxWidth = '300px';
            img.style.height = 'auto';
            container.appendChild(img);
        }

        img.src = imageUrl;
        container.style.display = 'block';
    }

    /**
     * Initialize a table with sorting and select-all functionality
     *
     * @param {string} tableType - The data-table-type attribute value
     */
    function initializeTable(tableType) {
        var table = document.querySelector('[data-table-type="' + tableType + '"]');
        if (!table) {
            return;
        }

        initializeSelectAll(table);
        initializeSorting(table);
    }

    /**
     * Initialize select-all checkbox functionality for a table
     *
     * @param {HTMLElement} table - The table container element
     */
    function initializeSelectAll(table) {
        var selectAllCheckbox = table.querySelector('.select-all-checkbox');
        var rowCheckboxes = table.querySelectorAll('tbody input[type="checkbox"]');

        if (!selectAllCheckbox || rowCheckboxes.length === 0) {
            return;
        }

        // Handle select-all checkbox change
        selectAllCheckbox.addEventListener('change', function() {
            var isChecked = this.checked;
            rowCheckboxes.forEach(function(checkbox) {
                checkbox.checked = isChecked;
            });
        });

        // Update select-all state when individual checkboxes change
        rowCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                updateSelectAllState(table, selectAllCheckbox, rowCheckboxes);
            });
        });

        // Set initial state
        updateSelectAllState(table, selectAllCheckbox, rowCheckboxes);
    }

    /**
     * Update the select-all checkbox state based on row checkboxes
     *
     * @param {HTMLElement} table - The table container element
     * @param {HTMLElement} selectAllCheckbox - The select-all checkbox
     * @param {NodeList} rowCheckboxes - All row checkboxes
     */
    function updateSelectAllState(table, selectAllCheckbox, rowCheckboxes) {
        var checkedCount = table.querySelectorAll('tbody input[type="checkbox"]:checked').length;
        var totalCount = rowCheckboxes.length;

        selectAllCheckbox.checked = (checkedCount === totalCount);
        selectAllCheckbox.indeterminate = (checkedCount > 0 && checkedCount < totalCount);
    }

    /**
     * Initialize sorting functionality for a table
     *
     * @param {HTMLElement} table - The table container element
     */
    function initializeSorting(table) {
        var sortableHeaders = table.querySelectorAll('.sortable-header');

        sortableHeaders.forEach(function(header) {
            header.addEventListener('click', function() {
                sortTable(table, this, sortableHeaders);
            });
        });
    }

    /**
     * Sort a table by the clicked header column
     *
     * @param {HTMLElement} table - The table container element
     * @param {HTMLElement} clickedHeader - The clicked header element
     * @param {NodeList} allHeaders - All sortable headers
     */
    function sortTable(table, clickedHeader, allHeaders) {
        var currentSort = clickedHeader.dataset.sort || 'none';
        var tbody = table.querySelector('tbody');
        var rows = Array.from(tbody.querySelectorAll('tr'));
        var indicator = clickedHeader.querySelector('.sort-indicator');
        var columnIndex = clickedHeader.cellIndex;

        // Reset all headers
        allHeaders.forEach(function(h) {
            h.classList.remove('sorted', 'desc');
            h.dataset.sort = 'none';
            var otherIndicator = h.querySelector('.sort-indicator');
            if (otherIndicator) {
                otherIndicator.textContent = '⇅';
            }
        });

        // Determine new sort direction
        var newSort = 'asc';
        if (currentSort === 'asc') {
            newSort = 'desc';
            clickedHeader.classList.add('desc');
            indicator.textContent = '↓';
        } else {
            indicator.textContent = '↑';
        }

        clickedHeader.classList.add('sorted');
        clickedHeader.dataset.sort = newSort;

        // Sort rows
        rows.sort(function(a, b) {
            var aVal = getCellSortValue(a, columnIndex);
            var bVal = getCellSortValue(b, columnIndex);

            var comparison = aVal.localeCompare(bVal);
            return newSort === 'desc' ? -comparison : comparison;
        });

        // Reorder DOM
        rows.forEach(function(row) {
            tbody.appendChild(row);
        });
    }

    /**
     * Get the sortable value from a table cell
     *
     * @param {HTMLElement} row - The table row
     * @param {number} columnIndex - The column index
     * @return {string} The lowercase text value for sorting
     */
    function getCellSortValue(row, columnIndex) {
        var cell = row.querySelector('td:nth-child(' + (columnIndex + 1) + ')');
        if (!cell) {
            return '';
        }

        // Check for status badge (extract text from badge)
        var statusBadge = cell.querySelector('.status-badge');
        if (statusBadge) {
            return statusBadge.textContent.trim().toLowerCase();
        }

        return cell.textContent.trim().toLowerCase();
    }

    /**
     * ==========================================================================
     * ADMIN BAR CSS ID TOKENFIELD
     * ==========================================================================
     * Handles the tokenfield/chip input for CSS-based admin bar item hiding.
     * @since 2.4
     */

    /**
     * Initialize the Admin Bar CSS ID tokenfield
     *
     * Sets up event handlers for adding and removing tokens (CSS IDs)
     * for the CSS-based admin bar hiding feature.
     *
     * @since 2.4
     */
    function initializeAdminBarCssTokenfield() {
        var tokenContainer = document.getElementById('admin-bar-css-tokens');
        var hiddenInputsContainer = document.getElementById('admin-bar-css-hidden-inputs');
        var textInput = document.getElementById('admin-bar-css-input');
        var addButton = document.getElementById('admin-bar-css-add');

        // Exit if elements don't exist (not on Admin Bar page)
        if (!tokenContainer || !hiddenInputsContainer || !textInput || !addButton) {
            return;
        }

        /**
         * Sanitize a CSS ID
         * Strips leading '#' and removes invalid characters
         *
         * @param {string} cssId - Raw CSS ID input
         * @return {string} Sanitized CSS ID
         */
        function sanitizeCssId(cssId) {
            // Trim whitespace
            cssId = cssId.trim();

            // Strip leading '#' if present
            if (cssId.charAt(0) === '#') {
                cssId = cssId.substring(1);
            }

            // Remove invalid characters (only allow: a-z, A-Z, 0-9, hyphen, underscore)
            cssId = cssId.replace(/[^a-zA-Z0-9_-]/g, '');

            return cssId;
        }

        /**
         * Check if a token already exists
         *
         * @param {string} value - The token value to check
         * @return {boolean} True if token exists
         */
        function tokenExists(value) {
            var existingTokens = tokenContainer.querySelectorAll('.security-tools-token');
            for (var i = 0; i < existingTokens.length; i++) {
                if (existingTokens[i].getAttribute('data-value') === value) {
                    return true;
                }
            }
            return false;
        }

        /**
         * Add a new token
         *
         * @param {string} value - The CSS ID value to add
         */
        function addToken(value) {
            // Sanitize the value
            value = sanitizeCssId(value);

            // Validate
            if (!value) {
                return;
            }

            // Check for duplicates
            if (tokenExists(value)) {
                // Highlight existing token briefly
                var existingToken = tokenContainer.querySelector('[data-value="' + value + '"]');
                if (existingToken) {
                    existingToken.style.borderColor = '#dc3232';
                    setTimeout(function() {
                        existingToken.style.borderColor = '';
                    }, 500);
                }
                return;
            }

            // Create token element
            var token = document.createElement('span');
            token.className = 'security-tools-token';
            token.setAttribute('data-value', value);
            token.innerHTML = '<span class="token-text">' + escapeHtml(value) + '</span>' +
                              '<button type="button" class="token-remove" aria-label="Remove">&times;</button>';

            // Add remove handler
            token.querySelector('.token-remove').addEventListener('click', function() {
                removeToken(value);
            });

            // Add token to container
            tokenContainer.appendChild(token);

            // Create hidden input for form submission
            var hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'security_tools_hidden_admin_bar_css[]';
            hiddenInput.value = value;
            hiddenInput.setAttribute('data-token-value', value);
            hiddenInputsContainer.appendChild(hiddenInput);

            // Clear input
            textInput.value = '';
            textInput.focus();
        }

        /**
         * Remove a token
         *
         * @param {string} value - The CSS ID value to remove
         */
        function removeToken(value) {
            // Remove token element
            var token = tokenContainer.querySelector('[data-value="' + value + '"]');
            if (token) {
                token.remove();
            }

            // Remove hidden input
            var hiddenInput = hiddenInputsContainer.querySelector('[data-token-value="' + value + '"]');
            if (hiddenInput) {
                hiddenInput.remove();
            }
        }

        /**
         * Escape HTML to prevent XSS
         *
         * @param {string} text - Text to escape
         * @return {string} Escaped text
         */
        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Handle Add button click
        addButton.addEventListener('click', function(e) {
            e.preventDefault();
            addToken(textInput.value);
        });

        // Handle Enter key in input
        textInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addToken(textInput.value);
            }
        });

        // Setup remove handlers for existing tokens (loaded from server)
        var existingTokens = tokenContainer.querySelectorAll('.security-tools-token');
        existingTokens.forEach(function(token) {
            var removeBtn = token.querySelector('.token-remove');
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    var value = token.getAttribute('data-value');
                    removeToken(value);
                });
            }
        });
    }

    /**
     * ==========================================================================
     * METABOX SCANNER
     * ==========================================================================
     * Handles the manual metabox scanning functionality on the Elements page.
     * Loads post edit screens in a hidden iframe to trigger metabox discovery.
     * @since 2.5
     */

    /**
     * Initialize the metabox scanner
     *
     * Sets up the scan button click handler and manages the scanning process
     * by loading each post type's edit screen in sequence.
     *
     * @since 2.5
     */
    function initializeMetaboxScanner() {
        var scanButton = document.getElementById('security-tools-scan-metaboxes');
        var scanStatus = document.getElementById('security-tools-scan-status');
        var scanProgress = document.getElementById('security-tools-scan-progress');
        var progressFill = document.querySelector('.security-tools-progress-fill');
        var scanCurrent = document.getElementById('security-tools-scan-current');
        var scanTotal = document.getElementById('security-tools-scan-total');
        var scanFrame = document.getElementById('security-tools-scan-frame');

        // Exit if elements don't exist (not on Elements page)
        if (!scanButton || !scanFrame || typeof securityToolsScan === 'undefined') {
            return;
        }

        var isScanning = false;
        var currentIndex = 0;
        var postTypes = [];
        var totalTypes = 0;

        /**
         * Start the scanning process
         */
        function startScan() {
            if (isScanning) {
                return;
            }

            isScanning = true;
            scanButton.disabled = true;
            scanButton.textContent = securityToolsScan.strings.scanning;

            // Show progress bar
            if (scanProgress) {
                scanProgress.style.display = 'block';
            }

            // Start scan via AJAX (clears existing discovered metaboxes)
            var xhr = new XMLHttpRequest();
            xhr.open('POST', securityToolsScan.ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success && response.data.post_types) {
                                postTypes = response.data.post_types;
                                totalTypes = postTypes.length;
                                currentIndex = 0;

                                if (scanTotal) {
                                    scanTotal.textContent = totalTypes;
                                }

                                // Start scanning post types
                                scanNextPostType();
                            } else {
                                scanError();
                            }
                        } catch (e) {
                            scanError();
                        }
                    } else {
                        scanError();
                    }
                }
            };
            xhr.send(
                'action=security_tools_manual_scan' +
                '&nonce=' + encodeURIComponent(securityToolsScan.nonce) +
                '&scan_action=start'
            );
        }

        /**
         * Scan the next post type in the queue
         */
        function scanNextPostType() {
            if (currentIndex >= totalTypes) {
                // All done
                completeScan();
                return;
            }

            var postType = postTypes[currentIndex];

            // Update progress
            if (scanCurrent) {
                scanCurrent.textContent = currentIndex + 1;
            }
            if (progressFill) {
                var percent = ((currentIndex + 1) / totalTypes) * 100;
                progressFill.style.width = percent + '%';
            }
            if (scanStatus) {
                scanStatus.textContent = securityToolsScan.strings.scanning + ' ' + postType;
            }

            // Get the URL for this post type's edit screen
            var xhr = new XMLHttpRequest();
            xhr.open('POST', securityToolsScan.ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success && response.data.url) {
                                // Load the URL in the hidden iframe
                                loadFrameAndContinue(response.data.url);
                            } else {
                                // Skip this post type and continue
                                currentIndex++;
                                scanNextPostType();
                            }
                        } catch (e) {
                            // Skip and continue
                            currentIndex++;
                            scanNextPostType();
                        }
                    } else {
                        // Skip and continue
                        currentIndex++;
                        scanNextPostType();
                    }
                }
            };
            xhr.send(
                'action=security_tools_get_scan_url' +
                '&nonce=' + encodeURIComponent(securityToolsScan.nonce) +
                '&post_type=' + encodeURIComponent(postType)
            );
        }

        /**
         * Load a URL in the hidden iframe and continue after it loads
         *
         * @param {string} url - The URL to load
         */
        function loadFrameAndContinue(url) {
            // Set up load handler
            var loadHandler = function() {
                scanFrame.removeEventListener('load', loadHandler);
                
                // Wait a bit for the discovery script to run and send its AJAX
                setTimeout(function() {
                    currentIndex++;
                    scanNextPostType();
                }, 2000); // 2 second delay to allow discovery AJAX to complete
            };

            scanFrame.addEventListener('load', loadHandler);

            // Set a timeout in case the frame fails to load
            setTimeout(function() {
                // If we're still on the same index after 10 seconds, move on
                scanFrame.removeEventListener('load', loadHandler);
                if (currentIndex < totalTypes && isScanning) {
                    currentIndex++;
                    scanNextPostType();
                }
            }, 10000);

            // Load the URL
            scanFrame.src = url;
        }

        /**
         * Complete the scanning process
         */
        function completeScan() {
            // Notify server that scan is complete
            var xhr = new XMLHttpRequest();
            xhr.open('POST', securityToolsScan.ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    var discoveredCount = 0;
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success && response.data.count !== undefined) {
                                discoveredCount = response.data.count;
                            }
                        } catch (e) {
                            // Ignore parse errors
                        }
                    }

                    // Show completion message
                    if (scanStatus) {
                        var msg = securityToolsScan.strings.discovered.replace('%d', discoveredCount);
                        scanStatus.textContent = securityToolsScan.strings.complete + ' ' + msg;
                    }

                    // Update progress to 100%
                    if (progressFill) {
                        progressFill.style.width = '100%';
                    }

                    isScanning = false;

                    // Reload page after a short delay to show updated metabox list
                    setTimeout(function() {
                        if (scanStatus) {
                            scanStatus.textContent = securityToolsScan.strings.reloading;
                        }
                        window.location.reload();
                    }, 1500);
                }
            };
            xhr.send(
                'action=security_tools_manual_scan' +
                '&nonce=' + encodeURIComponent(securityToolsScan.nonce) +
                '&scan_action=complete'
            );
        }

        /**
         * Handle scan error
         */
        function scanError() {
            isScanning = false;
            scanButton.disabled = false;
            scanButton.innerHTML = '<span class="dashicons dashicons-search" style="margin-top: 3px;"></span> ' +
                                   securityToolsScan.strings.scanning.replace('...', '');
            
            if (scanStatus) {
                scanStatus.textContent = securityToolsScan.strings.error;
                scanStatus.style.color = '#dc3232';
            }

            if (scanProgress) {
                scanProgress.style.display = 'none';
            }
        }

        // Handle scan button click
        scanButton.addEventListener('click', function(e) {
            e.preventDefault();
            startScan();
        });
    }

})();
