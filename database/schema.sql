-- ============================================================
-- AI Digital Library — Complete Database Schema
-- Engine: MySQL 8.0+  |  Charset: utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS ai_library CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ai_library;

-- ─────────────────────────────────────────────────────────────
-- 1. USERS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE users (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    email         VARCHAR(180)  NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password      VARCHAR(255)  NOT NULL,
    role          ENUM('admin','user') NOT NULL DEFAULT 'user',
    avatar        VARCHAR(255)  NULL,
    bio           TEXT          NULL,
    remember_token VARCHAR(100) NULL,
    created_at    TIMESTAMP     NULL,
    updated_at    TIMESTAMP     NULL,
    INDEX idx_users_email (email),
    INDEX idx_users_role  (role)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 2. CATEGORIES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE categories (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    slug        VARCHAR(120)  NOT NULL UNIQUE,
    description TEXT          NULL,
    icon        VARCHAR(50)   NULL,   -- emoji or icon name
    created_at  TIMESTAMP     NULL,
    updated_at  TIMESTAMP     NULL,
    INDEX idx_categories_slug (slug)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 3. AUTHORS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE authors (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)  NOT NULL,
    slug        VARCHAR(170)  NOT NULL UNIQUE,
    bio         TEXT          NULL,
    photo       VARCHAR(255)  NULL,
    website     VARCHAR(255)  NULL,
    created_at  TIMESTAMP     NULL,
    updated_at  TIMESTAMP     NULL,
    INDEX idx_authors_slug (slug),
    FULLTEXT idx_ft_authors_name (name)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 4. BOOKS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE books (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id     BIGINT UNSIGNED NOT NULL,
    title           VARCHAR(255)  NOT NULL,
    slug            VARCHAR(280)  NOT NULL UNIQUE,
    isbn            VARCHAR(20)   NULL UNIQUE,
    description     TEXT          NULL,
    ai_summary      TEXT          NULL,          -- cached AI summary
    cover_image     VARCHAR(255)  NULL,
    publisher       VARCHAR(150)  NULL,
    published_year  YEAR          NULL,
    pages           SMALLINT UNSIGNED NULL,
    language        VARCHAR(10)   NOT NULL DEFAULT 'en',
    google_books_id VARCHAR(50)   NULL,
    open_library_id VARCHAR(50)   NULL,
    external_url    VARCHAR(255)  NULL,          -- legal link only
    keywords        TEXT          NULL,          -- comma-separated, for search
    avg_rating      DECIMAL(3,2)  NOT NULL DEFAULT 0.00,
    reviews_count   INT UNSIGNED  NOT NULL DEFAULT 0,
    views_count     INT UNSIGNED  NOT NULL DEFAULT 0,
    meta_title      VARCHAR(70)   NULL,
    meta_description VARCHAR(160) NULL,
    status          ENUM('active','draft','hidden') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP     NULL,
    updated_at      TIMESTAMP     NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_books_category    (category_id),
    INDEX idx_books_slug        (slug),
    INDEX idx_books_status      (status),
    INDEX idx_books_published   (published_year),
    INDEX idx_books_rating      (avg_rating),
    FULLTEXT idx_ft_books_search (title, description, keywords)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 5. BOOK_AUTHOR  (many-to-many pivot)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE book_author (
    book_id   BIGINT UNSIGNED NOT NULL,
    author_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (book_id, author_id),
    FOREIGN KEY (book_id)   REFERENCES books(id)   ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE CASCADE,
    INDEX idx_ba_author (author_id)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 6. REVIEWS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE reviews (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id    BIGINT UNSIGNED NOT NULL,
    user_id    BIGINT UNSIGNED NOT NULL,
    rating     TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    body       TEXT          NULL,
    approved   BOOLEAN       NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP     NULL,
    updated_at TIMESTAMP     NULL,
    FOREIGN KEY (book_id) REFERENCES books(id)  ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)  ON DELETE CASCADE,
    UNIQUE  KEY uq_review_user_book (user_id, book_id),
    INDEX idx_reviews_book     (book_id),
    INDEX idx_reviews_approved (approved)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 7. FAVORITES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE favorites (
    user_id    BIGINT UNSIGNED NOT NULL,
    book_id    BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP       NULL,
    PRIMARY KEY (user_id, book_id),
    FOREIGN KEY (user_id) REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id)  ON DELETE CASCADE,
    INDEX idx_favorites_book (book_id)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 8. READING_HISTORY
-- ─────────────────────────────────────────────────────────────
CREATE TABLE reading_history (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    BIGINT UNSIGNED NOT NULL,
    book_id    BIGINT UNSIGNED NOT NULL,
    progress   TINYINT UNSIGNED NOT NULL DEFAULT 0 CHECK (progress BETWEEN 0 AND 100),
    last_read  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id)  ON DELETE CASCADE,
    UNIQUE KEY uq_history_user_book (user_id, book_id),
    INDEX idx_history_user (user_id),
    INDEX idx_history_last (last_read)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 9. SEARCH_LOGS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE search_logs (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      BIGINT UNSIGNED NULL,
    query        VARCHAR(255) NOT NULL,
    results_count INT UNSIGNED NOT NULL DEFAULT 0,
    ip_address   VARCHAR(45)  NULL,
    user_agent   VARCHAR(255) NULL,
    created_at   TIMESTAMP    NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sl_query      (query(50)),
    INDEX idx_sl_created    (created_at),
    INDEX idx_sl_user       (user_id)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 10. BLOG_POSTS  (SEO content)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE blog_posts (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    author_user_id   BIGINT UNSIGNED NOT NULL,
    title            VARCHAR(200) NOT NULL,
    slug             VARCHAR(220) NOT NULL UNIQUE,
    excerpt          VARCHAR(400) NULL,
    body             LONGTEXT     NOT NULL,
    cover_image      VARCHAR(255) NULL,
    meta_title       VARCHAR(70)  NULL,
    meta_description VARCHAR(160) NULL,
    status           ENUM('published','draft') NOT NULL DEFAULT 'draft',
    published_at     TIMESTAMP    NULL,
    created_at       TIMESTAMP    NULL,
    updated_at       TIMESTAMP    NULL,
    FOREIGN KEY (author_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_blog_status    (status),
    INDEX idx_blog_published (published_at),
    INDEX idx_blog_slug      (slug),
    FULLTEXT idx_ft_blog     (title, excerpt, body)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- 11. PERSONAL_ACCESS_TOKENS  (Laravel Sanctum)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE personal_access_tokens (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id   BIGINT UNSIGNED NOT NULL,
    name           VARCHAR(255) NOT NULL,
    token          VARCHAR(64)  NOT NULL UNIQUE,
    abilities      TEXT         NULL,
    last_used_at   TIMESTAMP    NULL,
    expires_at     TIMESTAMP    NULL,
    created_at     TIMESTAMP    NULL,
    updated_at     TIMESTAMP    NULL,
    INDEX idx_pat_tokenable (tokenable_type, tokenable_id)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- Seed: Default admin user (password: admin123)
-- ─────────────────────────────────────────────────────────────
INSERT INTO users (name, email, password, role, created_at, updated_at)
VALUES ('Admin', 'admin@library.com',
        '$2y$12$placeholderHashReplaceWithBcrypt', 'admin',
        NOW(), NOW());

INSERT INTO categories (name, slug, description, created_at, updated_at) VALUES
('Fiction',           'fiction',           'Novels, short stories, and imaginative works', NOW(), NOW()),
('Non-Fiction',       'non-fiction',       'Factual books, biographies, and essays', NOW(), NOW()),
('Science & Tech',    'science-tech',      'STEM, programming, and scientific research', NOW(), NOW()),
('Self-Help',         'self-help',         'Personal development and mindset books', NOW(), NOW()),
('History',           'history',           'Historical accounts and narratives', NOW(), NOW()),
('Business',          'business',          'Entrepreneurship, finance, and management', NOW(), NOW()),
('Philosophy',        'philosophy',        'Ethics, logic, and worldviews', NOW(), NOW()),
('Children',          'children',          'Books for young readers', NOW(), NOW());
