/**
 * Entry Selector for Community Sharing
 * PHP-populated modal with client-side interactions
 *
 * @package Myavana_Hair_Journey
 * @version 2.0.0
 */

(function($) {
    'use strict';

    const EntrySelector = {

        modal: null,
        selectedEntries: [],

        /**
         * Initialize the entry selector
         */
        init: function() {
            this.modal = $('#myavana-entry-selector-modal');
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;

            // Open modal button
            $('.share-existing-entry-btn').on('click', function() {
                self.openModal();
            });

            // Close modal
            this.modal.find('.myavana-modal-close, #cancel-entry-selection').on('click', function() {
                self.closeModal();
            });

            // Close on overlay click
            this.modal.find('.myavana-modal-overlay').on('click', function() {
                self.closeModal();
            });

            // Search functionality
            $('#entry-search').on('keyup', function() {
                self.filterEntries();
            });

            // Photo filter
            $('#entry-filter-photos').on('change', function() {
                self.filterEntries();
            });

            // Checkbox selection
            this.modal.on('change', '.entry-selector-checkbox', function() {
                self.updateSelection();
            });

            // Select all
            $('#select-all-entries').on('click', function() {
                self.selectAll();
            });

            // Deselect all
            $('#deselect-all-entries').on('click', function() {
                self.deselectAll();
            });

            // Share button
            $('#share-selected-entries').on('click', function() {
                self.shareEntries();
            });
        },

        /**
         * Open the modal
         */
        openModal: function() {
            this.modal.fadeIn(200);
            $('body').css('overflow', 'hidden');
        },

        /**
         * Close the modal
         */
        closeModal: function() {
            this.modal.fadeOut(200);
            $('body').css('overflow', '');
            this.deselectAll();
        },

        /**
         * Filter entries based on search and filters
         */
        filterEntries: function() {
            const searchTerm = $('#entry-search').val().toLowerCase();
            const photoFilter = $('#entry-filter-photos').val();

            $('.entry-selector-card').each(function() {
                const $card = $(this);
                const title = $card.data('entry-title').toLowerCase();
                const hasPhotos = $card.data('has-photos');

                let showCard = true;

                // Search filter
                if (searchTerm && !title.includes(searchTerm)) {
                    showCard = false;
                }

                // Photo filter
                if (photoFilter === 'with-photos' && hasPhotos !== 'yes') {
                    showCard = false;
                } else if (photoFilter === 'no-photos' && hasPhotos === 'yes') {
                    showCard = false;
                }

                $card.toggle(showCard);
            });
        },

        /**
         * Update selection state
         */
        updateSelection: function() {
            this.selectedEntries = [];
            const self = this;

            $('.entry-selector-checkbox:checked').each(function() {
                self.selectedEntries.push($(this).val());
            });

            // Update count
            $('#selected-count').text(this.selectedEntries.length);

            // Enable/disable share button
            $('#share-selected-entries').prop('disabled', this.selectedEntries.length === 0);

            // Enforce max limit (10)
            if (this.selectedEntries.length >= 10) {
                $('.entry-selector-checkbox:not(:checked)').prop('disabled', true);
            } else {
                $('.entry-selector-checkbox').prop('disabled', false);
            }
        },

        /**
         * Select all visible entries
         */
        selectAll: function() {
            $('.entry-selector-card:visible').each(function() {
                const $checkbox = $(this).find('.entry-selector-checkbox');
                if (!$checkbox.prop('disabled') && !$checkbox.prop('checked')) {
                    $checkbox.prop('checked', true);
                }
            });
            this.updateSelection();
        },

        /**
         * Deselect all entries
         */
        deselectAll: function() {
            $('.entry-selector-checkbox').prop('checked', false).prop('disabled', false);
            this.updateSelection();
        },

        /**
         * Share selected entries via AJAX
         */
        shareEntries: function() {
            const self = this;
            const privacy = $('input[name="bulk_privacy"]:checked').val();
            const $button = $('#share-selected-entries');

            if (this.selectedEntries.length === 0) {
                alert('Please select at least one entry to share.');
                return;
            }

            // Disable button and show loading state
            $button.prop('disabled', true).text('Sharing...');

            $.ajax({
                url: window.myavanaCommunitySettings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'myavana_bulk_share_entries',
                    nonce: window.myavanaCommunitySettings.nonce,
                    entry_ids: this.selectedEntries,
                    privacy: privacy
                },
                success: function(response) {
                    if (response.success) {
                        const results = response.data.results;
                        const sharedCount = results.shared.length;
                        const alreadySharedCount = results.already_shared.length;
                        const failedCount = results.failed.length;

                        let message = '';
                        if (sharedCount > 0) {
                            message += `Successfully shared ${sharedCount} ${sharedCount === 1 ? 'entry' : 'entries'}!\n`;
                        }
                        if (alreadySharedCount > 0) {
                            message += `${alreadySharedCount} ${alreadySharedCount === 1 ? 'entry was' : 'entries were'} already shared.\n`;
                        }
                        if (failedCount > 0) {
                            message += `Failed to share ${failedCount} ${failedCount === 1 ? 'entry' : 'entries'}.\n`;
                        }

                        alert(message || 'Entries shared successfully!');

                        // Mark shared entries as shared
                        results.shared.forEach(function(entryId) {
                            const $card = $(`.entry-selector-card[data-entry-id="${entryId}"]`);
                            $card.addClass('already-shared');
                            $card.find('.entry-card-actions').html(`
                                <span class="entry-shared-badge">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    Already Shared
                                </span>
                            `);
                        });

                        // Close modal and reload feed
                        self.closeModal();
                        if (typeof MyavanaSocialFeed !== 'undefined' && MyavanaSocialFeed.loadPosts) {
                            MyavanaSocialFeed.loadPosts();
                        }
                    } else {
                        const errorMsg = response.data && response.data.message ? response.data.message : 'Failed to share entries';
                        alert('Error: ' + errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Share error:', xhr, status, error);
                    alert('Failed to share entries. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Share Selected Entries');
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        EntrySelector.init();
    });

})(jQuery);
