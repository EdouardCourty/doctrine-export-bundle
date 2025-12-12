# AGENTS.md - Developer & AI Agent Guide

## Project Overview
`ecourty/doctrine-export-bundle` is a **Symfony bundle** that exports Doctrine entities to various formats (CSV, JSON, XML) using the Strategy pattern. It provides a clean API with streaming support for memory-efficient exports.

**Critical Context**: This bundle is designed to be **integrated into existing production applications**. It must be:
- **Non-invasive**: No side effects on the host application's state
- **Flexible**: Adapt to various use cases without imposing constraints
- **Safe**: Never modify or clear the EntityManager that belongs to the user's application

## Tech Stack
- PHP 8.3+ (enums, typed properties, named arguments)
- Symfony 7.0|8.0 (Framework Bundle, Property Accessor)
- Doctrine ORM 3.0|4.0
- PHPUnit 12+ for testing
- PHPStan Level 9 for static analysis
- PHP-CS-Fixer for code style

## Architecture & Patterns

### Strategy Pattern
Export formats are implemented as strategies:
- `ExportStrategyInterface` defines the contract
- Concrete implementations: `CsvExportStrategy`, `JsonExportStrategy`, `XmlExportStrategy`
- `ExportStrategyRegistry` manages strategies using Symfony's `!tagged_iterator` (tag: `doctrine_export.strategy`)

### Directory Structure
```
src/
├── Contract/          # Interfaces (DoctrineExporterInterface, ExportStrategyInterface)
├── Enum/              # Type-safe enums (ExportFormat)
├── Exception/         # Custom exceptions
├── Model/             # DTOs (ExportOptions)
├── Service/           # Core services (DoctrineExporter, ExportStrategyRegistry)
├── Strategy/          # Export format implementations
├── DependencyInjection/  # Symfony extension
└── Resources/config/  # Service configuration
```

### Key Components
- **DoctrineExporter**: Main service implementing `DoctrineExporterInterface`
- **ExportFormat enum**: Type-safe format specification with `getMimeType()` and `getExtension()`
- **ExportStrategyRegistry**: Auto-discovers strategies via Symfony autoconfiguration

## Development Guidelines

### Code Quality
```bash
composer qa          # Run all checks
composer cs-fix      # Fix code style (PSR-12)
composer phpstan     # Static analysis (level 9)
composer test        # Run PHPUnit tests
```

### Coding Standards
- PSR-12 code style enforced by PHP-CS-Fixer
- PHPStan level 9 - no `@phpstan-ignore` comments
- Type everything: properties, parameters, returns (no `mixed`)
- Use PHP 8.3+ features: enums, readonly properties, named arguments
- Document public APIs with PHPDoc (interfaces, public methods)
- No comments for obvious code; comment only complex logic

### Adding a New Export Format
1. Add case to `ExportFormat` enum with `getExtension()` and `getMimeType()`
2. Create strategy class implementing `ExportStrategyInterface`
3. Implement: `getFormat()`, `generateHeader()`, `formatRow()`, `generateFooter()`, `getFileExtension()`
4. Service auto-registered via `autoconfigure: true`
5. Add tests in `tests/Strategy/`

### Testing Rules
- Unit tests for all strategies and services
- Integration tests with in-memory SQLite database
- Mock Doctrine components in unit tests
- Test edge cases: empty results, special characters, null values
- Use data providers for format variations

### Memory Management
- Use generators (`exportToGenerator()`) for large datasets - provides **true streaming**
- **Always use `detach()` for exported entities** - safe and doesn't affect the user's EntityManager
- **NEVER use `clear()`** - this would clear ALL entities from the user's EntityManager, causing data loss and bugs
- The bundle uses `toIterable()` which streams entities **one by one** - no batching needed
- Entity detachment happens immediately after extraction
- No manual garbage collection needed - streaming architecture handles memory naturally

### Error Handling
- Use custom exceptions from `src/Exception/`
- Validate entity classes exist before export
- Check file write permissions
- Provide clear error messages with context

## Common Tasks

### Running Tests
```bash
composer test                    # All tests
vendor/bin/phpunit tests/Unit/   # Unit tests only
vendor/bin/phpunit --filter=Csv  # Specific test
```

### Debugging
- Enable Symfony debug mode in test app
- Check `var/cache/dev/App_KernelDevDebugContainer.xml` for service wiring
- Use `bin/console debug:container doctrine_export` to inspect services

### Performance
- Use `exportToGenerator()` for HTTP responses (streaming)
- Use `exportToFile()` for background jobs
- Avoid loading all entities in memory
- Profile with Blackfire/XDebug for large datasets

## Important Notes
- **This is a library/bundle for integration**: It will be used in existing production Symfony applications with their own business logic, EntityManager lifecycle, and transaction management
- **Never break BC**: This is a library, semver matters
- **No side effects**: Never modify global state, never clear EntityManager, never flush, never commit transactions
- **Type safety first**: Use enums, no string format names in code
- **Test everything**: Each strategy needs full test coverage
- **Document changes**: Update CHANGELOG.md with every PR
- **Follow Symfony best practices**: Use DI, avoid static, leverage framework features
- **Defensive coding**: Validate inputs, throw meaningful exceptions, assume the host application has complex state

## References
- Usage examples: `README.md`
- Symfony DI: https://symfony.com/doc/current/service_container.html
- Doctrine metadata: https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/metadata-drivers.html
