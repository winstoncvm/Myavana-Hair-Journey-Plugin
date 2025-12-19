/**
 * Entry Selector for Community Sharing
 * Allows users to browse and select existing entries to share
 *
 * @package Myavana_Hair_Journey
 * @version 1.0.0
 */

(function($) {
    'use strict';

    const EntrySelector = {

        modal: null,
        selectedEntries: [],
        allEntries: [],

        /**
         * Initialize the entry selector
         */
        init: function() {
            this.createModal();
            this.bindEvents();
        },

        /**
         * Create the modal HTML
         */
        createModal: function() {
            const modalHTML = `
                <div id="myavana-entry-selector-modal" class="myavana-modal" style="display: none;">
                    <div class="myavana-modal-overlay"></div>
                    <div class="myavana-modal-container">
                        <div class="myavana-modal-header">
                            <h2>Select Entries to Share</h2>
                            <button class="myavana-modal-close">&times;</button>
                        </div>

                        <div class="myavana-modal-body">
                            <!-- Search and Filter -->
                            <div class="entry-selector-filters">
                                <input type="text"
                                       id="entry-search"
                                       class="myavana-input"
                                       placeholder="Search entries...">
                                <select id="entry-filter-month" class="myavana-select">
                                    <option value="">All Months</option>
                                    <option value="30">Last 30 Days</option>
                                    <option value="60">Last 60 Days</option>
                                    <option value="90">Last 90 Days</option>
                                    <option value="180">Last 6 Months</option>
                                    <option value="365">Last Year</option>
                                </select>
                                <label class="checkbox-label">
                                    <input type="checkbox" id="filter-with-photos"> With Photos Only
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" id="filter-not-shared"> Not Shared Only
                                </label>
                            </div>

                            <!-- Selection Actions -->
                            <div class="entry-selector-actions">
                                <button class="myavana-btn-secondary select-all-btn">Select All</button>
                                <button class="myavana-btn-secondary deselect-all-btn">Deselect All</button>
                                <span class="selected-count">0 selected</span>
                            </div>

                            <!-- Loading State -->
                            <div class="entry-selector-loading" style="display: none;">
                                <div class="spinner"></div>
                                <p>Loading your entries...</p>
                            </div>

                            <!-- Entries Grid -->
                            <div class="entry-selector-grid"></div>

                            <!-- Empty State -->
                            <div class="entry-selector-empty" style="display: none;">
                                <p>No entries found. Start your hair journey by creating your first entry!</p>
                            </div>
                        </div>

                        <div class="myavana-modal-footer">
                            <div class="privacy-selector">
                                <label>Share as:</label>
                                <select id="share-privacy" class="myavana-select">
                                    <option value="public">Public</option>
                                    <option value="followers">Followers Only</option>
                                </select>
                            </div>
                            <button class="myavana-btn-secondary cancel-btn">Cancel</button>
                            <button class="myavana-btn share-selected-btn" disabled>
                                Share Selected (<span class="share-count">0</span>)
                            </button>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHTML);
            this.modal = $('#myavana-entry-selector-modal');
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            const self = this;

            // Open modal button
            $(document).on('click', '.open-entry-selector-btn, .share-existing-entry-btn', function(e) {
                e.preventDefault();
                self.openModal();
            });

            // Close modal
            this.modal.on('click', '.myavana-modal-close, .cancel-btn, .myavana-modal-overlay', function() {
                self.closeModal();
            });

            // Search
            this.modal.on('input', '#entry-search', function() {
                self.filterEntries();
            });

            // Filters
            this.modal.on('change', '#entry-filter-month, #filter-with-photos, #filter-not-shared', function() {
                self.filterEntries();
            });

            // Select/Deselect All
            this.modal.on('click', '.select-all-btn', function() {
                self.selectAll();
            });

            this.modal.on('click', '.deselect-all-btn', function() {
                self.deselectAll();
            });

            // Entry Selection
            this.modal.on('click', '.entry-selector-card', function() {
                const entryId = $(this).data('entry-id');
                const isShared = $(this).hasClass('already-shared');

                if (isShared) {
                    alert('This entry has already been shared to the community');
                    return;
                }

                self.toggleEntrySelection(entryId, $(this));
            });

            // Preview Entry
            this.modal.on('click', '.preview-entry-btn', function(e) {
                e.stopPropagation();
                const entryId = $(this).closest('.entry-selector-card').data('entry-id');
                self.previewEntry(entryId);
            });

            // Share Selected
            this.modal.on('click', '.share-selected-btn', function() {
                self.shareSelectedEntries();
            });
        },

        /**
         * Open modal and load entries
         */
        openModal: function() {
            this.modal.fadeIn(300);
            this.selectedEntries = [];
            this.loadEntries();
        },

        /**
         * Close modal
         */
        closeModal: function() {
            this.modal.fadeOut(300);
            this.selectedEntries = [];
            this.updateSelectionCount();
        },

        /**
         * Load user's entries
         */
        loadEntries: function() {
            const self = this;

            this.modal.find('.entry-selector-loading').show();
            this.modal.find('.entry-selector-grid').empty();

            // Debug: Log what we're sending
            console.log('myavanaAjax object:', myavanaAjax);
            console.log('Nonce being sent:', myavanaAjax.nonce);
            console.log('AJAX URL:', myavanaAjax.ajax_url);

            $.ajax({
                url: myavanaAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'myavana_get_shareable_entries',
                    nonce: myavanaAjax.nonce
                },
                success: function(response) {
                    self.modal.find('.entry-selector-loading').hide();

                    console.log('Entry selector response:', response);

                    if (response.success) {
                        self.allEntries = response.data.entries;

                        if (self.allEntries.length === 0) {
                            self.modal.find('.entry-selector-empty').show();
                        } else {
                            self.renderEntries(self.allEntries);
                        }
                    } else {
                        const errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                        console.error('Failed to load entries:', errorMsg);
                        alert('Failed to load entries: ' + errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    self.modal.find('.entry-selector-loading').hide();
                    console.error('AJAX error:', xhr, status, error);
                    console.error('Response text:', xhr.responseText);
                    alert('Failed to load entries. Please check the console for details.');
                }
            });
        },

        /**
         * Render entries in grid
         */
        renderEntries: function(entries) {
            const grid = this.modal.find('.entry-selector-grid');
            grid.empty();

            if (entries.length === 0) {
                this.modal.find('.entry-selector-empty').show();
                return;
            }

            this.modal.find('.entry-selector-empty').hide();

            entries.forEach(entry => {
                const card = this.createEntryCard(entry);
                grid.append(card);
            });
        },

        /**
         * Create entry card HTML
         */
        createEntryCard: function(entry) {
            const sharedClass = entry.is_shared ? 'already-shared' : '';
            const selectedClass = this.selectedEntries.includes(entry.id) ? 'selected' : '';
            const hasPhotoClass = entry.has_photos ? 'has-photo' : '';

            const thumbnail = entry.photo_url
                ? `<div class="entry-thumbnail" style="background-image: url('${entry.photo_url}');"></div>`
                : `<div class="entry-thumbnail no-photo"><span>📷</span></div>`;

            const healthRating = entry.health_rating
                ? `<div class="entry-health">❤️ ${entry.health_rating}/10</div>`
                : '';

            const mood = entry.mood
                ? `<div class="entry-mood">${this.getMoodEmoji(entry.mood)}</div>`
                : '';

            const sharedBadge = entry.is_shared
                ? '<div class="shared-badge">✓ Shared</div>'
                : '';

            const photoCount = entry.photo_count > 1
                ? `<div class="photo-count">📷 ${entry.photo_count}</div>`
                : '';

            return `
                <div class="entry-selector-card ${sharedClass} ${selectedClass} ${hasPhotoClass}"
                     data-entry-id="${entry.id}"
                     data-date="${entry.entry_date}"
                     data-has-photos="${entry.has_photos}">

                    ${thumbnail}
                    ${sharedBadge}
                    ${photoCount}

                    <div class="entry-card-content">
                        <div class="entry-date">${entry.formatted_date}</div>
                        ${entry.title ? `<h4 class="entry-title">${this.escapeHtml(entry.title)}</h4>` : ''}
                        ${entry.notes ? `<p class="entry-notes">${this.truncate(this.escapeHtml(entry.notes), 100)}</p>` : ''}

                        <div class="entry-meta">
                            ${healthRating}
                            ${mood}
                        </div>
                    </div>

                    <div class="entry-card-actions">
                        <button class="preview-entry-btn" title="Preview">
                            <i class="icon-eye"></i> Preview
                        </button>
                        ${!entry.is_shared ? '<div class="selection-indicator">✓</div>' : ''}
                    </div>
                </div>
            `;
        },

        /**
         * Filter entries based on search and filters
         */
        filterEntries: function() {
            const searchTerm = this.modal.find('#entry-search').val().toLowerCase();
            const monthFilter = parseInt(this.modal.find('#entry-filter-month').val()) || 0;
            const withPhotosOnly = this.modal.find('#filter-with-photos').is(':checked');
            const notSharedOnly = this.modal.find('#filter-not-shared').is(':checked');

            const now = new Date();
            const cutoffDate = monthFilter > 0
                ? new Date(now.getTime() - (monthFilter * 24 * 60 * 60 * 1000))
                : null;

            const filteredEntries = this.allEntries.filter(entry => {
                // Search filter
                if (searchTerm) {
                    const matchesSearch =
                        (entry.title && entry.title.toLowerCase().includes(searchTerm)) ||
                        (entry.notes && entry.notes.toLowerCase().includes(searchTerm));
                    if (!matchesSearch) return false;
                }

                // Date filter
                if (cutoffDate) {
                    const entryDate = new Date(entry.entry_date);
                    if (entryDate < cutoffDate) return false;
                }

                // Photos filter
                if (withPhotosOnly && !entry.has_photos) return false;

                // Shared filter
                if (notSharedOnly && entry.is_shared) return false;

                return true;
            });

            this.renderEntries(filteredEntries);
        },

        /**
         * Toggle entry selection
         */
        toggleEntrySelection: function(entryId, $card) {
            const index = this.selectedEntries.indexOf(entryId);

            if (index > -1) {
                // Deselect
                this.selectedEntries.splice(index, 1);
                $card.removeClass('selected');
            } else {
                // Select
                this.selectedEntries.push(entryId);
                $card.addClass('selected');
            }

            this.updateSelectionCount();
        },

        /**
         * Select all visible entries
         */
        selectAll: function() {
            const self = this;
            this.modal.find('.entry-selector-card').not('.already-shared').each(function() {
                const entryId = parseInt($(this).data('entry-id'));
                if (!self.selectedEntries.includes(entryId)) {
                    self.selectedEntries.push(entryId);
                    $(this).addClass('selected');
                }
            });
            this.updateSelectionCount();
        },

        /**
         * Deselect all entries
         */
        deselectAll: function() {
            this.selectedEntries = [];
            this.modal.find('.entry-selector-card').removeClass('selected');
            this.updateSelectionCount();
        },

        /**
         * Update selection count display
         */
        updateSelectionCount: function() {
            const count = this.selectedEntries.length;
            this.modal.find('.selected-count').text(`${count} selected`);
            this.modal.find('.share-count').text(count);
            this.modal.find('.share-selected-btn').prop('disabled', count === 0);
        },

        /**
         * Preview entry before sharing
         */
        previewEntry: function(entryId) {
            // TODO: Implement preview modal
            console.log('Preview entry:', entryId);
        },

        /**
         * Share selected entries
         */
        shareSelectedEntries: function() {
            if (this.selectedEntries.length === 0) {
                return;
            }

            const privacy = this.modal.find('#share-privacy').val();
            const count = this.selectedEntries.length;

            if (!confirm(`Share ${count} ${count === 1 ? 'entry' : 'entries'} to the community?`)) {
                return;
            }

            const $btn = this.modal.find('.share-selected-btn');
            const originalText = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner"></span> Sharing...');

            const self = this;

            if (count === 1) {
                // Single entry
                this.shareSingleEntry(this.selectedEntries[0], privacy, $btn, originalText);
            } else {
                // Bulk share
                this.bulkShareEntries(this.selectedEntries, privacy, $btn, originalText);
            }
        },

        /**
         * Share single entry
         */
        shareSingleEntry: function(entryId, privacy, $btn, originalText) {
            const self = this;

            $.ajax({
                url: myavanaAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'myavana_share_selected_entry',
                    nonce: myavanaAjax.nonce,
                    entry_id: entryId,
                    privacy: privacy
                },
                success: function(response) {
                    if (response.success) {
                        self.showSuccessMessage(`Entry shared successfully! +${response.data.points_earned} points`);
                        self.closeModal();

                        // Trigger refresh of community feed if on that page
                        $(document).trigger('myavana:entry-shared', [response.data]);
                    } else {
                        alert('Failed to share entry: ' + (response.data.message || 'Unknown error'));
                        $btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function() {
                    alert('Failed to share entry. Please try again.');
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        },

        /**
         * Bulk share multiple entries
         */
        bulkShareEntries: function(entryIds, privacy, $btn, originalText) {
            const self = this;

            $.ajax({
                url: myavanaAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'myavana_bulk_share_entries',
                    nonce: myavanaAjax.nonce,
                    entry_ids: entryIds,
                    privacy: privacy
                },
                success: function(response) {
                    if (response.success) {
                        const shared = response.data.shared.length;
                        const failed = response.data.failed.length;
                        const alreadyShared = response.data.already_shared.length;

                        let message = `${shared} ${shared === 1 ? 'entry' : 'entries'} shared successfully!`;
                        if (response.data.total_points > 0) {
                            message += ` +${response.data.total_points} points`;
                        }
                        if (alreadyShared > 0) {
                            message += `\n${alreadyShared} already shared.`;
                        }
                        if (failed > 0) {
                            message += `\n${failed} failed to share.`;
                        }

                        self.showSuccessMessage(message);
                        self.closeModal();

                        $(document).trigger('myavana:entries-shared', [response.data]);
                    } else {
                        alert('Failed to share entries: ' + (response.data.message || 'Unknown error'));
                        $btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function() {
                    alert('Failed to share entries. Please try again.');
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        },

        /**
         * Show success message
         */
        showSuccessMessage: function(message) {
            // Create toast notification
            const toast = $(`
                <div class="myavana-toast success">
                    <span>${message}</span>
                </div>
            `);

            $('body').append(toast);

            setTimeout(() => {
                toast.addClass('show');
            }, 100);

            setTimeout(() => {
                toast.removeClass('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        },

        /**
         * Helper: Get mood emoji
         */
        getMoodEmoji: function(mood) {
            const moodEmojis = {
                'happy': '😊',
                'excited': '🤩',
                'content': '😌',
                'neutral': '😐',
                'frustrated': '😤',
                'sad': '😢',
                'confident': '💪'
            };
            return moodEmojis[mood] || '😊';
        },

        /**
         * Helper: Truncate text
         */
        truncate: function(text, length) {
            if (text.length <= length) return text;
            return text.substring(0, length) + '...';
        },

        /**
         * Helper: Escape HTML
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        EntrySelector.init();
    });

})(jQuery);
