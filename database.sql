-- =============================================
-- HealthKiosk Database Schema
-- Database: medical_kiosk
-- =============================================

CREATE DATABASE IF NOT EXISTS medical_kiosk;
USE medical_kiosk;

-- -----------------------------------------------
-- TABLE: patients
-- Stores patient info from scanid.html
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS patients (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    first_name  VARCHAR(100) NOT NULL,
    last_name   VARCHAR(100) NOT NULL,
    date_of_birth DATE,
    age         INT,
    gender      ENUM('Male','Female') NOT NULL,
    phone       VARCHAR(20),
    barangay    VARCHAR(100),
    municipality VARCHAR(100) DEFAULT 'Pozorrubio',
    province    VARCHAR(100) DEFAULT 'Pangasinan',
    face_image  LONGTEXT,          -- base64 image from facecapture.html
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------
-- TABLE: health_records
-- Stores all vitals linked to a patient session
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS health_records (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    patient_id      INT NOT NULL,
    record_code     VARCHAR(20),           -- e.g. REC-123456

    -- Body Measurements
    weight_kg       DECIMAL(5,2),          -- from weight.html
    height_cm       DECIMAL(5,2),          -- from height.html
    bmi             DECIMAL(5,2),          -- auto-calculated

    -- Vital Signs
    temperature_c   DECIMAL(5,2),          -- from temperature.html
    spo2_percent    DECIMAL(5,2),          -- from oximeter.html
    systolic_bp     INT,                   -- from bloodpressure.html
    diastolic_bp    INT,                   -- from bloodpressure.html
    pulse_bpm       INT,                   -- from bloodpressure.html

    -- Status flags (auto-evaluated)
    temp_status     VARCHAR(20),           -- Normal / Fever / Low
    spo2_status     VARCHAR(20),           -- Normal / Low / Critical
    bp_status       VARCHAR(20),           -- Normal / Elevated / High
    pulse_status    VARCHAR(20),           -- Normal / Low / High
    bmi_status      VARCHAR(20),           -- Normal / Underweight / Overweight / Obese

    recorded_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- INDEX for faster lookups
-- -----------------------------------------------
CREATE INDEX idx_patient_id ON health_records(patient_id);
CREATE INDEX idx_recorded_at ON health_records(recorded_at);




-- ── ADMINS TABLE (for login) ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admins (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,   -- bcrypt hashed
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);
 
-- Default admin: username=admin, password=admin123 (bcrypt)
INSERT INTO admins (username, password) VALUES (
    'RHUKioskAdmin@gmail.com',
    'Kioskforhealth123'  -- admin123
);