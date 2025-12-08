# ImporterKit

[![Tests](https://github.com/omoustaouda/importerkit/actions/workflows/tests.yml/badge.svg)](https://github.com/omoustaouda/importerkit/actions/workflows/tests.yml)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-blue.svg)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A starting point for building data import pipelines in PHP 8.4. Designed with clean architecture, extensibility, and production-ready patterns.

![Demo run](docs/screenshot-demo_run.png)

## ✦ Why ImporterKit?

ImporterKit is a working building block meant to be forked and adapted for your own data import pipelines. Clone it, rename the entities, and you have a production-ready foundation.

This project embraces:

- **Clean Architecture** — Reader → Mapper → Validator → Repository pipeline
- **Extensibility** — Generic interfaces allowing CSV/JSON/XML/API sources
- **Modern PHP 8.4** — Readonly classes, enums, match expressions
- **Production Thinking** — Batch processing, idempotent imports, graceful error handling
- **Financial Precision** — DECIMAL storage for monetary values

## ⚡ Quick Start (using Docker)

```bash
# Clone
git clone https://github.com/omoustaouda/importerkit.git
cd importerkit

# Run tests
make test

# Run demo import
make demo

# Import a sepcific CSV file
make import FILE=/data/sample-feed.csv

# Or with options
docker compose run --rm app import:feed /data/sample-feed.csv --batch-size=200 --dry-run

# Open a shell in the container
make shell
```

## 📋 CLI Usage

```
bin/console import:feed <file> [options]

Arguments:
  file                  Path to the CSV file to import

Options:
  -b, --batch-size=100  Number of records to process per batch
      --dry-run         Validate data without inserting into database
      --skip-gtin-validation
                        Treat GTIN checksum errors as warnings (useful for demo data)
  -v                    Verbose output (shows validation errors)
```

### Exit Codes

| Code | Meaning |
|------|---------|
| 0 | Success — all records imported |
| 1 | Partial failure — some records skipped due to validation errors |
| 2 | Failed — no records imported or file not found |

## ⏹️ Architecture

### Data Flow

```
┌─────────────────┐
│   CSV File      │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  CsvDataReader  │  ← Wraps league/csv, returns raw arrays
│  (generic)      │    No knowledge of Item/columns
└────────┬────────┘
         │ iterable<array<string, string>>
         ▼
┌─────────────────┐
│   ItemMapper    │  ← Transforms array → Item
│  (domain-aware) │    Owns REQUIRED_COLUMNS, trimming, normalization
└────────┬────────┘
         │ Item
         ▼
┌─────────────────┐
│  ItemValidator  │  ← ALL validation: GTIN checksum, price, stock, URL
│  (domain rules) │    Returns ValidationResult
└────────┬────────┘
         │ Valid Item
         ▼
┌─────────────────┐
│ ItemRepository  │  ← Upsert to database
│  (persistence)  │    DECIMAL handling for price
└─────────────────┘
```

### Key Design Decisions

| Decision | Rationale |
|----------|-----------|
| **Separate Reader/Mapper/Validator** | Single responsibility, testable units |
| **DECIMAL for money** | Exact precision, no floating-point errors |
| **VARCHAR for GTIN** | Preserves leading zeros |
| **Batch upserts** | Memory efficient, idempotent |
| **Continue on errors** | Resilient imports, detailed logging |

See [ARCHITECTURE.md](ARCHITECTURE.md) for detailed documentation.

## 📄 CSV Format

The importer expects a CSV with the following columns:

```csv
gtin,language,title,picture,description,price,stock
4006381333931,en,Product Name,http://example.com/img.jpg,Description text,99.99,50
```

| Column | Type | Notes |
|--------|------|-------|
| gtin | string | 8–14 digits; GTIN-13 checksum validated |
| language | string | ISO 639-1 code (en, de, fr, it, es, nl, pl) |
| title | string | Required, non-empty |
| picture | string | Valid URL |
| description | string | Optional |
| price | decimal | Positive number, normalized to 2 decimal places |
| stock | integer | Non-negative |

## 🔧 Extending ImporterKit

### Add a New Data Source

1. Implement `DataReaderInterface`:

```php
class JsonDataReader implements DataReaderInterface
{
    public function read(): iterable
    {
        $data = json_decode(file_get_contents($this->path), true);
        foreach ($data as $index => $row) {
            yield $index => $row;
        }
    }
}
```

2. Register in your service configuration
3. Use the same Mapper → Validator → Repository pipeline

### Add Custom Validation Rules

Extend `ItemValidator` or create domain-specific validators:

```php
class CustomItemValidator extends ItemValidator
{
    protected function validateCustomRule(Item $item): ?ValidationError
    {
        // Your business logic
    }
}
```

## ▶️ Testing

```bash
# Run all tests
make test

# Run unit tests only
docker compose run --rm --entrypoint vendor/bin/phpunit app --testsuite=unit

# Run integration tests only
docker compose run --rm test --testsuite=integration
```

## 📁 Project Structure

```
src/
├── Command/           # CLI commands
├── DataReader/        # Data source abstractions
├── Mapper/            # Array → Entity transformation
├── Domain/            # Entities and validators
├── Repository/        # Database persistence
├── Service/           # Orchestration
├── Enum/              # Type-safe constants
├── DTO/               # Data transfer objects
└── Exception/         # Domain exceptions

tests/
├── Unit/              # Isolated component tests
├── Integration/       # Database tests
└── Fixtures/          # Test data files
```

## 📦 Requirements

- Docker and Docker Compose
- No local PHP installation needed — everything runs in containers

## 🔎 Troubleshooting

### MySQL "Access denied" error

If you see an error like:

```
SQLSTATE[HY000] [1045] Access denied for user 'app'@'...' (using password: YES)
```

This usually means the MySQL volume has stale credentials from a previous run. Fix it by removing the volumes and restarting:

```bash
docker compose down -v
make demo
```
