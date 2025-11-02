/**
 * Visitor Contact Collector - Admin JavaScript
 * Handles admin panel interactions, AJAX operations, and UI enhancements
 */

(function($) {
    'use strict';
    
    /**
     * Admin interface object
     */
    var VCCAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.initColorPickers();
            this.initDataTables();
            this.setupFormPreview();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Delete contact
            $(document).on('click', '.vcc-delete-contact', this.handleDeleteContact);
            
            // Bulk actions
            $('#doaction').on('click', this.handleBulkAction);
            
            // Export form handling
            $(document).on('change', 'input[name="date_range"]', this.toggleCustomDateRange);
            
            // GDPR tools
            $(document).on('click', '.vcc-gdpr-export', this.handleGDPRExport);
            $(document).on('click', '.vcc-gdpr-delete', this.handleGDPRDelete);
            
            // Settings form enhancements
            $(document).on('change', '#vcc_settings input, #vcc_settings select', this.handleSettingsChange);
            
            // Search functionality
            $('#contact-search-input').on('keyup', VCCUtils.debounce(this.handleSearch, 300));
            
            // Select all checkbox
            $('#cb-select-all-1').on('change', this.handleSelectAll);
            
            // Individual checkboxes
            $(document).on('change', 'input[name="contacts[]"]', this.updateSelectAllState);
        },
        
        /**
         * Handle contact deletion
         */
        handleDeleteContact: function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var contactId = $this.data('id');
            var contactName = $this.data('name');
            
            if (!contactId) {
                return;
            }
            
            // Confirm deletion
            if (!confirm('Are you sure you want to delete the contact "' + contactName + '"? This action cannot be undone.')) {
                return;
            }
            
            // Show loading state
            $this.prop('disabled', true).text('Deleting...');
            
            // AJAX request
            $.ajax({
                url: window.vcc_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vcc_delete_contact',
                    contact_id: contactId,
                    nonce: window.vcc_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Remove row from table
                        $this.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                            VCCAdmin.updateContactCount();
                        });
                        
                        VCCAdmin.showNotice(response.data.message, 'success');
                    } else {
                        VCCAdmin.showNotice(response.data.message, 'error');
                        $this.prop('disabled', false).text('Delete');
                    }
                },
                error: function() {
                    VCCAdmin.showNotice('An error occurred while deleting the contact.', 'error');
                    $this.prop('disabled', false).text('Delete');
                }
            });
        },
        
        /**
         * Handle bulk actions
         */
        handleBulkAction: function(e) {
            var action = $('#bulk-action-selector-top').val();
            var selectedContacts = $('input[name="contacts[]"]:checked');
            
            if (action === '-1') {
                e.preventDefault();
                VCCAdmin.showNotice('Please select a bulk action.', 'warning');
                return;
            }
            
            if (selectedContacts.length === 0) {
                e.preventDefault();
                VCCAdmin.showNotice('Please select at least one contact.', 'warning');
                return;
            }
            
            if (action === 'delete') {
                if (!confirm('Are you sure you want to delete the selected contacts? This action cannot be undone.')) {
                    e.preventDefault();
                    return;
                }
            }
            
            // Let the form submit naturally for bulk actions
        },
        
        /**
         * Toggle custom date range inputs
         */
        toggleCustomDateRange: function() {
            var isCustom = $(this).val() === 'custom';
            $('#custom-date-range').toggle(isCustom);
            
            if (isCustom) {
                $('#custom-date-range input').prop('required', true);
            } else {
                $('#custom-date-range input').prop('required', false);
            }
        },
        
        /**
         * Handle GDPR export
         */
        handleGDPRExport: function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var email = $this.siblings('input[type="email"]').val();
            
            if (!email) {
                VCCAdmin.showNotice('Please enter an email address.', 'warning');
                return;
            }
            
            if (!VCCAdmin.isValidEmail(email)) {
                VCCAdmin.showNotice('Please enter a valid email address.', 'error');
                return;
            }
            
            $this.prop('disabled', true).text('Exporting...');
            
            $.ajax({
                url: window.vcc_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vcc_gdpr_export',
                    email: email,
                    nonce: window.vcc_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VCCAdmin.showNotice(response.data.message, 'success');
                        VCCAdmin.displayGDPRData(response.data.data);
                    } else {
                        VCCAdmin.showNotice(response.data.message, 'error');
                    }
                    $this.prop('disabled', false).text('Export Data');
                },
                error: function() {
                    VCCAdmin.showNotice('An error occurred during export.', 'error');
                    $this.prop('disabled', false).text('Export Data');
                }
            });
        },
        
        /**
         * Handle GDPR deletion
         */
        handleGDPRDelete: function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var email = $this.siblings('input[type="email"]').val();
            
            if (!email) {
                VCCAdmin.showNotice('Please enter an email address.', 'warning');
                return;
            }
            
            if (!VCCAdmin.isValidEmail(email)) {
                VCCAdmin.showNotice('Please enter a valid email address.', 'error');
                return;
            }
            
            if (!confirm('Are you sure you want to delete all data for "' + email + '"? This action cannot be undone.')) {
                return;
            }
            
            $this.prop('disabled', true).text('Deleting...');
            
            $.ajax({
                url: window.vcc_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vcc_gdpr_delete',
                    email: email,
                    nonce: window.vcc_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VCCAdmin.showNotice(response.data.message, 'success');
                        // Clear the email input
                        $this.siblings('input[type="email"]').val('');
                    } else {
                        VCCAdmin.showNotice(response.data.message, 'error');
                    }
                    $this.prop('disabled', false).text('Delete Data');
                },
                error: function() {
                    VCCAdmin.showNotice('An error occurred during deletion.', 'error');
                    $this.prop('disabled', false).text('Delete Data');
                }
            });
        },
        
        /**
         * Handle settings changes
         */
        handleSettingsChange: function() {
            // Update form preview when settings change
            VCCAdmin.updateFormPreview();
            
            // Show unsaved changes warning
            VCCAdmin.showUnsavedChangesWarning();
        },
        
        /**
         * Handle search functionality
         */
        handleSearch: function() {
            // Let the default search form submission handle this
            // This is just for UX feedback
            var query = $(this).val();
            if (query.length > 2) {
                $(this).addClass('vcc-searching');
            } else {
                $(this).removeClass('vcc-searching');
            }
        },
        
        /**
         * Handle select all checkbox
         */
        handleSelectAll: function() {
            var isChecked = $(this).is(':checked');
            $('input[name="contacts[]"]').prop('checked', isChecked);
            VCCAdmin.updateBulkActionButtonState();
        },
        
        /**
         * Update select all state based on individual checkboxes
         */
        updateSelectAllState: function() {
            var totalCheckboxes = $('input[name="contacts[]"]').length;
            var checkedCheckboxes = $('input[name="contacts[]"]:checked').length;
            var selectAllCheckbox = $('#cb-select-all-1');
            
            if (checkedCheckboxes === 0) {
                selectAllCheckbox.prop('checked', false).prop('indeterminate', false);
            } else if (checkedCheckboxes === totalCheckboxes) {
                selectAllCheckbox.prop('checked', true).prop('indeterminate', false);
            } else {
                selectAllCheckbox.prop('checked', false).prop('indeterminate', true);
            }
            
            VCCAdmin.updateBulkActionButtonState();
        },
        
        /**
         * Update bulk action button state
         */
        updateBulkActionButtonState: function() {
            var hasSelection = $('input[name="contacts[]"]:checked').length > 0;
            $('#doaction').prop('disabled', !hasSelection);
        },
        
        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            // Simple tooltip implementation
            $(document).on('mouseenter', '.vcc-tooltip', function() {
                var tooltip = $(this).data('tooltip');
                if (tooltip) {
                    var $tooltip = $('<div class="vcc-tooltip-popup">' + tooltip + '</div>');
                    $('body').append($tooltip);
                    
                    var offset = $(this).offset();
                    $tooltip.css({
                        top: offset.top - $tooltip.outerHeight() - 5,
                        left: offset.left + ($(this).outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                    });
                }
            });
            
            $(document).on('mouseleave', '.vcc-tooltip', function() {
                $('.vcc-tooltip-popup').remove();
            });
        },
        
        /**
         * Initialize color pickers
         */
        initColorPickers: function() {
            if (typeof jQuery.fn.wpColorPicker !== 'undefined') {
                $('input[type="color"]').wpColorPicker({
                    change: function() {
                        VCCAdmin.updateFormPreview();
                    }
                });
            }
        },
        
        /**
         * Initialize data tables enhancements
         */
        initDataTables: function() {
            // Add row highlighting
            $('.wp-list-table tbody tr').on('mouseenter', function() {
                $(this).addClass('vcc-row-hover');
            }).on('mouseleave', function() {
                $(this).removeClass('vcc-row-hover');
            });
            
            // Improve checkbox accessibility
            $('input[name="contacts[]"]').on('focus', function() {
                $(this).closest('tr').addClass('vcc-row-focus');
            }).on('blur', function() {
                $(this).closest('tr').removeClass('vcc-row-focus');
            });
        },
        
        /**
         * Setup form preview
         */
        setupFormPreview: function() {
            if ($('.vcc-form-preview').length > 0) {
                this.updateFormPreview();
            }
        },
        
        /**
         * Update form preview
         */
        updateFormPreview: function() {
            // This would update a live preview of the form based on current settings
            // Implementation depends on having a preview area in the admin
        },
        
        /**
         * Show admin notice
         */
        showNotice: function(message, type) {
            type = type || 'info';
            
            var noticeClass = 'notice vcc-notice notice-' + type + ' is-dismissible';
            var $notice = $('<div class="' + noticeClass + '"><p>' + message + '</p></div>');
            
            // Add dismiss button
            $notice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
            
            // Insert notice
            $('.wrap > h1').after($notice);
            
            // Handle dismiss
            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // Auto-dismiss after 5 seconds for success notices
            if (type === 'success') {
                setTimeout(function() {
                    $notice.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
            
            // Scroll to notice
            $('html, body').animate({
                scrollTop: $notice.offset().top - 50
            }, 300);
        },
        
        /**
         * Display GDPR data
         */
        displayGDPRData: function(data) {
            if (!data || !data.data || data.data.length === 0) {
                VCCAdmin.showNotice('No data found for the specified email address.', 'info');
                return;
            }
            
            var html = '<div class="vcc-gdpr-export-result">';
            html += '<h3>Exported Data</h3>';
            
            $.each(data.data, function(index, item) {
                html += '<div class="vcc-gdpr-item">';
                html += '<h4>' + item.group_label + ' (ID: ' + item.item_id + ')</h4>';
                html += '<table class="widefat">';
                
                $.each(item.data, function(i, field) {
                    html += '<tr>';
                    html += '<td><strong>' + field.name + '</strong></td>';
                    html += '<td>' + field.value + '</td>';
                    html += '</tr>';
                });
                
                html += '</table>';
                html += '</div>';
            });
            
            html += '</div>';
            
            // Create modal or insert into page
            VCCAdmin.showModal('GDPR Export Results', html);
        },
        
        /**
         * Show modal dialog
         */
        showModal: function(title, content) {
            var modal = '<div class="vcc-modal-overlay">';
            modal += '<div class="vcc-modal">';
            modal += '<div class="vcc-modal-header">';
            modal += '<h2>' + title + '</h2>';
            modal += '<button class="vcc-modal-close" type="button">&times;</button>';
            modal += '</div>';
            modal += '<div class="vcc-modal-content">' + content + '</div>';
            modal += '</div>';
            modal += '</div>';
            
            $('body').append(modal);
            
            // Handle close
            $('.vcc-modal-overlay, .vcc-modal-close').on('click', function(e) {
                if (e.target === this) {
                    $('.vcc-modal-overlay').fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            });
            
            // Prevent closing when clicking inside modal
            $('.vcc-modal').on('click', function(e) {
                e.stopPropagation();
            });
        },
        
        /**
         * Update contact count display
         */
        updateContactCount: function() {
            // Update any contact count displays
            var $countDisplay = $('.displaying-num');
            if ($countDisplay.length > 0) {
                var currentText = $countDisplay.text();
                var currentCount = parseInt(currentText.match(/\d+/)[0]);
                var newCount = currentCount - 1;
                $countDisplay.text(currentText.replace(/\d+/, newCount));
            }
        },
        
        /**
         * Show unsaved changes warning
         */
        showUnsavedChangesWarning: function() {
            if (!$('.vcc-unsaved-warning').length) {
                var warning = '<div class="notice notice-warning vcc-unsaved-warning"><p>You have unsaved changes. Don\'t forget to save your settings!</p></div>';
                $('.form-table').first().before(warning);
            }
        },
        
        /**
         * Validate email format
         */
        isValidEmail: function(email) {
            var regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            return regex.test(email);
        }
    };
    
    /**
     * Utility functions for admin
     */
    var VCCUtils = {
        
        /**
         * Debounce function
         */
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },
        
        /**
         * Format number with commas
         */
        numberFormat: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },
        
        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            return $('<div>').text(text).html();
        }
    };
    
    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        VCCAdmin.init();
        
        // Remove unsaved changes warning when form is submitted
        $('form').on('submit', function() {
            $('.vcc-unsaved-warning').remove();
        });
        
        // Initialize select all state
        VCCAdmin.updateSelectAllState();
        VCCAdmin.updateBulkActionButtonState();
    });
    
    // Export VCCAdmin for external access
    window.VCCAdmin = VCCAdmin;
    
})(jQuery);