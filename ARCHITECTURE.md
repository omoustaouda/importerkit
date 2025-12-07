# ImporterKit Architecture

This document explains the key design choices made in ImporterKit, focusing on *why* each decision was taken rather than just *what* was implemented.

---

## 1. Modular Data Source Design

**Decision:** Abstract data reading behind `DataReaderInterface`.

**Rationale:**
- CSV today, but JSON/XML/API could be needed tomorrow
- The interface returns generic `array` data, not domain objects
- Domain mapping is handled separately by `ItemMapper`

**Implementation:**
- `DataReaderInterface` — generic contract returning `iterable<int, array<string, string>>`
- `CsvDataReader` — wraps league/csv, yields line numbers for error reporting
- `ItemMapper` — transforms raw arrays into `Item` entities

This separation means we can add a `JsonDataReader` or `ApiDataReader` without touching the mapper or validator.

---

## 2. Using league/csv Library

**Decision:** Use `league/csv` for CSV parsing instead of a custom implementation.

**Rationale:**
- Handles edge cases we'd have to solve ourselves:
  - BOM detection and handling
  - Character encoding issues
  - Malformed row handling
  - Memory-efficient iterators
- PHP 8.4 compatible (with `setEscape('')`)
- Well-maintained, ~200k weekly downloads, battle-tested

**What we handle ourselves:**
- Domain mapping (`ItemMapper`)
- Business validation (`ItemValidator`)
- Price normalization (738.7 → 738.70)
- GTIN checksum validation

**Trade-off:** External dependency, but saves significant development time and reduces edge-case bugs.

---

## 3. Separation: Reader vs Mapper vs Validator

**Decision:** Three distinct layers with single responsibilities.

```
CsvDataReader    → Generic CSV parsing, returns arrays
ItemMapper       → Array → Item transformation, owns REQUIRED_COLUMNS
ItemValidator    → Business rule validation, owns all validation logic
```

**Rationale:**
- Each layer is testable in isolation
- Reader can be swapped (JSON, XML) without touching domain logic
- Mapper can be reused with different readers
- Validator contains ALL business rules (single source of truth)

---

## 4. Money Storage Strategy

**Decision:** Store prices as `DECIMAL(15, 2)` in MySQL, normalize in mapper.

**Rationale:**
- E-commerce requires exact precision
- `FLOAT` has rounding errors: `19.99 + 0.01` might not equal `20.00`
- `DECIMAL` stores exact decimal values

**Implementation:**
```
CSV: "738.7" → ItemMapper normalizes → "738.70" → MySQL DECIMAL(738.70)
```

Price remains a string until DB insertion to preserve precision throughout the pipeline.

---

## 5. GTIN Validation (Single Location)

**Decision:** ALL GTIN validation lives in `ItemValidator` only.

**Rationale:**
- Single source of truth for validation rules
- No duplicate validation logic scattered across layers
- Easy to modify/extend rules in one place

**Implementation:**
- Accept GTIN-8, GTIN-12, GTIN-13, GTIN-14 (8–14 digits)
- Validate checksum for GTIN-13 (the most common format)
- Other lengths pass format check only

---

## 6. Batch Processing

**Decision:** Process in configurable batches (default 100 rows).

**Rationale:**
- Memory efficiency for large files (millions of rows possible)
- Transaction per batch for consistency
- Configurable via CLI option `--batch-size`

**Trade-off:** Larger batches mean faster imports but higher memory usage; smaller batches are safer but slower. Default of 100 balances both.

---

## 7. Idempotent Imports (Upsert Strategy)

**Decision:** Use `INSERT ... ON DUPLICATE KEY UPDATE` keyed on GTIN.

**Rationale:**
- **Safe to re-run:** Running the same import twice produces the same result
- **Updates existing products:** New data overwrites old without manual cleanup
- **GTIN is the natural unique identifier:** It's globally unique by design

**Implementation:**
```sql
INSERT INTO items (gtin, language, title, picture, description, price, stock)
VALUES (...)
ON DUPLICATE KEY UPDATE
    language = VALUES(language),
    title = VALUES(title),
    ...
    updated_at = CURRENT_TIMESTAMP
```

