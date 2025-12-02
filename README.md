# Data Feed Importer

A scalable, production-ready CSV data import system built with PHP 8.4.

## Features

- **Batch Processing** — Memory-efficient handling of large files via configurable batch sizes
- **GTIN Validation** — Full GTIN-13 checksum verification plus format checks for GTIN-8/12/14
- **GTIN Lenient Mode** — Optional flag to treat checksum issues as warnings for legacy/demo feeds
- **Financial Precision** — Prices stored as DECIMAL, normalized in the mapper layer
- **Idempotent Imports** — Safe to re-run; uses upsert strategy keyed on GTIN
- **Dry-Run Mode** — Validate data without touching the database
- **Continue on Error** — One bad row doesn't stop the entire import; errors logged with line numbers

## Quick Start

```bash
# Install dependencies (runs inside Docker)
make composer-install

# Run the test suite
make test

# Import a CSV file
make import FILE=/data/feed.csv

# Or with options
docker compose run --rm app import:feed /data/feed.csv --batch-size=200 --dry-run

# Run the sample demo (skips GTIN checksum for provided feed.csv)
make demo
```

## CLI Usage

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

## Exit Codes

| Code | Meaning |
|------|---------|
| 0 | Success — all records imported |
| 1 | Partial failure — some records skipped due to validation errors |
| 2 | Failed — no records imported or file not found |

## CSV Format

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

## Architecture

See [ARCHITECTURE.md](ARCHITECTURE.md) for detailed design decisions.

### Data Flow

```
CSV File → CsvDataReader → ItemMapper → ItemValidator → ItemRepository → MySQL
              (generic)      (domain)      (rules)        (persistence)
```

### Key Design Decisions

1. **league/csv for parsing** — Handles encoding, BOM, edge cases we'd have to solve ourselves
2. **Separation of concerns** — Reader (generic) → Mapper (domain) → Validator (rules)
3. **DECIMAL for money** — Float has precision issues; DECIMAL stores exact values
4. **Idempotent upserts** — INSERT ... ON DUPLICATE KEY UPDATE by GTIN
5. **Batch transactions** — Consistency without memory overhead
6. **Continue on errors** — Log and skip invalid rows, report at the end

## Development

```bash
# Open a shell in the app container
make shell

# Start MySQL (if not already running)
make docker-up

# Stop all services
make docker-down
```

## Requirements

- Docker and Docker Compose
- No local PHP installation needed — everything runs in containers


