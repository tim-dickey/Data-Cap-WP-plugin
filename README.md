# Visitor Contact Collector

A comprehensive WordPress plugin for collecting visitor contact information with GDPR compliance and advanced admin features.

![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)

## 📋 Overview

The Visitor Contact Collector plugin provides a professional, GDPR-compliant solution for collecting visitor contact information on your WordPress website. Built with modern web standards and WordPress best practices, it offers a seamless experience for both site visitors and administrators.

## ✨ Features

### Core Functionality
- **Contact Form**: Clean, responsive contact form with real-time validation
- **Data Collection**: Captures full name, email address, and phone number
- **GDPR Compliance**: Built-in consent mechanism and privacy tools
- **Mobile Responsive**: Optimized for all device sizes
- **Accessibility**: WCAG compliant with proper ARIA labels and keyboard navigation

### Admin Features
- **Dashboard**: Comprehensive overview with contact statistics
- **Contact Management**: View, search, filter, and manage all contacts
- **Bulk Actions**: Delete multiple contacts simultaneously
- **Export Options**: CSV and JSON export with date filtering
- **GDPR Tools**: Export and delete user data on request
- **Email Notifications**: Automatic notifications for new submissions

### Integration Options
- **Shortcode Support**: `[visitor_contact_form]` for easy placement
- **Gutenberg Block**: Native WordPress block editor integration
- **Widget Ready**: Compatible with WordPress widget system
- **Theme Integration**: PHP function for template integration

## 🚀 Installation

### Automatic Installation
1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select the ZIP file
4. Activate the plugin after installation

### Manual Installation
1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin panel
3. Navigate to "Contact Collector" in the admin menu

## 📖 Usage

### Quick Start
Add the contact form to any post or page using the shortcode:
```
[visitor_contact_form]
```

### Gutenberg Block
1. In the block editor, click the "+" button
2. Search for "Contact Form"
3. Add the block to your content

### Template Integration
Add to your theme templates using PHP:
```php
<?php echo do_shortcode('[visitor_contact_form]'); ?>
```

### Customization Options
Navigate to **Contact Collector → Settings** to configure:
- Form appearance and styling
- Email notification settings
- GDPR compliance options
- Data retention policies

## 🔧 Configuration

### Form Settings
- **Required Fields**: Configure which fields are mandatory
- **Placeholder Text**: Customize field placeholders
- **Success Messages**: Set custom confirmation messages
- **Error Messages**: Define validation error text

### Email Notifications
- **Admin Notifications**: Get notified of new submissions
- **Auto-Responders**: Send confirmation emails to visitors
- **Email Templates**: Customize email content and formatting

### GDPR Compliance
- **Consent Checkbox**: Mandatory consent for data collection
- **Privacy Policy**: Link to your privacy policy
- **Data Retention**: Set automatic data cleanup periods
- **Data Export**: Tools for fulfilling data export requests

## 📊 Admin Dashboard

### Contact Overview
- Total contacts collected
- Recent submission trends
- Monthly/yearly statistics
- Export summary

### Contact Management
- Searchable contact list
- Sortable columns (name, email, date, etc.)
- Bulk action support
- Individual contact actions

### Export Features
- **CSV Export**: Spreadsheet-compatible format
- **JSON Export**: Developer-friendly data format
- **Date Filtering**: Export specific time periods
- **Custom Fields**: Choose which data to include

## 🛡️ Security & Privacy

### Security Features
- **Data Validation**: Server-side validation for all inputs
- **SQL Injection Protection**: Prepared statements for database queries
- **XSS Prevention**: Proper data sanitization and escaping
- **CSRF Protection**: WordPress nonce verification
- **Capability Checks**: Proper user permission validation

### GDPR Compliance
- **Lawful Basis**: Clear consent mechanism
- **Data Minimization**: Only collect necessary information
- **Right to Access**: Export personal data functionality
- **Right to Erasure**: Delete personal data on request
- **Data Portability**: Machine-readable export formats

## 🎨 Customization

### CSS Customization
The plugin includes CSS custom properties for easy theming:

```css
:root {
    --vcc-primary-color: #0073aa;
    --vcc-border-radius: 4px;
    --vcc-input-padding: 12px;
    --vcc-font-family: inherit;
}
```

### Hooks & Filters
Available WordPress hooks for developers:

#### Actions
- `vcc_contact_submitted` - Fired after successful form submission
- `vcc_contact_deleted` - Fired when a contact is deleted
- `vcc_data_exported` - Fired after data export

#### Filters
- `vcc_form_fields` - Modify form field configuration
- `vcc_validation_rules` - Customize validation rules
- `vcc_email_content` - Modify notification email content

## 📁 File Structure

```
visitor-contact-collector/
├── visitor-contact-collector.php     # Main plugin file
├── README.md                         # This file
├── includes/                         # Core plugin classes
│   ├── class-vcc-database.php       # Database operations
│   ├── class-vcc-admin.php          # Admin interface
│   ├── class-vcc-frontend.php       # Frontend functionality
│   ├── class-vcc-shortcode.php      # Shortcode & blocks
│   ├── class-vcc-export.php         # Export functionality
│   └── class-vcc-gdpr.php           # GDPR compliance
└── assets/                          # Static assets
    ├── css/                         # Stylesheets
    │   ├── frontend.css             # Frontend form styling
    │   └── admin.css                # Admin panel styling
    └── js/                          # JavaScript files
        ├── frontend.js              # Form interactions
        ├── admin.js                 # Admin functionality
        └── block-editor.js          # Gutenberg integration
```

## 🔧 Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **JavaScript**: Enabled for enhanced functionality

## 🤝 Contributing

We welcome contributions to improve the Visitor Contact Collector plugin:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature/new-feature`)
5. Create a Pull Request

### Development Setup
1. Clone the repository to your WordPress plugins directory
2. Ensure WordPress debugging is enabled
3. Follow WordPress coding standards
4. Test on multiple WordPress versions

## 📝 Changelog

### Version 1.0.0
- Initial release
- Contact form with validation
- Admin dashboard and management
- CSV/JSON export functionality
- GDPR compliance features
- Shortcode and Gutenberg block support
- Responsive design and accessibility features

## 🐛 Bug Reports

If you encounter any issues:

1. Check the [Issues](https://github.com/your-username/Data-Cap-WP-plugin/issues) page
2. Search for existing reports
3. Create a new issue with:
   - WordPress version
   - PHP version
   - Plugin version
   - Detailed description of the problem
   - Steps to reproduce

## 📞 Support

For support and questions:

- **Documentation**: Check this README and inline code comments
- **Issues**: Use the GitHub Issues page for bug reports
- **Contact**: Visit [yourwebsite.com](https://www.yourwebsite.com) for direct contact

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 🙏 Acknowledgments

- Built with WordPress best practices
- Follows WCAG accessibility guidelines
- Implements GDPR compliance requirements
- Uses modern CSS and JavaScript standards

---

**Made with ❤️ for WordPress**

For more information, visit [yourwebsite.com](https://www.yourwebsite.com)