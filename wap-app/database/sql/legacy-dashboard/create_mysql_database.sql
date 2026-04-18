CREATE DATABASE IF NOT EXISTS iwa_dashboard 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
USE iwa_dashboard;

CREATE TABLE IF NOT EXISTS app_json_store (
    dataset VARCHAR(120) PRIMARY KEY,
    payload_json LONGTEXT NOT NULL,
    updated_at DATETIME NOT NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
