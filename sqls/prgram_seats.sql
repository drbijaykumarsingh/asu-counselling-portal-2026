-- Use the database
USE asu_portal;

-- Drop the old table
DROP TABLE IF EXISTS program_seats;

-- Create the new table with exam_type column
CREATE TABLE program_seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_type VARCHAR(10) NOT NULL,
    category VARCHAR(10) NOT NULL,
    
    -- B.Tech Programs
    btech_cse_aiml INT DEFAULT 0,
    btech_cse_cyber INT DEFAULT 0,
    btech_ece_vlsi INT DEFAULT 0,
    btech_ece_comm INT DEFAULT 0,
    btech_civil INT DEFAULT 0,
    
    -- Lateral Entry Programs
    lat_cse_aiml INT DEFAULT 0,
    lat_cse_cyber INT DEFAULT 0,
    lat_civil INT DEFAULT 0,
    
    -- Integrated & Diploma Programs
    int_btech_mech_cadcam INT DEFAULT 0,
    dip_elec_eng INT DEFAULT 0,
    dip_elec_ev INT DEFAULT 0,
    
    -- M.Tech Programs
    mtech_it_aiml INT DEFAULT 0,
    mtech_ece_vlsi INT DEFAULT 0,
    mtech_ece_wireless INT DEFAULT 0,
    mtech_civil_const INT DEFAULT 0,
    
    -- PG Diploma Programs
    pgdip_aiml INT DEFAULT 0,
    pgdip_const_tech INT DEFAULT 0,
    
    -- FYIMP Programs
    fyimp_food_tech INT DEFAULT 0,
    fyimp_travel_tour INT DEFAULT 0,
    
    -- Other Programs
    mttm INT DEFAULT 0,
    mba INT DEFAULT 0,
    bba INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_exam_category (exam_type, category)
);

-- Insert all rows
INSERT INTO program_seats (exam_type, category) VALUES
    ('CEE', 'UR'), ('CEE', 'OBC/MOBC'), ('CEE', 'SC'), ('CEE', 'STP'), ('CEE', 'STH'), ('CEE', 'DA'), ('CEE', 'EWS'),
    ('JEE', 'UR'), ('JEE', 'OBC/MOBC'), ('JEE', 'SC'), ('JEE', 'STP'), ('JEE', 'STH'), ('JEE', 'DA'), ('JEE', 'EWS'),
    ('ASUEE', 'UR'), ('ASUEE', 'OBC/MOBC'), ('ASUEE', 'SC'), ('ASUEE', 'STP'), ('ASUEE', 'STH'), ('ASUEE', 'DA'), ('ASUEE', 'EWS');