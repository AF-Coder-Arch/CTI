-- Create database
CREATE DATABASE IF NOT EXISTS cti_tracker;
USE cti_tracker;

-- Create findings table
CREATE TABLE IF NOT EXISTS findings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    contact_person VARCHAR(255) NULL,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_closed DATETIME NULL,
    status ENUM('open', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO findings (title, description, contact_person, status, date_created, date_closed) VALUES 
('Critical vulnerability in web server', 'SQL injection vulnerability discovered in login form', 'John Smith', 'closed', '2024-01-15 10:30:00', '2024-01-20 14:15:00'),
('Malware detected on endpoint', 'Suspicious file activity detected on workstation WS-001', 'Jane Doe', 'open', '2024-01-25 09:15:00', NULL),
('Phishing email campaign', 'Multiple users received phishing emails targeting credentials', 'Mike Johnson', 'closed', '2024-01-10 11:20:00', '2024-01-12 16:45:00'),
('Unauthorized access attempt', 'Multiple failed login attempts from suspicious IP', 'Sarah Wilson', 'open', '2024-01-28 13:45:00', NULL),
('Data exfiltration detected', 'Unusual outbound network traffic detected', 'David Brown', 'open', '2024-01-20 15:30:00', NULL),
('Password policy violation', 'Weak passwords found in security audit', 'Lisa Davis', 'closed', '2024-01-05 08:00:00', '2024-01-25 10:30:00');