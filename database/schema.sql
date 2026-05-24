CREATE DATABASE IF NOT EXISTS sicare_db;
USE sicare_db;

CREATE TABLE IF NOT EXISTS users (
    id CHAR(36) PRIMARY KEY,
    employee_id VARCHAR(20) NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'candidate',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Seed dummy user (employee@mail.com / password)
-- bcrypt hash for 'password' is '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
INSERT INTO users (id, employee_id, first_name, last_name, email, password_hash, role)
SELECT '123e4567-e89b-12d3-a456-426614174000', 'EMP-001', 'Dummy', 'Employee', 'employee@mail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee'
WHERE NOT EXISTS (
    SELECT id FROM users WHERE email = 'employee@mail.com'
);
