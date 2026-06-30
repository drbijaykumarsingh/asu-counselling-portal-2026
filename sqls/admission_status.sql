-- Use the database
USE asu_portal;

-- Create the admission_status table
CREATE TABLE IF NOT EXISTS admission_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- UAN (matching student_master table column name)
    uan_no VARCHAR(20) NOT NULL UNIQUE,
    
    -- Overall status
    status VARCHAR(50) DEFAULT 'Pending',
    
    -- Stage 1 details
    st1_user VARCHAR(100),
    st1_remarks TEXT,
    st1_date_time DATETIME,
    
    -- Stage 2 details
    st2_user VARCHAR(100),
    st2_remarks TEXT,
    st2_date_time DATETIME,
    
    -- Stage 3 details
    st3_user VARCHAR(100),
    st3_remarks TEXT,
    st3_date_time DATETIME,
    
    -- Stage 4 details
    st4_user VARCHAR(100),
    st4_remarks TEXT,
    st4_date_time DATETIME,
    
    -- Stage 5 (only date_time)
    st5_date_time DATETIME,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);