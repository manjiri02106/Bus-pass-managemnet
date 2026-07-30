CREATE DATABASE IF NOT EXISTS bus_pass_management;
USE bus_pass_management;

CREATE TABLE IF NOT EXISTS bus_pass_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(100) NOT NULL,
    route VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL,
    pass_type VARCHAR(50) NOT NULL,
    issued_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    renewal_date DATE NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO bus_pass_records (student_name, route, status, pass_type, issued_date, expiry_date, renewal_date)
VALUES
('Asha Verma', 'Route A', 'Pending', 'Student', '2026-01-05', '2026-06-30', '2026-06-15'),
('Rahul Kumar', 'Route B', 'Approved', 'Student', '2026-02-10', '2026-07-15', NULL),
('Neha Sharma', 'Route A', 'Rejected', 'Student', '2026-02-18', '2026-07-20', NULL),
('Aniket Rao', 'Route C', 'Approved', 'Student', '2026-03-01', '2026-08-01', '2026-07-25'),
('Kavya Patil', 'Route B', 'Pending', 'Student', '2026-03-16', '2026-08-16', NULL),
('Mohan Das', 'Route C', 'Approved', 'Student', '2026-04-03', '2026-09-01', '2026-08-10'),
('Pooja Singh', 'Route A', 'Pending', 'Student', '2026-04-10', '2026-09-10', '2026-08-20'),
('Suresh Nair', 'Route D', 'Rejected', 'Student', '2026-04-24', '2026-09-24', NULL);
