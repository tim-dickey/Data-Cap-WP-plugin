/**
 * Visitor Contact Collector - Frontend JavaScript
 * Handles form validation, submission, and user interactions
 */

(function($) {
    'use strict';
    
    /**
     * VCC Form Handler Class
     */
    window.VCCForm = function(formId) {
        this.formId = formId;
        this.form = document.getElementById(formId);
        this.isSubmitting = false;
        
        if (!this.form) {
            console.warn('VCC: Form with ID "' + formId + '" not found');
            return;
        }
        
        this.init();
    };
    
    VCCForm.prototype = {
        
        /**
         * Initialize the form
         */
        init: function() {
            this.bindEvents();
            this.setupValidation();
            this.enhanceAccessibility();
        },
        
        /**
         * Bind form events
         */
        bindEvents: function() {
            var self = this;
            
            // Form submission
            $(this.form).on('submit', function(e) {
                e.preventDefault();
                self.handleSubmit();
            });
            
            // Real-time validation
            $(this.form).find('input').on('blur', function() {
                self.validateField(this);
            });
            
            // Clear errors on input
            $(this.form).find('input').on('input', function() {
                self.clearFieldError(this);
            });
            
            // Phone number formatting
            $(this.form).find('input[type="tel"]').on('input', function() {
                self.formatPhoneNumber(this);
            });
            
            // Checkbox validation
            $(this.form).find('input[type="checkbox"]').on('change', function() {
                self.validateField(this);
            });
        },
        
        /**
         * Setup form validation
         */
        setupValidation: function() {
            var self = this;
            
            // Add validation attributes
            $(this.form).find('input[type="email"]').attr('pattern', '[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,}$');
            $(this.form).find('input[type="tel"]').attr('pattern', '[+]?[0-9\\s\\-\\(\\)]{10,}');
            
            // Set up custom validation messages
            this.setupCustomValidation();
        },
        
        /**
         * Setup custom HTML5 validation messages
         */
        setupCustomValidation: function() {
            var messages = window.vcc_ajax ? window.vcc_ajax.messages : {};
            
            $(this.form).find('input').each(function() {
                var input = this;
                var field = input.name;
                
                input.addEventListener('invalid', function() {
                    if (input.validity.valueMissing) {
                        input.setCustomValidity(messages.required_field || 'This field is required.');
                    } else if (input.validity.typeMismatch && input.type === 'email') {
                        input.setCustomValidity(messages.invalid_email || 'Please enter a valid email address.');
                    } else if (input.validity.patternMismatch && input.type === 'tel') {
                        input.setCustomValidity(messages.invalid_phone || 'Please enter a valid phone number.');
                    } else {
                        input.setCustomValidity('');
                    }
                });
                
                input.addEventListener('input', function() {
                    input.setCustomValidity('');
                });
            });
        },
        
        /**
         * Enhance accessibility
         */
        enhanceAccessibility: function() {
            // Add ARIA labels and descriptions
            $(this.form).find('input[required]').attr('aria-required', 'true');
            
            // Link error messages to fields
            $(this.form).find('.vcc-field-error').each(function() {
                var errorId = 'vcc-error-' + Math.random().toString(36).substr(2, 9);
                $(this).attr('id', errorId);
                $(this).prev('input').attr('aria-describedby', errorId);
            });
            
            // Add live region for form messages
            if (!$(this.form).find('.vcc-messages').attr('aria-live')) {
                $(this.form).find('.vcc-messages').attr('aria-live', 'polite');
            }
        },
        
        /**
         * Handle form submission
         */
        handleSubmit: function() {
            if (this.isSubmitting) {
                return;
            }
            
            // Clear previous messages
            this.clearMessages();
            
            // Validate form
            if (!this.validateForm()) {
                return;
            }
            
            // Show loading state
            this.setLoadingState(true);
            
            // Prepare form data
            var formData = this.getFormData();
            
            // Submit via AJAX
            this.submitForm(formData);
        },
        
        /**
         * Validate entire form
         */
        validateForm: function() {
            var isValid = true;
            var self = this;
            
            // Validate all required fields
            $(this.form).find('input[required]').each(function() {
                if (!self.validateField(this)) {
                    isValid = false;
                }
            });
            
            // Check HTML5 validity
            if (!this.form.checkValidity()) {
                isValid = false;
                // Focus first invalid field
                $(this.form).find('input:invalid').first().focus();
            }
            
            return isValid;
        },
        
        /**
         * Validate individual field
         */
        validateField: function(field) {
            var $field = $(field);
            var value = $field.val().trim();
            var fieldName = field.name;
            var isValid = true;
            var errorMessage = '';
            var messages = window.vcc_ajax ? window.vcc_ajax.messages : {};
            
            // Clear existing error
            this.clearFieldError(field);
            
            // Required field validation
            if (field.required && !value) {
                errorMessage = messages.required_field || 'This field is required.';
                isValid = false;
            }
            // Email validation
            else if (fieldName === 'email' && value) {
                if (!this.isValidEmail(value)) {
                    errorMessage = messages.invalid_email || 'Please enter a valid email address.';
                    isValid = false;
                }
            }
            // Phone validation
            else if (fieldName === 'phone' && value) {
                if (!this.isValidPhone(value)) {
                    errorMessage = messages.invalid_phone || 'Please enter a valid phone number.';
                    isValid = false;
                }
            }
            // Checkbox validation (GDPR consent)
            else if (field.type === 'checkbox' && field.required && !field.checked) {
                errorMessage = messages.gdpr_required || 'You must agree to the privacy policy.';
                isValid = false;
            }
            
            // Show error if validation failed
            if (!isValid) {
                this.showFieldError(field, errorMessage);
            }
            
            return isValid;
        },
        
        /**
         * Validate email format
         */
        isValidEmail: function(email) {
            var regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            return regex.test(email);
        },
        
        /**
         * Validate phone number format
         */
        isValidPhone: function(phone) {
            // Remove all non-numeric characters except +
            var cleaned = phone.replace(/[^+0-9]/g, '');
            
            // Check if it has at least 10 digits
            var digitCount = cleaned.replace(/[^0-9]/g, '').length;
            return digitCount >= 10 && digitCount <= 15;
        },
        
        /**
         * Format phone number as user types
         */
        formatPhoneNumber: function(field) {
            var value = field.value.replace(/[^+0-9]/g, '');
            var formatted = value;
            
            // Basic US formatting for numbers starting without +
            if (!value.startsWith('+') && value.length === 10) {
                formatted = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
            } else if (!value.startsWith('+') && value.length === 11 && value.startsWith('1')) {
                formatted = value.replace(/(\d{1})(\d{3})(\d{3})(\d{4})/, '$1 ($2) $3-$4');
            }
            
            field.value = formatted;
        },
        
        /**
         * Show field error
         */
        showFieldError: function(field, message) {
            var $field = $(field);
            var $fieldContainer = $field.closest('.vcc-form-field');
            var $errorElement = $fieldContainer.find('.vcc-field-error');
            
            $fieldContainer.addClass('vcc-has-error');
            $errorElement.text(message).addClass('vcc-show');
            
            // Update ARIA
            $field.attr('aria-invalid', 'true');
        },
        
        /**
         * Clear field error
         */
        clearFieldError: function(field) {
            var $field = $(field);
            var $fieldContainer = $field.closest('.vcc-form-field');
            var $errorElement = $fieldContainer.find('.vcc-field-error');
            
            $fieldContainer.removeClass('vcc-has-error');
            $errorElement.removeClass('vcc-show').text('');
            
            // Update ARIA
            $field.attr('aria-invalid', 'false');
        },
        
        /**
         * Get form data
         */
        getFormData: function() {
            var data = {
                action: 'vcc_submit_form',
                vcc_nonce: $(this.form).find('input[name="vcc_nonce"]').val()
            };
            
            // Get form fields
            $(this.form).find('input').each(function() {
                var $input = $(this);
                var name = $input.attr('name');
                var type = $input.attr('type');
                
                if (name && name !== 'vcc_nonce') {
                    if (type === 'checkbox') {
                        data[name] = $input.is(':checked') ? $input.val() : '';
                    } else {
                        data[name] = $input.val().trim();
                    }
                }
            });
            
            return data;
        },
        
        /**
         * Submit form via AJAX
         */
        submitForm: function(formData) {
            var self = this;
            
            $.ajax({
                url: window.vcc_ajax ? window.vcc_ajax.ajax_url : '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                timeout: 30000,
                success: function(response) {
                    self.handleSubmitResponse(response);
                },
                error: function(xhr, status, error) {
                    self.handleSubmitError(xhr, status, error);
                },
                complete: function() {
                    self.setLoadingState(false);
                }
            });
        },
        
        /**
         * Handle form submission response
         */
        handleSubmitResponse: function(response) {
            if (response.success) {
                this.showMessage(response.data.message, 'success');
                this.resetForm();
                this.trackConversion();
            } else {
                if (response.data && response.data.errors) {
                    this.showFieldErrors(response.data.errors);
                }
                this.showMessage(response.data.message || 'An error occurred. Please try again.', 'error');
            }
        },
        
        /**
         * Handle form submission error
         */
        handleSubmitError: function(xhr, status, error) {
            var message = 'An error occurred. Please try again.';
            
            if (status === 'timeout') {
                message = 'Request timed out. Please check your connection and try again.';
            } else if (xhr.status === 0) {
                message = 'Network error. Please check your connection.';
            }
            
            this.showMessage(message, 'error');
        },
        
        /**
         * Show field errors
         */
        showFieldErrors: function(errors) {
            var self = this;
            
            $.each(errors, function(fieldName, message) {
                var field = $(self.form).find('input[name="' + fieldName + '"]')[0];
                if (field) {
                    self.showFieldError(field, message);
                }
            });
        },
        
        /**
         * Show form message
         */
        showMessage: function(message, type) {
            var $messagesContainer = $(this.form).parent().find('.vcc-messages');
            
            if (!$messagesContainer.length) {
                $messagesContainer = $('<div class="vcc-messages" aria-live="polite"></div>');
                $(this.form).before($messagesContainer);
            }
            
            var messageClass = 'vcc-message vcc-message-' + type;
            var $message = $('<div class="' + messageClass + '">' + message + '</div>');
            
            $messagesContainer.empty().append($message);
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $messagesContainer.offset().top - 20
            }, 300);
            
            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(function() {
                    $message.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        },
        
        /**
         * Clear all messages
         */
        clearMessages: function() {
            $(this.form).parent().find('.vcc-messages').empty();
        },
        
        /**
         * Set loading state
         */
        setLoadingState: function(loading) {
            this.isSubmitting = loading;
            var $submitBtn = $(this.form).find('.vcc-submit-btn');
            var $submitText = $submitBtn.find('.vcc-submit-text');
            var $submitSpinner = $submitBtn.find('.vcc-submit-spinner');
            
            if (loading) {
                $submitBtn.prop('disabled', true).addClass('vcc-loading');
                $submitText.hide();
                $submitSpinner.show();
            } else {
                $submitBtn.prop('disabled', false).removeClass('vcc-loading');
                $submitText.show();
                $submitSpinner.hide();
            }
        },
        
        /**
         * Reset form
         */
        resetForm: function() {
            this.form.reset();
            
            // Clear all errors
            var self = this;
            $(this.form).find('input').each(function() {
                self.clearFieldError(this);
            });
        },
        
        /**
         * Track conversion for analytics
         */
        trackConversion: function() {
            // Google Analytics tracking
            if (typeof gtag !== 'undefined') {
                gtag('event', 'form_submit', {
                    'event_category': 'Contact Form',
                    'event_label': 'Visitor Contact Collector'
                });
            }
            
            // Facebook Pixel tracking
            if (typeof fbq !== 'undefined') {
                fbq('track', 'Lead');
            }
            
            // Custom event for other tracking systems
            $(document).trigger('vcc_form_submitted', {
                formId: this.formId,
                timestamp: new Date().toISOString()
            });
        }
    };
    
    /**
     * Auto-initialize forms when DOM is ready
     */
    $(document).ready(function() {
        // Initialize all VCC forms on the page
        $('.vcc-contact-form').each(function() {
            if (this.id) {
                new VCCForm(this.id);
            }
        });
        
        // Handle dynamically added forms
        $(document).on('DOMNodeInserted', function(e) {
            var $target = $(e.target);
            if ($target.hasClass('vcc-contact-form') && $target.attr('id')) {
                new VCCForm($target.attr('id'));
            } else {
                $target.find('.vcc-contact-form').each(function() {
                    if (this.id && !$(this).data('vcc-initialized')) {
                        new VCCForm(this.id);
                        $(this).data('vcc-initialized', true);
                    }
                });
            }
        });
    });
    
    /**
     * Utility functions
     */
    window.VCCUtils = {
        
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
         * Throttle function
         */
        throttle: function(func, limit) {
            var inThrottle;
            return function() {
                var args = arguments;
                var context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(function() { inThrottle = false; }, limit);
                }
            };
        }
    };
    
})(jQuery);