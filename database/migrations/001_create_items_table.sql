-- Data Feed Importer Schema
-- Using DECIMAL for price to make sure we have financial precision

CREATE TABLE IF NOT EXISTS items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- GTIN as VARCHAR: preserves leading zeros, supports GTIN-8/12/13/14
    gtin VARCHAR(20) NOT NULL,

    -- ISO 639-1 language code
    language CHAR(2) NOT NULL,

    -- Product information
    title VARCHAR(255) NOT NULL,
    picture VARCHAR(500) NOT NULL,
    description TEXT,

    -- DECIMAL for exact monetary precision (not FLOAT!)
    -- 15 total digits, 2 after decimal point
    -- Max: 9,999,999,999,999.99
    price DECIMAL(15, 2) NOT NULL,

    -- Stock quantity (unsigned, no negative inventory)
    stock INT UNSIGNED NOT NULL DEFAULT 0,

    -- Audit timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- GTIN is the natural unique identifier
    UNIQUE KEY uk_gtin (gtin),

    -- Indexes for common query patterns
    INDEX idx_language (language),
    INDEX idx_title (title),
    INDEX idx_stock (stock)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Imported items from data feeds';

