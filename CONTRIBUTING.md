# Contributing to AF-PWA

Thank you for considering contributing to AF-PWA! We welcome all types of contributions.

## 🤝 Code of Conduct

This project follows the [Contributor Covenant Code of Conduct](CODE_OF_CONDUCT.md). By participating, you agree to uphold this code.

## 🐛 Reporting Bugs

Before submitting a bug report:
1. Check if the issue already exists
2. Test with the latest version
3. Run `php artisan af-pwa:health --detailed` to gather system info

When reporting bugs, include:
- Laravel version
- PHP version
- Package version
- Steps to reproduce
- Expected vs actual behavior
- Error messages and stack traces

## 💡 Suggesting Features

We love feature suggestions! Please:
1. Check if the feature already exists or is planned
2. Open a discussion first for major features
3. Provide clear use cases and benefits
4. Consider backward compatibility

## 🔧 Development Setup

1. **Fork and clone the repository:**
   ```bash
   git clone https://github.com/your-username/af-pwa.git
   cd af-pwa
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Create a test Laravel project:**
   ```bash
   composer create-project laravel/laravel test-app
   cd test-app
   ```

4. **Link your local package:**
   ```json
   // In test-app/composer.json
   "repositories": [
       {
           "type": "path",
           "url": "../af-pwa"
       }
   ]
   ```

5. **Install your local package:**
   ```bash
   composer require artflow-studio/af-pwa:@dev
   ```

## 🧪 Testing

Run the test suite:
```bash
composer test
```

Run specific tests:
```bash
composer test -- --filter=TestName
```

Run tests with coverage:
```bash
composer test-coverage
```

## 📝 Code Standards

We follow PSR-12 coding standards. Before submitting:

1. **Run code style fixes:**
   ```bash
   composer fix-style
   ```

2. **Check code quality:**
   ```bash
   composer analyse
   ```

3. **Run all checks:**
   ```bash
   composer check
   ```

## 📋 Pull Request Process

1. **Create a feature branch:**
   ```bash
   git checkout -b feature/amazing-feature
   ```

2. **Make your changes:**
   - Write tests for new functionality
   - Update documentation if needed
   - Follow existing code patterns

3. **Test your changes:**
   ```bash
   composer test
   composer analyse
   composer fix-style
   ```

4. **Commit your changes:**
   ```bash
   git commit -m "Add amazing feature"
   ```

5. **Push and create PR:**
   ```bash
   git push origin feature/amazing-feature
   ```

### PR Requirements

- ✅ All tests pass
- ✅ Code follows PSR-12
- ✅ New features have tests
- ✅ Documentation updated
- ✅ No breaking changes (unless major version)

## 📚 Documentation

Help improve our documentation:

- Fix typos and unclear sections
- Add examples and use cases
- Improve code comments
- Create tutorials and guides

Documentation files:
- `README.md` - Main documentation
- `docs/` - Detailed guides
- Inline code comments
- PHPDoc blocks

## 🎨 Frontend Development

For JavaScript and CSS changes:

1. **Development mode:**
   ```bash
   npm run dev
   ```

2. **Watch for changes:**
   ```bash
   npm run watch
   ```

3. **Production build:**
   ```bash
   npm run prod
   ```

## 🏗️ Package Structure

```
src/
├── AfPwaServiceProvider.php    # Main service provider
├── AfPwaManager.php           # Core functionality
├── Facades/                   # Laravel facades
├── Console/                   # Artisan commands
└── Contracts/                 # Interfaces

resources/
├── js/                       # JavaScript files
├── css/                      # Stylesheets
└── views/                    # Blade templates

config/
└── af-pwa.php               # Configuration file

tests/
├── Feature/                 # Feature tests
└── Unit/                    # Unit tests
```

## 🔄 Release Process

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

## 📋 Issue Labels

- `bug` - Something isn't working
- `enhancement` - New feature request
- `documentation` - Documentation improvements
- `good first issue` - Good for newcomers
- `help wanted` - Extra attention needed
- `question` - Further information requested

## 🚀 Getting Help

- 💬 [GitHub Discussions](https://github.com/artflow-studio/af-pwa/discussions)
- 📧 Email: support@artflow-studio.com
- 📚 [Documentation](README.md)

## 🙏 Recognition

Contributors are recognized in:
- GitHub contributors page
- Release notes
- Documentation credits

Thank you for contributing to AF-PWA! 🎉
