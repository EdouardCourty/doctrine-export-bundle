# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Association support**: Doctrine associations are now automatically exported as primary keys
  - ManyToOne/OneToOne relations are exported as scalar primary key values
  - ManyToMany/OneToMany relations are exported as arrays of primary keys
  - Prevents lazy loading issues and N+1 queries during export
  - Works seamlessly with all export formats (CSV, JSON, XML)
- Integration tests for association export functionality
- Unit tests for association identifier extraction
- Initial release
- CSV export strategy
- JSON export strategy
- XML export strategy
- Streaming architecture with entity detachment for automatic memory efficiency
- True streaming support via generators (no batching needed)
- Type-safe ExportFormat enum
- Comprehensive documentation
- Unit tests for all components
- **Criteria and orderBy field validation** - Throws `InvalidCriteriaException` if invalid fields are specified
- **Field selection** - Choose which fields to export via `fields` parameter (optional, defaults to all fields)
- `InvalidCriteriaException` for better error handling

### Changed
- Export strategies now handle array values by encoding them as JSON (for CSV and XML formats)
- `ValueNormalizer` no longer processes association values (they're already extracted as primary keys)
- Updated test fixtures with `Article` and `Tag` entities to demonstrate association handling
- Enhanced error messages showing available fields when validation fails

### Technical Notes
- Uses `detach()` for memory management - safe and non-invasive
- Never uses `clear()` - bundle is designed to integrate into existing applications without side effects
- True streaming with `toIterable()` - processes one entity at a time, no batch accumulation