**Benefits:**
- No need to check "does this row exist?" before inserting
- No risk of duplicate entries
- `updated_at` automatically tracks when a product was last refreshed
- Partial imports can be resumed or retried without corruption

---

## 8. Continue on Validation Errors

**Decision:** Log and skip invalid rows, continue processing the rest.

**Rationale:**
- One bad row shouldn't stop 999,999 good rows
- Errors are logged with line numbers for debugging
- Final report shows success/failure counts
- Exit code indicates partial failure (code 1)

**Implementation:**
- Mapping errors → logged, row skipped
- Validation errors → logged with specific error type, row skipped
- Repository errors → exception bubbles up (these are infrastructure failures)

---

## 9. Dry-Run Mode

**Decision:** Provide a `--dry-run` flag that validates without persisting.

**Rationale:**
- Allows previewing what would be imported
- Useful for testing new feeds before going live
- Same validation path as real imports, just skips the DB write

---

## 10. GTIN Lenient Mode

**Decision:** Add a `--skip-gtin-validation` flag that downgrades checksum failures to warnings.

**Context:** Real-life feeds sometimes contain placeholder GTINs that fail checksum validation even though the rest of the row is valid.

**Rationale:**
- Keeps the default path strict — no flag, no import of invalid GTINs
- Helps demo data and legacy catalogs load without hand-editing thousands of rows
- Still runs every other validation rule (price, language, URL, stock, title)
- Emits a notice-level log per row so operators know a lenient import happened

**Safety Measures:**
- Flag must be explicitly set; there is no implicit auto-detect
- Stats still show how many rows were processed, so operators can compare strict vs lenient runs
- The demo docker service (`make demo`) runs with this flag enabled to showcase a successful import

---

## 11. PHP 8.4

**Decision:** Use PHP 8.4 over PHP 8.5.

**Context:** PHP 8.5 was released on November 20, 2025 — just 10 days before this project. After reviewing the new version, I decided to stick with PHP 8.4 for stability reasons.

**Why not PHP 8.5?**
- The ecosystem hasn't caught up yet: Docker images, Debian repos, and Packagist libraries are still stabilizing around 8.5
- Stability over bleeding edge: for a production-ready tool, ecosystem maturity matters
- Forward-compatible: this codebase has been tested on PHP 8.5 and runs without issues

**Migration path:** After 1–2 months, porting to PHP 8.5 should require only updating the `Dockerfile` and `composer.json` — no application code changes expected.

**Features used (available since 8.1–8.4):**
- `readonly` classes and properties — immutable domain objects
- Enums — type-safe status and error codes
- Match expressions — clean branching
- Constructor property promotion — less boilerplate
- Named arguments — readable service construction
- First-class callables — progress callbacks

**Rationale:**
- Modern features improve code quality and readability
- PHP 8.4 is the current stable release with long-term support

---

## Layer Responsibilities Summary

| Layer | Responsibility | Knows About |
|-------|----------------|-------------|
| `CsvDataReader` | Read CSV, yield raw arrays | File path, CSV structure |
| `ItemMapper` | Transform array → Item | Required columns, normalization |
| `ItemValidator` | Validate business rules | GTIN, price, stock, language, URL rules |
| `ItemRepository` | Persist to database | MySQL, transactions |
| `DataImporter` | Orchestrate the flow | All of the above |
| `ImportCommand` | CLI interface | User options, output formatting |

---

## Why These Trade-offs?

This architecture prioritizes:

1. **Clarity over cleverness** — A junior developer should understand any file in under 30 seconds
2. **Testability** — Each layer can be tested in isolation with simple mocks
3. **Extensibility** — New data sources or validation rules require minimal changes
4. **Production readiness** — Batch processing, error handling, and logging are built in
5. **Idempotency** — Imports are safe to retry, resume, or re-run

The goal is a system that's simple enough to reason about, robust enough to handle real-world data, and clean enough to extend when requirements change.

**Why `DataImporter`, not `ItemImporter`?**

The service coordinates a generic import pipeline (Reader → Mapper → Validator → Repository). Today those collaborators handle `Item` entities, but nothing in `DataImporter` ties it to that concept. Keeping the name generic matches the architecture and leaves room for plugging in a different mapper/validator pair (for example, importing availability feeds, or importing books) without renaming the orchestration layer.
