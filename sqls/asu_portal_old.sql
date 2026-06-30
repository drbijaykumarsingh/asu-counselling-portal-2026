-- ============================================================
--  Assam Skill University – Admission & Student Management Portal
--  Database: asu_portal
-- ============================================================

CREATE DATABASE IF NOT EXISTS asu_portal
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE asu_portal;

-- ------------------------------------------------------------
--  Table: users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)   NOT NULL UNIQUE,
    full_name     VARCHAR(120)  NOT NULL,
    email         VARCHAR(120)  DEFAULT NULL,
    password_hash VARCHAR(255)  NOT NULL,
    role          ENUM(
                    'super_admin',
                    'system_admin',
                    'counsellor',
                    'department',
                    'hod',
                    'finance'
                  ) NOT NULL,
    is_active     TINYINT(1)    NOT NULL DEFAULT 1,
    first_login   TINYINT(1)    NOT NULL DEFAULT 1,  -- 1 = must change password
    created_by    INT UNSIGNED  DEFAULT NULL,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_created_by FOREIGN KEY (created_by)
        REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Default Super Admin
--  Username : admin
--  Password : 123456  (bcrypt hash below)
--  first_login = 1 → will be forced to change password on login
-- ------------------------------------------------------------
INSERT INTO users (username, full_name, email, password_hash, role, is_active, first_login, created_by)
VALUES (
    'admin',
    'Super Administrator',
    'admin@assamskilluniversity.ac.in',
    '$2y$12$YourBcryptHashWillBeGeneratedByPHP',   -- placeholder; see config/create_admin.php
    'super_admin',
    1,
    1,
    NULL
);
