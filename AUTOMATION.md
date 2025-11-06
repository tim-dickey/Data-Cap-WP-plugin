# Automation Documentation

## Overview
This document describes the comprehensive automation system implemented for the Visitor Contact Collector WordPress plugin.

## Automation Components

### 1. GitHub Actions Workflows

#### CI/CD Pipeline (`.github/workflows/ci-cd.yml`)
- **Triggers**: Push to main/develop, pull requests, manual dispatch
- **Jobs**:
  - **Code Quality Analysis**: Codacy integration, dependency installation
  - **PHP Tests**: Multi-version testing (PHP 7.4-8.2, WordPress 6.0-6.4)
  - **JavaScript Tests**: Node.js 16, 18, 20 compatibility
  - **Security Scan**: Trivy vulnerability scanning
  - **Build Plugin**: Asset compilation and package creation
  - **Deploy**: WordPress.org SVN deployment (on release commits)

#### Automated Testing (`.github/workflows/automated-testing.yml`)
- **Triggers**: Daily schedule (2 AM), manual dispatch with test type selection
- **Features**:
  - Automated code quality checks (PHPCS, PHPMD, ESLint)
  - Missing test file generation
  - Comprehensive test suite execution
  - Performance testing and reporting
  - Automatic issue creation on failure

### 2. Automation Scripts

#### Setup Script (`scripts/automation/setup-automation.sh`)
- Environment configuration
- WordPress test installation script creation
- Pre-commit hook setup
- Directory structure creation

#### Test Runner (`scripts/automation/run-tests.sh`)
- Codacy analysis integration
- PHP and JavaScript test execution
- Security scanning with Trivy
- Test report generation

#### WordPress Test Installation (`bin/install-wp-tests.sh`)
- WordPress core installation for testing
- Test suite setup
- Database configuration

### 3. Configuration Files

#### Package.json
- NPM scripts for build, test, and development
- Development dependencies (Jest, ESLint, Prettier, PostCSS)
- Jest configuration for JavaScript testing

#### Code Quality Tools
- **ESLint** (`.eslintrc.js`): JavaScript linting rules
- **Prettier** (`.prettierrc`): Code formatting configuration
- **PHPMD** (`phpmd.xml`): PHP Mess Detector rules
- **PostCSS** (`postcss.config.js`): CSS processing configuration

#### Distribution
- **`.distignore`**: Files to exclude from plugin distribution

## Usage Instructions

### Initial Setup
```bash
# Run the setup script (one-time setup)
bash scripts/automation/setup-automation.sh

# Install dependencies
composer install --dev
npm install
```

### Development Workflow
```bash
# Start development mode (builds assets and runs tests in watch mode)
npm run dev

# Run all tests manually
bash scripts/automation/run-tests.sh

# Build for production
npm run ci
```

### Testing Commands
```bash
# PHP Tests
vendor/bin/phpunit --configuration tests/phpunit.xml

# JavaScript Tests
npm test
npm run test:coverage

# Code Quality
npm run lint
vendor/bin/phpcs --standard=WordPress includes/
```

### Build Commands
```bash
# Build minified assets
npm run build

# Format code
npm run format
npm run lint:fix
```

## Automation Features

### Code Generation
- **Automatic Test Generation**: Creates missing test files for new classes
- **Asset Building**: Minifies CSS and JavaScript files
- **Package Creation**: Builds distribution-ready plugin ZIP

### Quality Assurance
- **Multi-Version Testing**: Tests across PHP and WordPress versions
- **Code Standards**: WordPress coding standards enforcement
- **Security Scanning**: Vulnerability detection with Trivy
- **Coverage Reporting**: Code coverage analysis for PHP and JavaScript

### Continuous Integration
- **Automated Testing**: Runs on every push and pull request
- **Parallel Execution**: PHP and JavaScript tests run simultaneously
- **Matrix Testing**: Tests multiple environment combinations
- **Artifact Creation**: Builds deployment-ready packages

### Deployment Automation
- **Release Detection**: Deploys when commit message contains `[release]`
- **WordPress.org Integration**: Automatic SVN deployment
- **Asset Optimization**: Minified and optimized assets only

## Configuration Requirements

### GitHub Secrets
Required for WordPress.org deployment:
- `SVN_USERNAME`: WordPress.org SVN username
- `SVN_PASSWORD`: WordPress.org SVN password

### Codacy Integration
- Set up Codacy project for code quality analysis
- Configure organization MCP settings in GitHub

### Pre-commit Hooks
Automatically installed by setup script:
- Codacy analysis on staged files
- Quick test execution
- Code quality validation

## Monitoring and Reporting

### Test Reports
- **Coverage Reports**: HTML and LCOV formats
- **Performance Reports**: Execution time analysis
- **Security Reports**: SARIF format for GitHub Security tab

### Failure Handling
- **Automatic Issue Creation**: Creates GitHub issues for test failures
- **Detailed Logging**: Comprehensive workflow logs
- **Email Notifications**: GitHub Actions notifications

## Best Practices

### Code Quality
- Follow WordPress coding standards
- Maintain high test coverage (>90%)
- Use semantic commit messages
- Keep dependencies updated

### Testing
- Write tests for all new functionality
- Include both positive and negative test cases
- Test error handling and edge cases
- Maintain test isolation

### Deployment
- Use feature branches for development
- Test thoroughly before merging to main
- Use `[release]` in commit messages for deployments
- Monitor deployment success

## Troubleshooting

### Common Issues
1. **Test Failures**: Check logs for specific error messages
2. **Build Failures**: Verify all dependencies are installed
3. **Deployment Issues**: Check GitHub secrets configuration
4. **Coverage Issues**: Ensure all code paths are tested

### Debugging
- Use verbose flags for detailed output
- Check individual test files for specific failures
- Review GitHub Actions logs for workflow issues
- Verify environment configuration

## Maintenance

### Regular Tasks
- Update dependencies monthly
- Review and update coding standards
- Monitor security vulnerabilities
- Update WordPress compatibility

### Updates
- Keep GitHub Actions updated to latest versions
- Update Node.js and PHP versions as needed
- Review and update ESLint/Prettier configurations
- Monitor WordPress API changes