﻿# mmhr_census

CREATE TABLE uploaded_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE patient_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_id INT,
    sheet_name VARCHAR(255),
    admission_date DATE,
    discharge_date DATE,
    member_category VARCHAR(255),
    FOREIGN KEY (file_id) REFERENCES uploaded_files(id) ON DELETE CASCADE
);

CREATE TABLE patient_records_2 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_id INT,
    sheet_name VARCHAR(255),
    admission_date DATE,
    patient_name VARCHAR(255),
    FOREIGN KEY (file_id) REFERENCES uploaded_files(id)
);

CREATE TABLE patient_records_3 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_id INT NOT NULL,
    sheet_name_3 VARCHAR(255) NOT NULL,
    patient_name_3 VARCHAR(255) NOT NULL,
    date_admitted DATE NOT NULL,
    date_discharge DATE NOT NULL,
    category VARCHAR(100) NOT NULL,
    FOREIGN KEY (file_id) REFERENCES uploaded_files(id) ON DELETE CASCADE
);



