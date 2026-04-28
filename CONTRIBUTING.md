# Contributing to Data Cap WP Plugin

## Welcome!

Thank you for considering a contribution to the Data Cap WP Plugin! This document provides guidelines and instructions for contributing to the project.

## Ways to Contribute

There are many ways you can contribute to this project:

- **Report Bugs**: Help us identify issues and improve stability
- **Suggest Features**: Share ideas for new functionality or improvements
- **Write Code**: Fix bugs, implement features, or improve existing code
- **Improve Documentation**: Enhance README, inline comments, and guides
- **Test**: Help verify fixes and new features work correctly
- **Write Tests**: Add unit and integration tests to improve coverage

## Development Setup

### Prerequisites

- **PHP**: 7.4 or higher
- **Node.js**: 14.0 or higher
- **WordPress**: Latest development version
- **Git**: For version control

### Installation Steps

1. Clone the repository:
   ```bash
   git clone https://github.com/tim-dickey/Data-Cap-WP-plugin.git
   cd Data-Cap-WP-plugin
   ```

2. Install PHP dependencies (if using Composer):
   ```bash
   composer install
   ```

3. Install Node.js dependencies:
   ```bash
   npm install
   ```

4. Build assets:
   ```bash
   npm run build
   ```

5. Follow WordPress plugin development best practices and ensure the plugin is properly activated in a WordPress development environment.

## Submitting Pull Requests

### Before You Start

1. Fork the repository
2. Create a new branch for your feature/fix: `git checkout -b feature/your-feature-name`
3. Follow the code style guidelines below

### During Development

1. Make meaningful, atomic commits with clear messages
2. Keep pull requests focused on a single feature or fix
3. Add or update tests for your changes
4. Ensure all tests pass locally
5. Update documentation if needed

### Submitting Your PR

1. Push your changes to your fork
2. Create a pull request against the `main` branch
3. Fill out the PR template completely
4. Link any related issues
5. Ensure CI/CD checks pass
6. Be responsive to review feedback

### PR Requirements

- [ ] Self-review completed
- [ ] Comments added for complex logic
- [ ] Documentation updated (if applicable)
- [ ] No new warnings introduced
- [ ] Tests added or updated
- [ ] Code follows project style guidelines

## Reporting Bugs

Found a bug? Please report it on our [GitHub Issues](https://github.com/tim-dickey/Data-Cap-WP-plugin/issues) page.

When reporting a bug, include:
- Steps to reproduce the issue
- Expected behavior
- Actual behavior
- WordPress version
- Plugin version
- PHP version
- Any error messages or logs

## Code Style

### PHP

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Use meaningful variable and function names
- Add PHPDoc comments for functions and classes
- Maximum line length: 100 characters

### JavaScript/CSS

- Follow the included ESLint and Prettier configurations
- Run `npm run lint` to check code style
- Run `npm run format` to auto-format code
- Add comments for complex logic

## License Agreement

By contributing to this project, you agree that your contributions will be licensed under the same license as the project (typically GPL v2 or later for WordPress plugins). You represent that you have the right to grant this license and that your contributions do not violate any third-party rights.

## Questions?

If you have questions or need help:
1. Check the [README](README.md) for documentation
2. Review existing [issues](https://github.com/tim-dickey/Data-Cap-WP-plugin/issues) for similar questions
3. Open a new discussion or issue for your question

Thank you for contributing!
