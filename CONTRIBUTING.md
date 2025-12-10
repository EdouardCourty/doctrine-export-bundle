# Contributing to Doctrine Export Bundle

Thank you for considering contributing to the Doctrine Export Bundle!

## Development Setup

1. Clone the repository
```bash
git clone https://github.com/ecourty/doctrine-export-bundle.git
cd doctrine-export-bundle
```

2. Install dependencies
```bash
composer install
```

3. Run tests
```bash
vendor/bin/phpunit
```

## Coding Standards

- Follow PSR-12 coding style
- Use PHP 8.1+ features (enums, named arguments, etc.)
- Write comprehensive PHPDoc for public APIs
- Add type hints for all parameters and return values

## Testing

- Write unit tests for new features
- Ensure all tests pass before submitting PR
- Aim for high code coverage

## Submitting Changes

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Adding New Export Formats

To add a new export format:

1. Add the format case to `src/Enum/ExportFormat.php`
2. Create a new strategy class implementing `ExportStrategyInterface`
3. Add tests for the new strategy
4. Update README.md with usage examples
5. Update CHANGELOG.md

## Code Review Process

All submissions require review. We use GitHub pull requests for this purpose.

## Questions?

Open an issue for any questions or discussions.
