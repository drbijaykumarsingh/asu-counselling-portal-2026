-- ============================================================
--  ASU Portal – Admitted Students Table
--  Stores final admitted student records after counselling
-- ============================================================

USE asu_portal;

CREATE TABLE IF NOT EXISTS admitted_students (

    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- ── Student Identity (FK to students master table) ───────
    uan_no                  VARCHAR(50)     NOT NULL,
    application_no          VARCHAR(30)     DEFAULT NULL,

    -- ── Enrolment ────────────────────────────────────────────
    enrolment_no            VARCHAR(20)     DEFAULT NULL UNIQUE COMMENT 'e.g. IT26B0134',

    -- ── Personal (copied from students at time of admission) ─
    cname                   VARCHAR(120)    NOT NULL,
    fathername              VARCHAR(120)    DEFAULT NULL,
    mothername              VARCHAR(120)    DEFAULT NULL,
    dob                     VARCHAR(20)     DEFAULT NULL,
    gender                  VARCHAR(20)     DEFAULT NULL,
    mobile                  VARCHAR(20)     DEFAULT NULL,
    email                   VARCHAR(120)    DEFAULT NULL,

    -- ── Category at time of admission ────────────────────────
    category                VARCHAR(50)     DEFAULT NULL COMMENT 'Original category from application',
    admitted_category       VARCHAR(50)     NOT NULL    COMMENT 'Category under which admitted: UR/OBC-MOBC/SC/ST(H)/ST(P)/DA',
    ews                     VARCHAR(5)      DEFAULT NULL COMMENT 'YES/NO – applicable for UR only',
    obc_ncl                 VARCHAR(5)      DEFAULT NULL COMMENT 'YES/NO – applicable for OBC/MOBC only',

    -- ── Programme & Department ───────────────────────────────
    programme_type          VARCHAR(10)     NOT NULL    COMMENT 'B/L/I/D/M/F/P',
    department_code         VARCHAR(5)      NOT NULL    COMMENT 'IT/CE/ME/EE/EC/FT/MG/TT',
    department_name         VARCHAR(100)    NOT NULL,
    programme_code          VARCHAR(20)     NOT NULL    COMMENT 'e.g. IT26B01',
    programme_name          VARCHAR(150)    NOT NULL,

    -- ── Entrance Exam ─────────────────────────────────────────
    entrance_exam           VARCHAR(20)     DEFAULT NULL COMMENT 'CEE/JEE/ASUEE',
    ees                     VARCHAR(100)    DEFAULT NULL COMMENT 'Raw ees value from application',

    -- ── Admission Details ────────────────────────────────────
    academic_year           VARCHAR(10)     DEFAULT NULL,
    admission_date          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    admitted_by             VARCHAR(50)     NOT NULL    COMMENT 'Username of counsellor who admitted',
    admitted_by_user_id     INT UNSIGNED    DEFAULT NULL,

    -- ── Status ───────────────────────────────────────────────
    status                  TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Cancelled',
    remarks                 TEXT            DEFAULT NULL,

    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- ── Indexes ──────────────────────────────────────────────
    INDEX idx_uan_no            (uan_no),
    INDEX idx_enrolment_no      (enrolment_no),
    INDEX idx_application_no    (application_no),
    INDEX idx_admitted_category (admitted_category),
    INDEX idx_department_code   (department_code),
    INDEX idx_programme_code    (programme_code),
    INDEX idx_admission_date    (admission_date),
    INDEX idx_academic_year     (academic_year),

    -- ── Foreign Keys ─────────────────────────────────────────
    CONSTRAINT fk_admitted_uan      FOREIGN KEY (uan_no)
        REFERENCES students(uan_no) ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT fk_admitted_user     FOREIGN KEY (admitted_by_user_id)
        REFERENCES users(id)        ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Final admitted students after counselling process';
