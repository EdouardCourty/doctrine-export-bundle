# Doctrine Export Bundle

[![CI](https://github.com/EdouardCourty/doctrine-export-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/EdouardCourty/doctrine-export-bundle/actions/workflows/ci.yml)

A flexible and extensible Symfony bundle for exporting Doctrine entities to various formats (CSV, JSON, XML).

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

| Format | Extension | Description                     | Use Case                  |
|--------|-----------|---------------------------------|---------------------------|
| CSV    | `.csv`    | Comma-separated values          | Spreadsheets, Excel       |
| JSON   | `.json`   | JSON format                     | APIs                      |
| XML    | `.xml`    | XML with configurable structure | Legacy enterprise systems |

### Format Examples

```php
// CSV
$exporter->exportToFile(User::class, ExportFormat::CSV, '/tmp/users.csv');

// JSON
$exporter->exportToFile(User::class, ExportFormat::JSON, '/tmp/users.json');

// XML
$exporter->exportToFile(User::class, ExportFormat::XML, '/tmp/users.xml');
```

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
