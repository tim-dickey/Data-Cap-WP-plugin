# Visitor Contact Collector

A comprehensive WordPress plugin for collecting visitor contact information with GDPR compliance, advanced automation, and enterprise-grade features.

![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![Node.js](https://img.shields.io/badge/Node.js-16%2B-green.svg)
![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)
![CI/CD](https://img.shields.io/badge/CI%2FCD-GitHub%20Actions-orange.svg)
![Code Quality](https://img.shields.io/badge/Code%20Quality-Codacy-brightgreen.svg)

## 📋 Overview

The Visitor Contact Collector plugin provides a professional, enterprise-ready solution for collecting visitor contact information on WordPress websites. Built with modern development practices, comprehensive testing, and full automation support, it offers a robust foundation for contact management with GDPR compliance.

## ✨ Features

### Core Functionality
- **Contact Form**: Clean, responsive contact form with real-time validation
- **Data Collection**: Captures full name, email address, and phone number
- **GDPR Compliance**: Built-in consent mechanism and privacy tools
- **Mobile Responsive**: Optimized for all device sizes
- **Accessibility**: WCAG compliant with proper ARIA labels and keyboard navigation
- **Security**: Rate limiting, honeypot protection, and data encryption

### Admin Features
- **Dashboard**: Comprehensive overview with contact statistics and analytics
- **Contact Management**: View, search, filter, and manage all contacts
- **Bulk Actions**: Delete multiple contacts simultaneously
- **Export Options**: CSV and JSON export with date filtering
- **GDPR Tools**: Export and delete user data on request
- **Email Notifications**: Automatic notifications with SMTP support
- **Data Visualization**: Charts and graphs for contact analytics

### Integration & APIs
- **Shortcode Support**: `[visitor_contact_form]` for easy placement
- **Gutenberg Block**: Native WordPress block editor integration
- **Widget Ready**: Compatible with WordPress widget system
- **REST API**: Full API support for external integrations
- **Webhooks**: Real-time notifications to external services
- **Third-party APIs**: MailChimp, SendGrid, and reCAPTCHA integration

### Development & Automation
- **Environment Configuration**: Comprehensive .env support for all settings
- **CI/CD Pipeline**: Automated testing and deployment via GitHub Actions
- **Code Quality**: Integrated Codacy analysis and quality gates
- **Testing Framework**: Complete PHPUnit and Jest test suites
- **Performance Monitoring**: Built-in performance tracking and optimization
- **Security Scanning**: Automated vulnerability detection with Trivy

### Enterprise Features
- **Multi-environment Support**: Development, staging, and production configurations
- **Logging & Monitoring**: Comprehensive error tracking and analytics
- **Performance Optimization**: Asset minification and CDN support
- **Scalability**: Optimized for high-traffic websites
- **Backup & Recovery**: Data export and restoration capabilities
- **Compliance**: GDPR, CCPA, and privacy regulation compliance

## 🚀 Quick Start

### Installation
1. **Download**: Get the latest release from GitHub or WordPress.org
2. **Upload**: Install via WordPress admin or upload to `/wp-content/plugins/`
3. **Activate**: Enable the plugin in WordPress admin
4. **Configure**: Set up your environment and preferences

### Environment Setup
1. **Copy Environment Template**:
   ```bash
   cp .env.example .env
   ```

2. **Configure Settings**: Edit `.env` with your specific configuration:
   ```bash
   # Application Settings
   VCC_ENVIRONMENT=production
   VCC_DEBUG=false
   
   # Email Configuration
   VCC_SMTP_HOST=smtp.example.com
   VCC_SMTP_USERNAME=your_username
   VCC_SMTP_PASSWORD=your_password
   
   # Security Settings
   VCC_RATE_LIMIT_ENABLED=true
   VCC_ENCRYPTION_KEY=your_secret_key
   ```

3. **Test Configuration**: Use the built-in health check:
   ```php
   // Access via WordPress admin or API
   GET /wp-json/vcc/v1/health
   ```

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

## 🔧 Configuration & Environment Variables

### Core Application Settings
```bash
# Environment and debugging
VCC_ENVIRONMENT=production          # Environment: development, testing, production
VCC_DEBUG=false                     # Enable debug mode
VCC_LOG_LEVEL=info                  # Logging level: debug, info, warning, error
VCC_TEST_MODE=false                 # Enable test mode
```

### Database Configuration
```bash
# WordPress test environment
WP_TEST_DB_NAME=wordpress_test      # Test database name
WP_TEST_DB_USER=root                # Test database user
WP_TEST_DB_PASS=root                # Test database password
WP_TEST_DB_HOST=localhost           # Test database host
WP_TEST_VERSION=latest              # WordPress version for testing
```

### Email & SMTP Settings
```bash
# Email configuration
VCC_DEFAULT_ADMIN_EMAIL=admin@example.com    # Default admin email
VCC_EMAIL_NOTIFICATIONS=true                # Enable email notifications
VCC_SMTP_HOST=smtp.example.com              # SMTP server host
VCC_SMTP_PORT=587                           # SMTP server port
VCC_SMTP_USERNAME=your_username             # SMTP username
VCC_SMTP_PASSWORD=your_password             # SMTP password
VCC_SMTP_ENCRYPTION=tls                     # SMTP encryption: tls, ssl
```

### Security & Privacy
```bash
# Security settings
VCC_RATE_LIMIT_ENABLED=true         # Enable rate limiting
VCC_RATE_LIMIT_ATTEMPTS=5           # Rate limit attempts
VCC_RATE_LIMIT_WINDOW=900           # Rate limit window (seconds)
VCC_HONEYPOT_FIELD=website_url      # Honeypot field name
VCC_ENCRYPTION_KEY=your_secret_key  # Encryption key for sensitive data

# GDPR compliance
VCC_DATA_RETENTION_DAYS=730         # Data retention period
VCC_AUTO_CLEANUP=true               # Enable automatic cleanup
VCC_PRIVACY_POLICY_URL=/privacy     # Privacy policy URL
VCC_COOKIE_CONSENT=false            # Enable cookie consent
```

### API Integrations
```bash
# Third-party services
VCC_MAILCHIMP_API_KEY=your_key      # MailChimp API key
VCC_MAILCHIMP_LIST_ID=your_list     # MailChimp list ID
VCC_SENDGRID_API_KEY=your_key       # SendGrid API key
VCC_RECAPTCHA_SITE_KEY=your_key     # reCAPTCHA site key
VCC_RECAPTCHA_SECRET_KEY=your_key   # reCAPTCHA secret key
VCC_WEBHOOK_URL=https://example.com # Webhook URL
VCC_WEBHOOK_SECRET=your_secret      # Webhook secret
```

### Performance & Assets
```bash
# Asset optimization
VCC_USE_CDN=false                   # Use CDN for assets
VCC_CDN_URL=https://cdn.example.com # CDN base URL
VCC_MINIFY_CSS=true                 # Minify CSS files
VCC_MINIFY_JS=true                  # Minify JavaScript files
VCC_COMPRESS_IMAGES=true            # Compress images
```

### Monitoring & Analytics
```bash
# Monitoring
VCC_ERROR_REPORTING=true            # Enable error reporting
VCC_LOG_ERRORS=true                 # Log errors to file
VCC_GOOGLE_ANALYTICS_ID=GA-XXXXX    # Google Analytics tracking ID
VCC_TRACKING_ENABLED=false          # Enable user tracking
VCC_HEALTH_CHECK_ENABLED=true       # Enable health checks
```

For a complete list of all 50+ environment variables, see [ENVIRONMENT_INTEGRATION.md](ENVIRONMENT_INTEGRATION.md).

## 🏗️ Development & Automation

### CI/CD Pipeline
The plugin includes a comprehensive CI/CD pipeline with GitHub Actions:

```yaml
# Automated workflow includes:
- Multi-version PHP testing (7.4, 8.0, 8.1, 8.2)
- Multi-version WordPress testing (6.0-6.4)
- JavaScript testing with Jest
- Code quality analysis with Codacy
- Security scanning with Trivy
- Automated deployment to WordPress.org
```

### Testing Framework
```bash
# Run all tests
npm run ci

# PHP unit tests
vendor/bin/phpunit

# JavaScript tests
npm test

# Code coverage
npm run test:coverage

# Security scan
trivy fs .
```

### Development Setup
1. **Clone Repository**:
   ```bash
   git clone https://github.com/your-username/Data-Cap-WP-plugin.git
   cd Data-Cap-WP-plugin
   ```

2. **Install Dependencies**:
   ```bash
   # PHP dependencies (if using Composer)
   composer install
   
   # Node.js dependencies
   npm install
   ```

3. **Setup Environment**:
   ```bash
   # Copy environment template
   cp .env.example .env
   
   # Setup automation
   bash scripts/automation/setup-automation.sh
   ```

4. **Run Tests**:
   ```bash
   # Automated test suite
   bash scripts/automation/run-tests.sh
   ```

### Code Quality
- **PHP CodeSniffer**: WordPress coding standards
- **ESLint**: JavaScript linting and formatting
- **Prettier**: Code formatting
- **PHPStan**: Static analysis
- **Codacy**: Automated code review

### Pre-commit Hooks
Automated checks before each commit:
- Code formatting
- Lint validation
- Unit tests
- Security scanning

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

## 📁 Project Structure

```
visitor-contact-collector/
├── visitor-contact-collector.php     # Main plugin file
├── README.md                         # Project documentation
├── ENVIRONMENT_INTEGRATION.md        # Environment configuration guide
├── AUTOMATION.md                     # Automation documentation
├── .env.example                      # Environment template
├── .env                             # Local environment config
├── package.json                     # Node.js dependencies & scripts
├── composer.json                    # PHP dependencies (optional)
├── phpunit.xml                      # PHPUnit configuration
├── .eslintrc.js                     # ESLint configuration
├── .prettierrc                      # Prettier configuration
├── .distignore                      # Distribution ignore file
├── includes/                        # Core plugin classes
│   ├── class-vcc-config.php        # Environment configuration
│   ├── class-vcc-database.php      # Database operations
│   ├── class-vcc-admin.php         # Admin interface
│   ├── class-vcc-frontend.php      # Frontend functionality
│   ├── class-vcc-shortcode.php     # Shortcode & blocks
│   ├── class-vcc-export.php        # Export functionality
│   └── class-vcc-gdpr.php          # GDPR compliance
├── assets/                          # Static assets
│   ├── css/                         # Stylesheets
│   │   ├── frontend.css             # Frontend form styling
│   │   └── admin.css                # Admin panel styling
│   └── js/                          # JavaScript files
│       ├── frontend.js              # Form interactions
│       ├── admin.js                 # Admin functionality
│       └── block-editor.js          # Gutenberg integration
├── tests/                           # Test suites
│   ├── phpunit.xml                  # PHPUnit configuration
│   ├── bootstrap.php                # Test bootstrap
│   ├── unit/                        # PHP unit tests
│   ├── integration/                 # PHP integration tests
│   └── js/                          # JavaScript tests
│       ├── setup.js                 # Jest setup
│       ├── env.setup.js             # Environment setup
│       ├── frontend.test.js         # Frontend tests
│       └── admin.test.js            # Admin tests
├── .github/                         # GitHub configuration
│   └── workflows/                   # GitHub Actions
│       ├── ci-cd.yml                # Main CI/CD pipeline
│       └── automated-testing.yml    # Scheduled testing
├── scripts/                         # Automation scripts
│   └── automation/                  # Automation tools
│       ├── setup-automation.sh      # Environment setup
│       └── run-tests.sh             # Test runner
├── bin/                             # Binary scripts
│   └── install-wp-tests.sh          # WordPress test installer
└── coverage/                        # Test coverage reports
    ├── html/                        # HTML coverage reports
    └── lcov.info                    # Coverage data
```

## 🔧 Requirements

### System Requirements
- **WordPress**: 6.0 or higher
- **PHP**: 7.4 or higher (8.2 recommended)
- **MySQL**: 5.7 or higher (8.0 recommended)
- **Node.js**: 16+ (for development)
- **Memory**: 128MB minimum (256MB recommended)

### Browser Support
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### Development Requirements
- **Composer**: For PHP dependency management (optional)
- **NPM**: For JavaScript dependencies and build tools
- **Git**: For version control
- **GitHub CLI**: For automation features (optional)

## 🤝 Contributing

We welcome contributions to improve the Visitor Contact Collector plugin! Please read our contribution guidelines before getting started.

### Getting Started
1. **Fork the repository** on GitHub
2. **Clone your fork** locally:
   ```bash
   git clone https://github.com/your-username/Data-Cap-WP-plugin.git
   cd Data-Cap-WP-plugin
   ```
3. **Set up the development environment**:
   ```bash
   cp .env.example .env
   npm install
   bash scripts/automation/setup-automation.sh
   ```
4. **Create a feature branch**:
   ```bash
   git checkout -b feature/your-feature-name
   ```

### Development Workflow
1. **Make your changes** following WordPress coding standards
2. **Write tests** for new functionality
3. **Run the test suite**:
   ```bash
   npm run ci
   bash scripts/automation/run-tests.sh
   ```
4. **Commit your changes**:
   ```bash
   git add .
   git commit -m "feat: add your feature description"
   ```
5. **Push to your fork** and create a Pull Request

### Coding Standards
- **PHP**: Follow WordPress Coding Standards (WPCS)
- **JavaScript**: Use ESLint configuration provided
- **CSS**: Follow BEM methodology where applicable
- **Documentation**: Update README and inline comments
- **Testing**: Maintain 80%+ code coverage

### Pull Request Guidelines
- **Clear Description**: Explain what changes were made and why
- **Tests**: Include tests for new functionality
- **Documentation**: Update relevant documentation
- **Changelog**: Add entry to CHANGELOG.md
- **No Breaking Changes**: Unless it's a major version update

### Reporting Issues
When reporting bugs or requesting features:

1. **Search existing issues** first
2. **Use issue templates** provided
3. **Provide detailed information**:
   - WordPress version
   - PHP version
   - Plugin version
   - Steps to reproduce
   - Expected vs. actual behavior
   - Screenshots (if applicable)

### Development Resources
- **WordPress Codex**: https://codex.wordpress.org/
- **PHP Standards**: https://www.php-fig.org/psr/
- **Testing Documentation**: See `tests/README.md`
- **Environment Setup**: See `ENVIRONMENT_INTEGRATION.md`

## 📝 Changelog

### Version 1.0.0 (November 2025)
#### ✨ New Features
- **Core Functionality**: Complete contact form with validation and GDPR compliance
- **Environment Configuration**: Comprehensive .env support with 50+ configuration options
- **CI/CD Pipeline**: Automated testing and deployment via GitHub Actions
- **Testing Framework**: Complete PHPUnit and Jest test suites with 80%+ coverage
- **Admin Dashboard**: Full-featured contact management and analytics
- **Export System**: CSV and JSON export with filtering and date ranges
- **Security Features**: Rate limiting, honeypot protection, and data encryption
- **API Integration**: MailChimp, SendGrid, reCAPTCHA, and webhook support

#### 🔧 Technical Improvements
- **Code Quality**: Integrated Codacy analysis with automated quality gates
- **Performance**: Asset optimization, CDN support, and caching
- **Security**: Automated vulnerability scanning with Trivy
- **Automation**: Pre-commit hooks and automated deployment
- **Documentation**: Comprehensive documentation and inline code comments
- **Accessibility**: WCAG 2.1 AA compliance with screen reader support

#### 🛡️ Security & Compliance
- **GDPR Compliance**: Data export, deletion, and consent management
- **Privacy Controls**: Data retention policies and automatic cleanup
- **Security Scanning**: Automated vulnerability detection and reporting
- **Data Protection**: Encryption for sensitive data and secure transmission

#### 🔧 Developer Experience
- **Environment Management**: Flexible configuration for all environments
- **Testing Tools**: Comprehensive test coverage with automated reporting
- **Code Standards**: PHP CodeSniffer, ESLint, and Prettier integration
- **Documentation**: Detailed setup guides and API documentation

## � Support & Resources

### Documentation
- **Setup Guide**: See [ENVIRONMENT_INTEGRATION.md](ENVIRONMENT_INTEGRATION.md)
- **Automation Guide**: See [AUTOMATION.md](AUTOMATION.md)
- **API Documentation**: Available in `/docs` directory
- **Inline Documentation**: Comprehensive code comments throughout

### Getting Help
- **GitHub Issues**: [Report bugs or request features](https://github.com/tim-dickey/Data-Cap-WP-plugin/issues)
- **Discussions**: [Community discussions and Q&A](https://github.com/tim-dickey/Data-Cap-WP-plugin/discussions)
- **Wiki**: [Additional documentation and guides](https://github.com/tim-dickey/Data-Cap-WP-plugin/wiki)
- **Email Support**: Contact via [your website](https://www.yourwebsite.com)

### Community
- **Contributors**: See [CONTRIBUTORS.md](CONTRIBUTORS.md)
- **Code of Conduct**: See [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)
- **Security Policy**: See [SECURITY.md](SECURITY.md)

### Resources
- **WordPress Plugin Directory**: Coming soon
- **Demo Site**: [View live demo](https://demo.yourwebsite.com)
- **Video Tutorials**: [YouTube channel](https://youtube.com/your-channel)
- **Blog Posts**: [Latest updates and tutorials](https://blog.yourwebsite.com)

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

### Built With
- **WordPress**: The world's most popular CMS
- **Modern Web Standards**: HTML5, CSS3, ES6+
- **Testing Frameworks**: PHPUnit, Jest, and comprehensive test coverage
- **Automation Tools**: GitHub Actions, Codacy, and security scanning
- **Accessibility**: WCAG 2.1 AA compliance guidelines
- **Security**: Industry best practices and automated vulnerability scanning

### Special Thanks
- **WordPress Community**: For excellent documentation and best practices
- **Open Source Contributors**: For inspiration and code examples
- **Testing Community**: For comprehensive testing methodologies
- **Security Researchers**: For vulnerability disclosure and best practices

### Third-Party Libraries
- **PHPUnit**: PHP testing framework
- **Jest**: JavaScript testing framework
- **ESLint**: JavaScript linting
- **Prettier**: Code formatting
- **Trivy**: Security vulnerability scanner
- **Codacy**: Code quality analysis

---

**Made with ❤️ for the WordPress Community**

### Links
- **Project Homepage**: [https://github.com/tim-dickey/Data-Cap-WP-plugin](https://github.com/tim-dickey/Data-Cap-WP-plugin)
- **WordPress.org**: Plugin directory listing (coming soon)
- **Documentation**: [Complete documentation](https://github.com/tim-dickey/Data-Cap-WP-plugin/wiki)
- **Support**: [Get help and support](https://github.com/tim-dickey/Data-Cap-WP-plugin/issues)

*This plugin represents a commitment to quality, security, and user experience in WordPress plugin development.*