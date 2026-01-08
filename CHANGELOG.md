# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Google Sheets export support**: Export entities directly to Google Sheets via optional `google/apiclient` dependency
  - New `exportToApi()` method for API-based exports with clean `ExportResult` return type
  - `ExportResult` class to hold export metadata (URL, count, duration)
  - Extended `ExportStrategyInterface` with lifecycle methods: `prepare()`, `finalize()`, `supportsFileExport()`
  - New `OPTION_SPREADSHEET_TITLE` constant for custom spreadsheet naming
  - New `OPTION_GOOGLE_SHEETS_SHARE_EMAIL` constant for dynamic email sharing per export
  - Bundle configuration for Google Sheets credentials and batch size
  - Batch writing with configurable batch size (default 10,000 rows)
  - New exceptions: `GoogleSheetsException` and `UnsupportedOperationException`

### Changed
- **[BREAKING]** Removed `admin_email` and `required_email_domain` from bundle configuration
- Google Sheets email sharing is now dynamic via `OPTION_GOOGLE_SHEETS_SHARE_EMAIL` option instead of hardcoded config
- Removed unused email domain validation logic

### Removed
- **[BREAKING]** `GOOGLE_APPLICATION_CREDENTIALS` environment variable is no longer supported (use `credentials_path` config instead)

## [1.2.0] - 2025-12-14

### Added
- **Event system**: `PreExportEvent` and `PostExportEvent` for monitoring exports
  - `PostExportEvent::getDurationInSeconds()` provides microsecond-precision performance metrics
  - Events are optional and only dispatched when PSR-14 EventDispatcher is available
  - Perfect for logging, monitoring, and performance tracking

## [1.1.0] - 2025-12-12

### Added
- **Custom entity processors**: Process entities before export via `EntityProcessorInterface`
  - Transform, enrich or filter entity data using custom business logic
  - Inject processors via `entityProcessor` parameter in `ExportOptions`

## [1.0.0] - 2025-12-10

### Added
- Initial release
- CSV export strategy
- JSON export strategy
- XML export strategy
- Streaming architecture with entity detachment for automatic memory efficiency
- True streaming support via generators (no batching needed)
- Type-safe ExportFormat enum
- Comprehensive documentation
- Unit tests for all components
- **Criteria and orderBy field validation
- **Field selection** - Choose which fields to export via `fields` parameter (optional, defaults to all fields)
- **Association support**: Doctrine associations are automatically exported as primary keys
  - ManyToOne/OneToOne relations are exported as scalar primary key values
  - ManyToMany/OneToMany relations are exported as arrays of primary keys
  - Prevents lazy loading issues and N+1 queries during export
  - Works seamlessly with all export formats (CSV, JSON, XML)
- Integration tests for association export functionality
- Unit tests for association identifier extraction
