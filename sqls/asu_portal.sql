-- ============================================================
--  Assam Skill University – Admission & Student Management Portal
--  Full Database Schema
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
    role          ENUM('super_admin','system_admin','counsellor','department','hod','finance') NOT NULL,
    is_active     TINYINT(1)    NOT NULL DEFAULT 1,
    first_login   TINYINT(1)    NOT NULL DEFAULT 1,
    created_by    INT UNSIGNED  DEFAULT NULL,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin (run config/create_admin.php to set proper bcrypt hash)
INSERT IGNORE INTO users (username, full_name, email, password_hash, role, is_active, first_login, created_by)
VALUES ('admin','Super Administrator','admin@assamskilluniversity.ac.in','PLACEHOLDER','super_admin',1,1,NULL);

-- ------------------------------------------------------------
--  Table: students  (128 columns – mirrors Excel upload)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS students (

    id                              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    original_id                     VARCHAR(20)     DEFAULT NULL,
    application_no                  VARCHAR(30)     NOT NULL UNIQUE,
    uan_no                          VARCHAR(50)     DEFAULT NULL,
    academic_year                   VARCHAR(10)     DEFAULT NULL,
    application_date                DATETIME        DEFAULT NULL,
    update_date                     DATETIME        DEFAULT NULL,
    status                          VARCHAR(10)     DEFAULT NULL,
    declaration                     VARCHAR(10)     DEFAULT NULL,

    -- Personal
    cname                           VARCHAR(120)    NOT NULL,
    dob                             VARCHAR(20)     DEFAULT NULL,
    age                             VARCHAR(50)     DEFAULT NULL,
    gender                          VARCHAR(20)     DEFAULT NULL,
    blood_group                     VARCHAR(10)     DEFAULT NULL,
    nationality                     VARCHAR(50)     DEFAULT NULL,
    aadhaar_no                      VARCHAR(20)     DEFAULT NULL,
    mobile                          VARCHAR(20)     DEFAULT NULL,
    telephone_no                    VARCHAR(20)     DEFAULT NULL,
    email                           VARCHAR(120)    DEFAULT NULL,
    identification_marks            TEXT            DEFAULT NULL,
    signature                       VARCHAR(255)    DEFAULT NULL,

    -- Address
    address_line1                   VARCHAR(255)    DEFAULT NULL,
    address_line2                   VARCHAR(255)    DEFAULT NULL,
    address_line3                   VARCHAR(255)    DEFAULT NULL,
    city                            VARCHAR(100)    DEFAULT NULL,
    district                        VARCHAR(100)    DEFAULT NULL,
    state                           VARCHAR(100)    DEFAULT NULL,
    pincode                         VARCHAR(10)     DEFAULT NULL,
    per_resident                    VARCHAR(10)     DEFAULT NULL,

    -- Family
    fathername                      VARCHAR(120)    DEFAULT NULL,
    mothername                      VARCHAR(120)    DEFAULT NULL,
    guardian_name                   VARCHAR(120)    DEFAULT NULL,
    guardian_contact_no             VARCHAR(20)     DEFAULT NULL,
    parents_contact_no              VARCHAR(20)     DEFAULT NULL,

    -- Category / Reservation
    category                        VARCHAR(50)     DEFAULT NULL,
    ews                             VARCHAR(10)     DEFAULT NULL,
    obc_ncl                         VARCHAR(10)     DEFAULT NULL,
    ph_disabled                     VARCHAR(10)     DEFAULT NULL,
    hostel_accommodation            VARCHAR(10)     DEFAULT NULL,

    -- Programme
    programme                       VARCHAR(150)    DEFAULT NULL,
    programme_id                    VARCHAR(20)     DEFAULT NULL,
    programme_name                  VARCHAR(150)    DEFAULT NULL,
    programme_type                  VARCHAR(50)     DEFAULT NULL,
    interested_in_btech             VARCHAR(10)     DEFAULT NULL,
    interested_in_mba               VARCHAR(10)     DEFAULT NULL,
    interested_in_mttm              VARCHAR(10)     DEFAULT NULL,
    interested_in_food_technology   VARCHAR(10)     DEFAULT NULL,
    appno                           VARCHAR(30)     DEFAULT NULL,
    programid                       VARCHAR(20)     DEFAULT NULL,
    programname                     VARCHAR(150)    DEFAULT NULL,

    -- Entrance exams
    ees                             VARCHAR(100)    DEFAULT NULL,
    gate_score                      VARCHAR(20)     DEFAULT NULL,
    gate_year                       VARCHAR(10)     DEFAULT NULL,
    cat_score                       VARCHAR(20)     DEFAULT NULL,
    cat_year                        VARCHAR(10)     DEFAULT NULL,
    gmat_score                      VARCHAR(20)     DEFAULT NULL,
    gmat_year                       VARCHAR(10)     DEFAULT NULL,
    mat_score                       VARCHAR(20)     DEFAULT NULL,
    mat_year                        VARCHAR(10)     DEFAULT NULL,
    nlm_score                       VARCHAR(20)     DEFAULT NULL,
    nlm_year                        VARCHAR(10)     DEFAULT NULL,

    -- HSLC (Class 10)
    hslc_bord                       VARCHAR(100)    DEFAULT NULL,
    hslc_other_board_text           VARCHAR(100)    DEFAULT NULL,
    hslc_name_of_institute          VARCHAR(200)    DEFAULT NULL,
    hslc_roll_no                    VARCHAR(50)     DEFAULT NULL,
    hslc_year_of_passing            VARCHAR(10)     DEFAULT NULL,
    hslc_marks_obtained             VARCHAR(20)     DEFAULT NULL,
    hslc_out_of                     VARCHAR(20)     DEFAULT NULL,
    hslc_percentage                 VARCHAR(20)     DEFAULT NULL,
    total_hslc_percentage           VARCHAR(20)     DEFAULT NULL,
    english_hslc_marks_obtained     VARCHAR(20)     DEFAULT NULL,
    english_hslc_out_of             VARCHAR(20)     DEFAULT NULL,
    english_hslc_percentage         VARCHAR(20)     DEFAULT NULL,
    maths_hslc_marks_obtained       VARCHAR(20)     DEFAULT NULL,
    maths_hslc_out_of               VARCHAR(20)     DEFAULT NULL,
    maths_hslc_percentage           VARCHAR(20)     DEFAULT NULL,
    science_hslc_marks_obtained     VARCHAR(20)     DEFAULT NULL,
    science_hslc_out_of             VARCHAR(20)     DEFAULT NULL,
    science_hslc_percentage         VARCHAR(20)     DEFAULT NULL,

    -- HSSLC (Class 12)
    hsslc_bord                      VARCHAR(100)    DEFAULT NULL,
    hsslc_other_board_text          VARCHAR(100)    DEFAULT NULL,
    hsslc_name_of_institute         VARCHAR(200)    DEFAULT NULL,
    hsslc_roll_no                   VARCHAR(50)     DEFAULT NULL,
    hsslc_stream                    VARCHAR(50)     DEFAULT NULL,
    hsslc_year_of_passing           VARCHAR(10)     DEFAULT NULL,
    hsslc_marks_obtained            VARCHAR(20)     DEFAULT NULL,
    hsslc_out_of                    VARCHAR(20)     DEFAULT NULL,
    hsslc_percentage                VARCHAR(20)     DEFAULT NULL,
    total_hsslc_percentage          VARCHAR(20)     DEFAULT NULL,
    english_hsslc_marks_obtained    VARCHAR(20)     DEFAULT NULL,
    english_hsslc_out_of            VARCHAR(20)     DEFAULT NULL,
    english_hsslc_percentage        VARCHAR(20)     DEFAULT NULL,
    maths_hsslc_marks_obtained      VARCHAR(20)     DEFAULT NULL,
    maths_hsslc_out_of              VARCHAR(20)     DEFAULT NULL,
    maths_hsslc_percentage          VARCHAR(20)     DEFAULT NULL,
    phy_hsslc_marks_obtained        VARCHAR(20)     DEFAULT NULL,
    phy_hsslc_out_of                VARCHAR(20)     DEFAULT NULL,
    phy_hsslc_percentage            VARCHAR(20)     DEFAULT NULL,
    che_comp_bio_hsslc_marks_obtained VARCHAR(20)   DEFAULT NULL,
    che_comp_bio_hsslc_out_of       VARCHAR(20)     DEFAULT NULL,
    che_comp_bio_hsslc_percentage   VARCHAR(20)     DEFAULT NULL,
    chemistry_hsslc_marks_obtained  VARCHAR(20)     DEFAULT NULL,
    chemistry_hsslc_out_of          VARCHAR(20)     DEFAULT NULL,
    chemistry_hsslc_percentage      VARCHAR(20)     DEFAULT NULL,

    -- Diploma
    diploma_bord                    VARCHAR(100)    DEFAULT NULL,
    diploma_name_of_institute       VARCHAR(200)    DEFAULT NULL,
    diploma_stream                  VARCHAR(100)    DEFAULT NULL,
    diploma_roll_no                 VARCHAR(50)     DEFAULT NULL,
    diploma_year_of_passing         VARCHAR(10)     DEFAULT NULL,
    diploma_marks_obtained          VARCHAR(20)     DEFAULT NULL,
    diploma_out_of                  VARCHAR(20)     DEFAULT NULL,
    diploma_percentage              VARCHAR(20)     DEFAULT NULL,

    -- Graduation
    graduation_bord                 VARCHAR(100)    DEFAULT NULL,
    graduation_name_of_institute    VARCHAR(200)    DEFAULT NULL,
    graduation_stream               VARCHAR(100)    DEFAULT NULL,
    graduation_roll_no              VARCHAR(50)     DEFAULT NULL,
    graduation_year_of_passing      VARCHAR(10)     DEFAULT NULL,
    graduation_marks_obtained       VARCHAR(20)     DEFAULT NULL,
    graduation_out_of               VARCHAR(20)     DEFAULT NULL,
    graduation_percentage           VARCHAR(20)     DEFAULT NULL,

    -- Post Graduation
    pg_bord                         VARCHAR(100)    DEFAULT NULL,
    pg_name_of_institute            VARCHAR(200)    DEFAULT NULL,
    pg_stream                       VARCHAR(100)    DEFAULT NULL,
    pg_roll_no                      VARCHAR(50)     DEFAULT NULL,
    pg_year_of_passing              VARCHAR(10)     DEFAULT NULL,
    pg_marks_obtained               VARCHAR(20)     DEFAULT NULL,
    pg_out_of                       VARCHAR(20)     DEFAULT NULL,
    pg_percentage                   VARCHAR(20)     DEFAULT NULL,

    -- Source / audit
    student                         VARCHAR(20)     DEFAULT NULL,
    uploaded_at                     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    uploaded_by                     INT UNSIGNED    DEFAULT NULL,

    INDEX idx_application_no  (application_no),
    INDEX idx_cname           (cname),
    INDEX idx_mobile          (mobile),
    INDEX idx_category        (category),
    INDEX idx_programme       (programme),
    INDEX idx_academic_year   (academic_year),
    INDEX idx_district        (district),

    CONSTRAINT fk_student_uploader FOREIGN KEY (uploaded_by)
        REFERENCES users(id) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
