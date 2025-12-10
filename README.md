# Doctrine Export Bundle

[![CI](https://github.com/ecourty/doctrine-export-bundle/workflows/CI/badge.svg)](https://github.com/ecourty/doctrine-export-bundle/actions)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue)](https://php.net)
[![Symfony Compatibility](https://img.shields.io/badge/symfony-7%20%7C%208-green)](https://symfony.com)

A flexible and extensible Symfony bundle for exporting Doctrine entities to various formats (CSV, JSON, XML).

**Compatible with Symfony 7.x and 8.x** 🎉

## 📖 Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Key Features](#-key-features)
- [Performance](#performance)
- [Usage](#usage)
  - [Basic Export to File](#basic-export-to-file)
  - [Streaming Export (Binary Response)](#streaming-export-binary-response)
- [Advanced Options](#advanced-options)
  - [Field Selection](#field-selection)
  - [Export Options](#export-options)
  - [Field Validation](#field-validation)
  - [Memory Management](#memory-management)
  - [Association Handling](#association-handling)
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
    ]
);
```

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

- PHP 8.1 or higher
- Symfony 7.0 or 8.0
- Doctrine ORM 2.10, 3.0, or 4.0

> **💡 Recommended**: Use Doctrine ORM 3.x for new projects. It's faster, actively maintained, and has better PHP 8.1+ support. ORM 2.x is in maintenance mode.

## License

MIT
