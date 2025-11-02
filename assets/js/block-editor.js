/**
 * Visitor Contact Collector - Gutenberg Block Editor Integration
 * Provides modern block editor support for the contact form
 */

(function() {
    'use strict';
    
    const { registerBlockType } = wp.blocks;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, ToggleControl, SelectControl, TextControl, ColorPicker } = wp.components;
    const { Fragment, useState, useEffect } = wp.element;
    const { __ } = wp.i18n;
    
    /**
     * Register the Visitor Contact Collector block
     */
    registerBlockType('visitor-contact-collector/form', {
        title: __('Contact Form', 'visitor-contact-collector'),
        description: __('A form to collect visitor contact information with GDPR compliance.', 'visitor-contact-collector'),
        category: 'widgets',
        icon: {
            src: 'email-alt',
            foreground: '#2271b1'
        },
        keywords: [
            __('contact', 'visitor-contact-collector'),
            __('form', 'visitor-contact-collector'),
            __('lead', 'visitor-contact-collector'),
            __('email', 'visitor-contact-collector')
        ],
        supports: {
            align: ['left', 'center', 'right', 'wide', 'full'],
            anchor: true,
            spacing: {
                margin: true,
                padding: true
            },
            color: {
                background: true,
                text: true
            }
        },
        attributes: {
            title: {
                type: 'string',
                default: 'Get in Touch'
            },
            description: {
                type: 'string',
                default: 'Please fill out the form below and we\'ll get back to you soon.'
            },
            showTitle: {
                type: 'boolean',
                default: true
            },
            showDescription: {
                type: 'boolean',
                default: true
            },
            style: {
                type: 'string',
                default: 'default'
            },
            submitButtonText: {
                type: 'string',
                default: 'Submit'
            },
            successMessage: {
                type: 'string',
                default: 'Thank you! Your message has been received.'
            },
            redirectUrl: {
                type: 'string',
                default: ''
            },
            enablePhone: {
                type: 'boolean',
                default: true
            },
            requiredFields: {
                type: 'object',
                default: {
                    name: true,
                    email: true,
                    phone: false
                }
            },
            primaryColor: {
                type: 'string',
                default: '#2271b1'
            },
            backgroundColor: {
                type: 'string',
                default: '#ffffff'
            },
            textColor: {
                type: 'string',
                default: '#333333'
            }
        },
        
        /**
         * Edit function for the block editor
         */
        edit: function(props) {
            const { attributes, setAttributes, clientId } = props;
            const {
                title,
                description,
                showTitle,
                showDescription,
                style,
                submitButtonText,
                enablePhone,
                requiredFields,
                primaryColor,
                backgroundColor,
                textColor
            } = attributes;
            
            // State for form preview
            const [isPreviewMode, setIsPreviewMode] = useState(false);
            
            /**
             * Update required fields
             */
            const updateRequiredField = (field, value) => {
                setAttributes({
                    requiredFields: {
                        ...requiredFields,
                        [field]: value
                    }
                });
            };
            
            /**
             * Generate form preview HTML
             */
            const getFormPreview = () => {
                let formHtml = '<div class="vcc-form-container vcc-style-' + style + '" style="background-color: ' + backgroundColor + '; color: ' + textColor + ';">';
                
                if (showTitle && title) {
                    formHtml += '<h3 class="vcc-form-title">' + title + '</h3>';
                }
                
                if (showDescription && description) {
                    formHtml += '<p class="vcc-form-description">' + description + '</p>';
                }
                
                formHtml += '<form class="vcc-form" data-block-id="' + clientId + '">';
                
                // Name field
                formHtml += '<div class="vcc-form-group">';
                formHtml += '<label for="vcc_name_' + clientId + '">Full Name' + (requiredFields.name ? ' *' : '') + '</label>';
                formHtml += '<input type="text" id="vcc_name_' + clientId + '" name="vcc_name" ' + (requiredFields.name ? 'required' : '') + ' placeholder="Enter your full name">';
                formHtml += '</div>';
                
                // Email field
                formHtml += '<div class="vcc-form-group">';
                formHtml += '<label for="vcc_email_' + clientId + '">Email Address' + (requiredFields.email ? ' *' : '') + '</label>';
                formHtml += '<input type="email" id="vcc_email_' + clientId + '" name="vcc_email" ' + (requiredFields.email ? 'required' : '') + ' placeholder="Enter your email address">';
                formHtml += '</div>';
                
                // Phone field (if enabled)
                if (enablePhone) {
                    formHtml += '<div class="vcc-form-group">';
                    formHtml += '<label for="vcc_phone_' + clientId + '">Phone Number' + (requiredFields.phone ? ' *' : '') + '</label>';
                    formHtml += '<input type="tel" id="vcc_phone_' + clientId + '" name="vcc_phone" ' + (requiredFields.phone ? 'required' : '') + ' placeholder="Enter your phone number">';
                    formHtml += '</div>';
                }
                
                // Consent checkbox
                formHtml += '<div class="vcc-form-group vcc-consent-group">';
                formHtml += '<label class="vcc-checkbox-label">';
                formHtml += '<input type="checkbox" name="vcc_consent" required>';
                formHtml += '<span class="vcc-checkbox-text">I agree to the <a href="#" target="_blank">Privacy Policy</a> and consent to my data being processed.</span>';
                formHtml += '</label>';
                formHtml += '</div>';
                
                // Submit button
                formHtml += '<div class="vcc-form-group">';
                formHtml += '<button type="submit" class="vcc-submit-btn" style="background-color: ' + primaryColor + ';">' + submitButtonText + '</button>';
                formHtml += '</div>';
                
                formHtml += '</form>';
                formHtml += '</div>';
                
                return formHtml;
            };
            
            return wp.element.createElement(
                Fragment,
                null,
                
                // Inspector Controls (Sidebar)
                wp.element.createElement(
                    InspectorControls,
                    null,
                    
                    // Content Settings
                    wp.element.createElement(
                        PanelBody,
                        {
                            title: __('Content Settings', 'visitor-contact-collector'),
                            initialOpen: true
                        },
                        
                        wp.element.createElement(ToggleControl, {
                            label: __('Show Title', 'visitor-contact-collector'),
                            checked: showTitle,
                            onChange: (value) => setAttributes({ showTitle: value })
                        }),
                        
                        showTitle && wp.element.createElement(TextControl, {
                            label: __('Form Title', 'visitor-contact-collector'),
                            value: title,
                            onChange: (value) => setAttributes({ title: value }),
                            placeholder: __('Enter form title', 'visitor-contact-collector')
                        }),
                        
                        wp.element.createElement(ToggleControl, {
                            label: __('Show Description', 'visitor-contact-collector'),
                            checked: showDescription,
                            onChange: (value) => setAttributes({ showDescription: value })
                        }),
                        
                        showDescription && wp.element.createElement(TextControl, {
                            label: __('Form Description', 'visitor-contact-collector'),
                            value: description,
                            onChange: (value) => setAttributes({ description: value }),
                            placeholder: __('Enter form description', 'visitor-contact-collector')
                        }),
                        
                        wp.element.createElement(TextControl, {
                            label: __('Submit Button Text', 'visitor-contact-collector'),
                            value: submitButtonText,
                            onChange: (value) => setAttributes({ submitButtonText: value }),
                            placeholder: __('Submit', 'visitor-contact-collector')
                        })
                    ),
                    
                    // Form Fields Settings
                    wp.element.createElement(
                        PanelBody,
                        {
                            title: __('Form Fields', 'visitor-contact-collector'),
                            initialOpen: false
                        },
                        
                        wp.element.createElement(ToggleControl, {
                            label: __('Enable Phone Number Field', 'visitor-contact-collector'),
                            checked: enablePhone,
                            onChange: (value) => setAttributes({ enablePhone: value })
                        }),
                        
                        wp.element.createElement('h4', {
                            style: { marginTop: '20px', marginBottom: '10px' }
                        }, __('Required Fields', 'visitor-contact-collector')),
                        
                        wp.element.createElement(ToggleControl, {
                            label: __('Name Required', 'visitor-contact-collector'),
                            checked: requiredFields.name,
                            onChange: (value) => updateRequiredField('name', value)
                        }),
                        
                        wp.element.createElement(ToggleControl, {
                            label: __('Email Required', 'visitor-contact-collector'),
                            checked: requiredFields.email,
                            onChange: (value) => updateRequiredField('email', value)
                        }),
                        
                        enablePhone && wp.element.createElement(ToggleControl, {
                            label: __('Phone Required', 'visitor-contact-collector'),
                            checked: requiredFields.phone,
                            onChange: (value) => updateRequiredField('phone', value)
                        })
                    ),
                    
                    // Style Settings
                    wp.element.createElement(
                        PanelBody,
                        {
                            title: __('Style Settings', 'visitor-contact-collector'),
                            initialOpen: false
                        },
                        
                        wp.element.createElement(SelectControl, {
                            label: __('Form Style', 'visitor-contact-collector'),
                            value: style,
                            options: [
                                { label: __('Default', 'visitor-contact-collector'), value: 'default' },
                                { label: __('Modern', 'visitor-contact-collector'), value: 'modern' },
                                { label: __('Minimal', 'visitor-contact-collector'), value: 'minimal' },
                                { label: __('Classic', 'visitor-contact-collector'), value: 'classic' }
                            ],
                            onChange: (value) => setAttributes({ style: value })
                        }),
                        
                        wp.element.createElement('h4', {
                            style: { marginTop: '20px', marginBottom: '10px' }
                        }, __('Colors', 'visitor-contact-collector')),
                        
                        wp.element.createElement('div', {
                            style: { marginBottom: '15px' }
                        },
                            wp.element.createElement('label', {
                                style: { display: 'block', marginBottom: '5px', fontWeight: 'bold' }
                            }, __('Primary Color', 'visitor-contact-collector')),
                            wp.element.createElement(ColorPicker, {
                                color: primaryColor,
                                onChange: (value) => setAttributes({ primaryColor: value }),
                                disableAlpha: true
                            })
                        ),
                        
                        wp.element.createElement('div', {
                            style: { marginBottom: '15px' }
                        },
                            wp.element.createElement('label', {
                                style: { display: 'block', marginBottom: '5px', fontWeight: 'bold' }
                            }, __('Background Color', 'visitor-contact-collector')),
                            wp.element.createElement(ColorPicker, {
                                color: backgroundColor,
                                onChange: (value) => setAttributes({ backgroundColor: value }),
                                disableAlpha: true
                            })
                        ),
                        
                        wp.element.createElement('div', {
                            style: { marginBottom: '15px' }
                        },
                            wp.element.createElement('label', {
                                style: { display: 'block', marginBottom: '5px', fontWeight: 'bold' }
                            }, __('Text Color', 'visitor-contact-collector')),
                            wp.element.createElement(ColorPicker, {
                                color: textColor,
                                onChange: (value) => setAttributes({ textColor: value }),
                                disableAlpha: true
                            })
                        )
                    ),
                    
                    // Advanced Settings
                    wp.element.createElement(
                        PanelBody,
                        {
                            title: __('Advanced Settings', 'visitor-contact-collector'),
                            initialOpen: false
                        },
                        
                        wp.element.createElement(TextControl, {
                            label: __('Success Message', 'visitor-contact-collector'),
                            value: attributes.successMessage,
                            onChange: (value) => setAttributes({ successMessage: value }),
                            help: __('Message shown after successful form submission', 'visitor-contact-collector')
                        }),
                        
                        wp.element.createElement(TextControl, {
                            label: __('Redirect URL (Optional)', 'visitor-contact-collector'),
                            value: attributes.redirectUrl,
                            onChange: (value) => setAttributes({ redirectUrl: value }),
                            help: __('URL to redirect to after form submission (leave empty for no redirect)', 'visitor-contact-collector'),
                            placeholder: 'https://example.com/thank-you'
                        })
                    )
                ),
                
                // Block Preview/Editor
                wp.element.createElement(
                    'div',
                    {
                        className: 'vcc-block-editor'
                    },
                    
                    // Preview toggle
                    wp.element.createElement(
                        'div',
                        {
                            className: 'vcc-block-toolbar',
                            style: {
                                padding: '10px',
                                background: '#f0f0f0',
                                borderBottom: '1px solid #ddd',
                                display: 'flex',
                                justifyContent: 'space-between',
                                alignItems: 'center'
                            }
                        },
                        wp.element.createElement('span', {
                            style: { fontWeight: 'bold' }
                        }, __('Contact Form Block', 'visitor-contact-collector')),
                        
                        wp.element.createElement('button', {
                            type: 'button',
                            className: 'button button-small',
                            onClick: () => setIsPreviewMode(!isPreviewMode)
                        }, isPreviewMode ? __('Edit', 'visitor-contact-collector') : __('Preview', 'visitor-contact-collector'))
                    ),
                    
                    // Form preview or placeholder
                    isPreviewMode ? 
                        wp.element.createElement('div', {
                            className: 'vcc-block-preview',
                            dangerouslySetInnerHTML: { __html: getFormPreview() }
                        }) :
                        wp.element.createElement(
                            'div',
                            {
                                className: 'vcc-block-placeholder',
                                style: {
                                    padding: '40px',
                                    textAlign: 'center',
                                    background: '#f9f9f9',
                                    border: '2px dashed #ddd',
                                    borderRadius: '4px'
                                }
                            },
                            wp.element.createElement('div', {
                                className: 'dashicons dashicons-email-alt',
                                style: {
                                    fontSize: '48px',
                                    color: '#999',
                                    marginBottom: '10px'
                                }
                            }),
                            wp.element.createElement('h3', {
                                style: { color: '#666', margin: '0 0 10px 0' }
                            }, __('Contact Form', 'visitor-contact-collector')),
                            wp.element.createElement('p', {
                                style: { color: '#999', margin: '0' }
                            }, __('Configure your form settings in the sidebar, then click "Preview" to see how it will look.', 'visitor-contact-collector'))
                        )
                )
            );
        },
        
        /**
         * Save function - returns the shortcode
         */
        save: function(props) {
            const { attributes } = props;
            const shortcodeAtts = [];
            
            // Build shortcode attributes
            if (attributes.title !== 'Get in Touch') {
                shortcodeAtts.push('title="' + attributes.title + '"');
            }
            
            if (attributes.description !== 'Please fill out the form below and we\'ll get back to you soon.') {
                shortcodeAtts.push('description="' + attributes.description + '"');
            }
            
            if (!attributes.showTitle) {
                shortcodeAtts.push('show_title="false"');
            }
            
            if (!attributes.showDescription) {
                shortcodeAtts.push('show_description="false"');
            }
            
            if (attributes.style !== 'default') {
                shortcodeAtts.push('style="' + attributes.style + '"');
            }
            
            if (attributes.submitButtonText !== 'Submit') {
                shortcodeAtts.push('submit_text="' + attributes.submitButtonText + '"');
            }
            
            if (!attributes.enablePhone) {
                shortcodeAtts.push('enable_phone="false"');
            }
            
            if (attributes.successMessage !== 'Thank you! Your message has been received.') {
                shortcodeAtts.push('success_message="' + attributes.successMessage + '"');
            }
            
            if (attributes.redirectUrl) {
                shortcodeAtts.push('redirect_url="' + attributes.redirectUrl + '"');
            }
            
            if (attributes.primaryColor !== '#2271b1') {
                shortcodeAtts.push('primary_color="' + attributes.primaryColor + '"');
            }
            
            const shortcode = '[visitor_contact_form' + (shortcodeAtts.length ? ' ' + shortcodeAtts.join(' ') : '') + ']';
            
            return wp.element.createElement('div', {
                className: 'vcc-shortcode-wrapper'
            }, shortcode);
        }
    });
    
})();