# Contributing to NexusForms

Thank you for your interest in contributing to NexusForms! This document provides guidelines and instructions for contributing.

## Getting Started

1. Fork the repository
2. Clone your fork: `git clone https://github.com/your-username/nexusforms.git`
3. Create a new branch: `git checkout -b feature/your-feature-name`
4. Install dependencies: `npm install`
5. Make your changes
6. Run tests and linting
7. Commit your changes
8. Push to your fork
9. Create a Pull Request

## Development Setup

### Prerequisites
- Node.js 16+ and npm
- PHP 8.0+
- WordPress 6.0+
- MySQL 5.7+

### Installation
```bash
# Install npm dependencies
npm install

# Start development build (watch mode)
npm run start
```

## Coding Standards

### PHP
- Follow WordPress PHP Coding Standards
- Use PHP 8.0+ features (typed properties, named arguments, etc.)
- Document all classes and methods with PHPDoc
- Use strict types: `declare(strict_types=1);`

### JavaScript/React
- Follow WordPress JavaScript Coding Standards
- Use ESLint and Prettier
- Write functional components with hooks
- Use TypeScript where possible

### CSS
- Use BEM methodology for class names
- Mobile-first approach
- Support for dark mode and high contrast
- Follow WCAG 2.1 AA accessibility guidelines

## Pull Request Process

1. Update README.md with details of changes if needed
2. Update version numbers following SemVer
3. Ensure all tests pass
4. Request review from maintainers
5. Squash commits before merging

## Code Review

All submissions require review. We use GitHub pull requests for this purpose.

## Reporting Bugs

Use GitHub Issues to report bugs. Include:
- WordPress version
- PHP version
- Plugin version
- Steps to reproduce
- Expected behavior
- Actual behavior
- Screenshots if applicable

## Feature Requests

We welcome feature requests! Please:
- Check if the feature already exists or is planned
- Provide detailed description and use cases
- Explain why this feature would benefit users

## Testing

```bash
# Run JavaScript tests
npm run test

# Run linting
npm run lint:js
npm run lint:css

# Build production assets
npm run build
```

## License

By contributing, you agree that your contributions will be licensed under the GPL v2 or later license.
