CREATE DATABASE IF NOT EXISTS student_queue;
USE student_queue;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student','cashier') NOT NULL DEFAULT 'student',
    window_number TINYINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_id INT NOT NULL,
    service_id INT NOT NULL,
    queue_number INT NOT NULL,
    window_number TINYINT UNSIGNED NULL,
    status ENUM('waiting','in_progress','served') NOT NULL DEFAULT 'waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS service_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    service_id INT NOT NULL,
    queue_number INT NOT NULL,
    window_number TINYINT UNSIGNED NULL,
    status ENUM('served','cancelled') NOT NULL DEFAULT 'served',
    served_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT
);

INSERT IGNORE INTO services (name) VALUES
('Grade Payment/Request'),
('Downpayment'),
('Tuition Payment'),
('Book Payment'),
('Uniform Payment'),
('SPR Request');

INSERT IGNORE INTO users
(name, email, password, role, window_number)
VALUES
('Cashier 1', 'cashier1@test.com', '$2y$12$TsD8y5cLsJRjIK8jC/UGQulyc4KE79zC.sfLJQeW9/hTtJvS15CGm', 'cashier', 1),
('Cashier 2', 'cashier2@test.com', '$2y$12$TsD8y5cLsJRjIK8jC/UGQulyc4KE79zC.sfLJQeW9/hTtJvS15CGm', 'cashier', 2),
('Cashier 3', 'cashier3@test.com', '$2y$12$TsD8y5cLsJRjIK8jC/UGQulyc4KE79zC.sfLJQeW9/hTtJvS15CGm', 'cashier', 3),
('Cashier 4', 'cashier4@test.com', '$2y$12$TsD8y5cLsJRjIK8jC/UGQulyc4KE79zC.sfLJQeW9/hTtJvS15CGm', 'cashier', 4),
('Cashier 5', 'cashier5@test.com', '$2y$12$TsD8y5cLsJRjIK8jC/UGQulyc4KE79zC.sfLJQeW9/hTtJvS15CGm', 'cashier', 5),
('Cashier 6', 'cashier6@test.com', '$2y$12$TsD8y5cLsJRjIK8jC/UGQulyc4KE79zC.sfLJQeW9/hTtJvS15CGm', 'cashier', 6);
