# Doctrine Export Bundle

[![CI](https://github.com/EdouardCourty/doctrine-export-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/EdouardCourty/doctrine-export-bundle/actions/workflows/ci.yml)

A flexible and extensible Symfony bundle for exporting Doctrine entities to various formats (CSV, JSON, XML, Google Sheets).

**Compatible with PHP 8.3+, Symfony 7.x/8.x, and Doctrine ORM 3.x/4.x** 🎉

## 📖 Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Key Features](#-key-features)
- [Performance](#performance)
- [Usage](#usage)
  - [Basic Export to File](#basic-export-to-file)
  - [Streaming Export (Binary Response)](#streaming-export-binary-response)
- [Advanced Options](#advanced-options)
  - [Custom Entity Processors](#custom-entity-processors)
  - [Field Selection](#field-selection)
  - [Export Options](#export-options)
  - [Field Validation](#field-validation)
  - [Memory Management](#memory-management)
  - [Association Handling](#association-handling)
  - [Events](#events)
- [Supported Formats](#supported-formats)
- [Google Sheets Export (Optional)](#google-sheets-export-optional)
- [Development](#development)
- [Requirements](#requirements)
- [License](#license)

## Installation

```bash
composer require ecourty/doctrine-export-bundle
```

If you're not using Symfony Flex, enable the bundle manually:

```php
// config/bundles.php
return [
    // ...
    Ecourty\DoctrineExportBundle\DoctrineExportBundle::class => ['all' => true],
];
```

## Quick Start

Export entities in a few lines:

```php
use Ecourty\DoctrineExportBundle\Contract\DoctrineExporterInterface;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;

// Inject the service
public function __construct(
    private DoctrineExporterInterface $exporter
) {}

// Export to CSV
$this->exporter->exportToFile(
    entityClass: User::class,
    format: ExportFormat::CSV,
    filePath: '/tmp/users.csv'
);

// Stream to browser
return new StreamedResponse(
    $this->exporter->exportToGenerator(User::class, ExportFormat::JSON),
    Response::HTTP_OK,
    ['Content-Type' => 'application/json']
);
```

That's it! 🚀

## ✨ Key Features

- **🎯 Field Selection** - Export only the fields you need
- **🔍 Advanced Filtering** - Filter by criteria, pagination, ordering
- **📦 Multiple Formats** - CSV, JSON, XML out of the box
- **☁️ Google Sheets Export** - Direct export to Google Sheets with auto-sharing (optional)
- **💾 Memory Efficient** - Streaming support via generators (< 5 MB for 100k entities)
- **⚡ High Performance** - 42,000 entities/second (JSON), linear O(n) scaling
- **🔌 Extensible** - Add custom formats with the Strategy pattern
- **🎯 Type-Safe** - PHP 8.1+ enums for format specification
- **🛡️ XML Native** - Uses XMLWriter for guaranteed valid XML

## Performance

Benchmarked with realistic dataset (10,000 entities, 15 fields):

| Format    | Time (10k entities) | Memory Usage | Throughput     |
|-----------|---------------------|--------------|----------------|
| CSV       | 0.274s              | 2.00 MB      | 36,496 ent/s   |
| JSON      | 0.238s              | < 0.1 MB     | 42,017 ent/s   |
| XML       | 0.314s              | < 0.1 MB     | 31,847 ent/s   |
| Generator | 0.305s              | < 0.1 MB     | 32,787 ent/s   |

*Tested: 10,000 entities × 15 fields = 150,000 data points*

**Linear scaling**: tested with 100,000 entities in ~2.5s with < 5 MB memory 🚀

## Usage

### Basic Export to File

```php
use Ecourty\DoctrineExportBundle\Contract\DoctrineExporterInterface;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;
use App\Entity\User;

class UserExportService
{
    public function __construct(
        private DoctrineExporterInterface $exporter
    ) {}

    public function exportActiveUsers(): void
    {
        $this->exporter->exportToFile(
            entityClass: User::class,
            format: ExportFormat::CSV,
            filePath: '/tmp/active_users.csv',
            criteria: ['isActive' => true],
            limit: 1000,
            orderBy: ['createdAt' => 'DESC']
        );
    }
}
```

### Streaming Export (Binary Response)

Simply pass the generator to a `StreamedResponse`:

```php
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Route('/export/users')]
public function export(DoctrineExporterInterface $exporter): StreamedResponse
{
    $format = ExportFormat::CSV;
    
    return new StreamedResponse(
        $exporter->exportToGenerator(
            entityClass: User::class,
            format: $format,
            criteria: ['isActive' => true]
        ),
        200,
        [
            'Content-Type' => $format->getMimeType(),
            'Content-Disposition' => 'attachment; filename="users.' . $format->getExtension() . '"'
        ]
    );
}
```

### Events

The bundle dispatches events before and after each export, allowing you to hook into the export lifecycle for logging, monitoring, or custom logic.

**Events are optional** - if no event dispatcher is configured, exports work normally without events.

The bundle uses the **PSR-14 EventDispatcherInterface** (`Psr\EventDispatcher\EventDispatcherInterface`), making it compatible with any PSR-14 compliant event dispatcher, not just Symfony's.

#### Available Events

- **`PreExportEvent`** - Dispatched before export begins
- **`PostExportEvent`** - Dispatched after export completes (includes count and duration)

#### Example: Logging Exports

```php
use Ecourty\DoctrineExportBundle\Event\PostExportEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
public function onPostExport(PostExportEvent $event): void
{
    $this->logger->info('Export completed', [
        'entity' => $event->getEntityClass(),
        'count' => $event->getExportedCount(),
    ]);
}
```

#### Example: Performance Monitoring

```php
use Ecourty\DoctrineExportBundle\Event\PostExportEvent;

#[AsEventListener]
public function onPostExport(PostExportEvent $event): void
{
    $duration = $event->getDurationInSeconds();
    $throughput = $event->getExportedCount() / $duration;
    
    $this->metrics->gauge('export.duration', $duration);
    $this->metrics->gauge('export.throughput', $throughput);
}
```

#### Event Properties

**PreExportEvent:**
- `getEntityClass()` - Entity class being exported
- `getFormat()` - Export format (CSV, JSON, XML)
- `getCriteria()` - Filter criteria
- `getLimit()` - Result limit
- `getOffset()` - Result offset
- `getOrderBy()` - Sort order
- `getFields()` - Selected fields
- `getOptions()` - Export options

**PostExportEvent:**
- All PreExportEvent properties
- `getExportedCount()` - Number of entities exported
- `getDurationInSeconds()` - Export duration measured with microsecond precision (float)

## Supported Formats

| Format        | Extension | Description                      | Use Case                  |
|---------------|-----------|----------------------------------|---------------------------|
| CSV           | `.csv`    | Comma-separated values           | Spreadsheets, Excel       |
| JSON          | `.json`   | JSON format                      | APIs                      |
| XML           | `.xml`    | XML with configurable structure  | Legacy enterprise systems |
| Google Sheets | -         | Export directly to Google Sheets | Cloud-based collaboration |

### Format Examples

```php
// CSV
$exporter->exportToFile(User::class, ExportFormat::CSV, '/tmp/users.csv');

// JSON
$exporter->exportToFile(User::class, ExportFormat::JSON, '/tmp/users.json');

// XML
$exporter->exportToFile(User::class, ExportFormat::XML, '/tmp/users.xml');
```

### Export Output Examples

Here's what the exported data looks like for each format (using a sample `User` entity):

#### CSV Format

```csv
id,email,firstName,lastName,isActive,age,createdAt
1,john.doe@example.com,John,Doe,1,32,2024-01-15T10:30:00+00:00
2,jane.smith@example.com,Jane,Smith,1,28,2024-01-16T14:22:00+00:00
3,bob.johnson@example.com,Bob,Johnson,0,45,2024-01-17T09:15:00+00:00
```

#### JSON Format

```json
[
  {"id":1,"email":"john.doe@example.com","firstName":"John","lastName":"Doe","isActive":1,"age":32,"createdAt":"2024-01-15T10:30:00+00:00"},
  {"id":2,"email":"jane.smith@example.com","firstName":"Jane","lastName":"Smith","isActive":1,"age":28,"createdAt":"2024-01-16T14:22:00+00:00"},
  {"id":3,"email":"bob.johnson@example.com","firstName":"Bob","lastName":"Johnson","isActive":0,"age":45,"createdAt":"2024-01-17T09:15:00+00:00"}
]
```

#### XML Format

```xml
<?xml version="1.0" encoding="UTF-8"?>
<data>
  <item><id>1</id><email>john.doe@example.com</email><firstName>John</firstName><lastName>Doe</lastName><isActive>1</isActive><age>32</age><createdAt>2024-01-15T10:30:00+00:00</createdAt></item>
  <item><id>2</id><email>jane.smith@example.com</email><firstName>Jane</firstName><lastName>Smith</lastName><isActive>1</isActive><age>28</age><createdAt>2024-01-16T14:22:00+00:00</createdAt></item>
  <item><id>3</id><email>bob.johnson@example.com</email><firstName>Bob</firstName><lastName>Johnson</lastName><isActive>0</isActive><age>45</age><createdAt>2024-01-17T09:15:00+00:00</createdAt></item>
</data>
```

## Google Sheets Export (Optional)

Export directly to Google Sheets with automatic spreadsheet creation and sharing.

### Installation

The `google/apiclient` package is required for Google Sheets integration:
```bash
composer require google/apiclient
```

### Configuration

**1. Create a Google Service Account (Google Workspace Only)**

⚠️ **Important**: Google Sheets export **only works with Google Workspace accounts** that have domain-wide delegation enabled. Service accounts **cannot** create files in personal Google Drive accounts.

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the **Google Sheets API** and **Google Drive API**
4. Create a **Service Account** with **domain-wide delegation** and download the JSON credentials file
5. In your Google Workspace Admin console, grant the service account domain-wide delegation with the following scopes:
   - `https://www.googleapis.com/auth/spreadsheets`
   - `https://www.googleapis.com/auth/drive.file`
6. Save the credentials file to your project (e.g., `config/google-credentials.json`)

**2. Configure the Bundle**

You can provide credentials in two ways:

**Option 1: File path** (recommended for local development):
```yaml
# config/packages/doctrine_export.yaml
doctrine_export:
  google_sheets:
    credentials_path: '%kernel.project_dir%/config/google-credentials.json'
    batch_size: 10000  # Optional: rows per batch (default: 10000)
```

**Option 2: JSON string** (recommended for production with secret managers):
```yaml
# config/packages/doctrine_export.yaml
doctrine_export:
  google_sheets:
    credentials_path: '%env(GOOGLE_SERVICE_ACCOUNT_JSON)%'
    batch_size: 10000  # Optional: rows per batch (default: 10000)
```

The bundle automatically detects whether `credentials_path` is a file path or a JSON string. This is useful when using secret managers (AWS Secrets Manager, Google Secret Manager, Vault, etc.) that store credentials as JSON strings instead of files.

### Usage

**⚠️ Important**: Service account with domain-wide delegation requires a **subject email** to impersonate. You must provide `OPTION_GOOGLE_SHEETS_SUBJECT_EMAIL` in all exports.

**Basic Export with Custom Title**:
```php
use Ecourty\DoctrineExportBundle\Contract\DoctrineExporterInterface;
use Ecourty\DoctrineExportBundle\Enum\ExportFormat;

$result = $exporter->exportToApi(
    entityClass: User::class,
    format: ExportFormat::GOOGLE_SHEETS,
    options: [
        DoctrineExporterInterface::OPTION_GOOGLE_SHEETS_SUBJECT_EMAIL => 'user@yourdomain.com', // REQUIRED
        DoctrineExporterInterface::OPTION_SPREADSHEET_TITLE => 'User Export 2024',
    ]
);

// Access export results
echo $result->url;                  // Spreadsheet URL
echo $result->exportedCount;        // Number of entities exported
echo $result->durationInSeconds;    // Export duration
```

**Export with Email Sharing**:
```php
// Automatically share the spreadsheet with an email address
$result = $exporter->exportToApi(
    entityClass: User::class,
    format: ExportFormat::GOOGLE_SHEETS,
    options: [
        DoctrineExporterInterface::OPTION_GOOGLE_SHEETS_SUBJECT_EMAIL => 'user@yourdomain.com', // REQUIRED
        DoctrineExporterInterface::OPTION_SPREADSHEET_TITLE => 'User Export 2024',
        DoctrineExporterInterface::OPTION_GOOGLE_SHEETS_SHARE_EMAIL => 'manager@company.com',
    ]
);

echo "Exported {$result->exportedCount} users to: {$result->url}";
echo "Shared with: manager@company.com";
```

**Export with Auto-Generated Title**:
```php
// If no title provided, uses: "Export YYYY-MM-DD HH:MM:SS"
$result = $exporter->exportToApi(
    entityClass: Order::class,
    format: ExportFormat::GOOGLE_SHEETS,
    options: [
        DoctrineExporterInterface::OPTION_GOOGLE_SHEETS_SUBJECT_EMAIL => 'user@yourdomain.com', // REQUIRED
    ],
    criteria: ['status' => 'completed'],
    limit: 1000
);

echo "Exported {$result->exportedCount} orders to: {$result->url}";
```

### Important Notes

- **Subject email is REQUIRED** - You must provide `OPTION_GOOGLE_SHEETS_SUBJECT_EMAIL` for service account domain-wide delegation
- **Use `exportToApi()` method** - Google Sheets export requires the `exportToApi()` method, **NOT** `exportToFile()`
- **Automatic spreadsheet creation** - A new spreadsheet is created for each export using the email provided in `OPTION_GOOGLE_SHEETS_SUBJECT_EMAIL`
- **Email sharing** - Use `OPTION_GOOGLE_SHEETS_SHARE_EMAIL` to automatically share with specific users (writer permission)
- **Batch writing** - Large exports are written in configurable batches (default: 10,000 rows)

## Advanced Options

### Custom Entity Processors

Implement custom data transformations with entity processors. They allow you to modify exported data, add virtual fields, or apply business logic during export.

#### Creating a Custom Processor

```php
use Ecourty\DoctrineExportBundle\Contract\EntityProcessorInterface;

class EmailMaskingProcessor implements EntityProcessorInterface
{
    public function process(object $entity, array $data, array $options): array
    {
        // Mask email addresses
        if (isset($data['email'])) {
            $data['email'] = preg_replace('/(?<=.).(?=.*@)/', '*', $data['email']);
        }
        
        return $data;
    }
}
```

#### Using Processors

```php
$exporter->exportToFile(
    entityClass: User::class,
    format: ExportFormat::CSV,
    filePath: '/tmp/users.csv',
    processors: [new EmailMaskingProcessor()]
);
```

#### Adding Virtual Fields

```php
class UserVirtualFieldsProcessor implements EntityProcessorInterface
{
    public function process(object $entity, array $data, array $options): array
    {
        assert($entity instanceof User);
        
        // Add computed fields
        $data['displayName'] = $entity->getFirstName() . ' ' . $entity->getLastName();
        $data['ageCategory'] = $entity->getAge() >= 30 ? 'senior' : 'junior';
        
        return $data;
    }
}

// Export with virtual fields
$exporter->exportToFile(
    entityClass: User::class,
    format: ExportFormat::JSON,
    filePath: '/tmp/users.json',
    fields: ['firstName', 'displayName', 'ageCategory'], // Include virtual fields
    processors: [new UserVirtualFieldsProcessor()]
);
```

#### Chaining Multiple Processors

Processors are executed in order, allowing you to compose transformations:

```php
$exporter->exportToFile(
    entityClass: User::class,
    format: ExportFormat::CSV,
    filePath: '/tmp/users.csv',
    processors: [
        new EmailMaskingProcessor(),      // First: mask emails
        new UppercaseProcessor(),          // Then: uppercase all strings
        new UserVirtualFieldsProcessor(), // Finally: add virtual fields
    ]
);
```

#### Performance Optimization: Disable Default Processor

When using a fully custom processor that handles all data extraction, disable the default processor for better performance:

```php
class FullyCustomProcessor implements EntityProcessorInterface
{
    public function process(object $entity, array $data, array $options): array
    {
        // Handle ALL field extraction yourself
        $data['id'] = $entity->getId();
        $data['email'] = $entity->getEmail();
        // ... handle all fields
        
        return $data;
    }
}

$exporter->exportToFile(
    entityClass: User::class,
    format: ExportFormat::JSON,
    filePath: '/tmp/users.json',
    fields: ['id', 'email'],
    options: [
        // Skip default processor - custom processor handles everything
        DoctrineExporterInterface::OPTION_DISABLE_DEFAULT_PROCESSOR => true,
    ],
    processors: [new FullyCustomProcessor()]
);
```

**Note**: The default processor handles property access, associations, and data normalization. Only disable it when your custom processor fully replaces this functionality.

### Field Selection

You can specify which fields to export. If not specified, all entity fields are exported:

```php
// Export only specific fields
$exporter->exportToFile(
    entityClass: User::class,
    format: ExportFormat::CSV,
    filePath: '/tmp/users_minimal.csv',
    fields: ['id', 'email', 'firstName', 'lastName'] // Only these fields
);

// Export all fields (default)
$exporter->exportToFile(
    entityClass: User::class,
    format: ExportFormat::CSV,
    filePath: '/tmp/users_full.csv',
    fields: [] // Empty = all fields
);
```

**Note**: Field names are validated against the entity metadata. If you specify a field that doesn't exist, an `InvalidCriteriaException` will be thrown.

### Export Options

```php
use Ecourty\DoctrineExportBundle\Contract\DoctrineExporterInterface;

$exporter->exportToFile(
    entityClass: User::class,
    format: ExportFormat::CSV,
    filePath: '/tmp/users.csv',
    options: [
        // Boolean values as integers (default: true)
        DoctrineExporterInterface::OPTION_BOOLEAN_TO_INTEGER => false,
        
        // Custom datetime format (default: ATOM)
        DoctrineExporterInterface::OPTION_DATETIME_FORMAT => 'Y-m-d H:i:s',
        
        // Custom null value representation (default: null)
        DoctrineExporterInterface::OPTION_NULL_VALUE => '',
        
        // Strict field validation - throw exception if field doesn't exist (default: false)
        DoctrineExporterInterface::OPTION_STRICT_FIELDS => true,
        
        // Disable default processor for performance (default: false)
        // Only use when custom processor handles all data extraction
        DoctrineExporterInterface::OPTION_DISABLE_DEFAULT_PROCESSOR => true,
    ]
);
```

#### Available Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `OPTION_BOOLEAN_TO_INTEGER` | `bool` | `true` | Convert boolean values to integers (1/0) instead of strings ('true'/'false') |
| `OPTION_DATETIME_FORMAT` | `string` | `DateTimeInterface::ATOM` | PHP date format for DateTime fields (e.g., 'Y-m-d H:i:s', 'c') |
| `OPTION_NULL_VALUE` | `string\|int\|float` | `null` | Custom representation for null values (e.g., 'NULL', 'N/A', '') |
| `OPTION_STRICT_FIELDS` | `bool` | `false` | Throw exception if a requested field doesn't exist on the entity |
| `OPTION_DISABLE_DEFAULT_PROCESSOR` | `bool` | `false` | Skip default processor when using custom processors that handle all processing |

### Field Validation

The bundle automatically validates that fields in `criteria` and `orderBy` exist in the entity. If an invalid field is specified, an `InvalidCriteriaException` will be thrown with a helpful error message listing all available fields.

```php
// This will throw InvalidCriteriaException if 'nonExistentField' doesn't exist
$exporter->exportToFile(
    entityClass: User::class,
    format: ExportFormat::CSV,
    filePath: '/tmp/users.csv',
    criteria: ['nonExistentField' => 'value'] // ❌ Throws exception
);
```

### Memory Management

The bundle uses **streaming** and entity detachment for automatic memory efficiency:

```php
// Streaming export - automatically memory efficient
foreach ($exporter->exportToGenerator(User::class, ExportFormat::CSV) as $line) {
    echo $line; // Each entity is processed and immediately detached
    flush();
}
```

**How it works:**
- Uses Doctrine's `toIterable()` for **true streaming** (one entity at a time)
- Each entity is **detached** immediately after processing
- Detachment is **safe**: only affects memory tracking, not your database
- No `clear()`, no `flush()`, no batch management needed
- PHP's garbage collector handles memory automatically with the streaming approach

**Why no batching?**
This bundle doesn't need batch processing because:
- Entities are processed one-by-one (real streaming)
- Each entity is detached immediately (no accumulation)
- Generators ensure minimal memory footprint naturally
- No circular references are created

### Association Handling

**Doctrine associations are automatically exported as primary keys:**

```php
// Given entities:
// Article (id, title, author_id) -> ManyToOne -> User (id, name)
// Article (id, title) -> ManyToMany -> Tag (id, name)

$exporter->exportToFile(
    entityClass: Article::class,
    format: ExportFormat::JSON,
    filePath: '/tmp/articles.json',
    fields: ['title', 'author', 'tags']
);

// Output:
// [
//   {"title": "Article 1", "author": 42, "tags": [1, 2, 3]},
//   {"title": "Article 2", "author": 43, "tags": [2, 4]}
// ]
```

**How it works:**
- **ManyToOne / OneToOne**: Exported as the related entity's primary key (integer or string)
- **ManyToMany / OneToMany**: Exported as an array of primary keys `[1, 2, 3]`
- **Null associations**: Exported as `null`
- **Collections**: Empty collections exported as `[]`
- **No lazy loading issues**: Primary keys are extracted without triggering proxy initialization
- **Format-specific rendering**: JSON keeps arrays native, CSV/XML encode as JSON string

**Benefits:**
- Avoids N+1 queries and lazy loading issues
- Keeps export lightweight and predictable
- Easy to re-hydrate entities on import if needed
- Works seamlessly with all export formats

## Development

### Quality Assurance

Run all quality checks:
```bash
composer qa
```

Individual commands:
```bash
# Code style check
composer cs-check

# Fix code style
composer cs-fix

# Static analysis (PHPStan level 9)
composer phpstan

# Run tests
composer test

# Run all tests (including performance)
composer test:all

# Run only performance tests
composer test:performance
```

## Requirements

- PHP 8.3 or higher
- Symfony 7.0 or 8.0
- Doctrine ORM 3.0 or 4.0

## License

MIT
