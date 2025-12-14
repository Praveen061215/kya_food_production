-- =====================================================
-- KYA Food Production - Temperature Monitoring Dummy Data
-- Section 1 - Raw Materials Storage Temperature & Humidity
-- =====================================================

USE kya_food_production;

-- Create temperature_logs table if not exists
CREATE TABLE IF NOT EXISTS temperature_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section INT NOT NULL,
    room_id VARCHAR(50) NOT NULL,
    room_name VARCHAR(100) NOT NULL,
    temperature DECIMAL(5,2) NOT NULL,
    humidity DECIMAL(5,2) NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    alert_triggered BOOLEAN DEFAULT FALSE,
    status ENUM('Normal', 'Warning', 'Critical') DEFAULT 'Normal',
    notes TEXT,
    INDEX idx_section_date (section, recorded_at),
    INDEX idx_room (room_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clear existing data for Section 1 (optional)
-- DELETE FROM temperature_logs WHERE section = 1;

-- =====================================================
-- Insert Temperature Data for Last 7 Days
-- =====================================================

-- Storage Room A: Optimal for dry spices (18-22°C, 50-70% RH)
INSERT INTO temperature_logs (section, room_id, room_name, temperature, humidity, recorded_at, alert_triggered, status, notes) VALUES
-- Day 1 (7 days ago)
(1, 'RM_A', 'Storage Room A', 20.5, 62.0, DATE_SUB(NOW(), INTERVAL 7 DAY) + INTERVAL 8 HOUR, 0, 'Normal', 'Morning check - optimal conditions'),
(1, 'RM_A', 'Storage Room A', 21.2, 65.5, DATE_SUB(NOW(), INTERVAL 7 DAY) + INTERVAL 12 HOUR, 0, 'Normal', NULL),
(1, 'RM_A', 'Storage Room A', 21.8, 68.0, DATE_SUB(NOW(), INTERVAL 7 DAY) + INTERVAL 16 HOUR, 0, 'Normal', NULL),
(1, 'RM_A', 'Storage Room A', 20.0, 60.0, DATE_SUB(NOW(), INTERVAL 7 DAY) + INTERVAL 20 HOUR, 0, 'Normal', 'Evening check'),

-- Day 2 (6 days ago)
(1, 'RM_A', 'Storage Room A', 19.8, 58.5, DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'RM_A', 'Storage Room A', 20.5, 63.0, DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 12 HOUR, 0, 'Normal', NULL),
(1, 'RM_A', 'Storage Room A', 21.0, 66.0, DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 16 HOUR, 0, 'Normal', NULL),
(1, 'RM_A', 'Storage Room A', 19.5, 59.0, DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 20 HOUR, 0, 'Normal', NULL),

-- Day 3 (5 days ago) - Warning condition
(1, 'RM_A', 'Storage Room A', 23.5, 72.0, DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 8 HOUR, 0, 'Warning', 'Temperature slightly elevated'),
(1, 'RM_A', 'Storage Room A', 24.0, 74.5, DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 12 HOUR, 1, 'Critical', 'AC malfunction - maintenance called'),
(1, 'RM_A', 'Storage Room A', 22.5, 70.0, DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 16 HOUR, 0, 'Warning', 'AC repaired, cooling down'),
(1, 'RM_A', 'Storage Room A', 20.5, 65.0, DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 20 HOUR, 0, 'Normal', 'Back to normal'),

-- Recent days (4-1 days ago) - Normal operations
(1, 'RM_A', 'Storage Room A', 20.2, 61.5, DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 10 HOUR, 0, 'Normal', NULL),
(1, 'RM_A', 'Storage Room A', 20.8, 64.0, DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 10 HOUR, 0, 'Normal', NULL),
(1, 'RM_A', 'Storage Room A', 21.0, 63.5, DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 10 HOUR, 0, 'Normal', NULL),
(1, 'RM_A', 'Storage Room A', 20.5, 62.0, DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 10 HOUR, 0, 'Normal', NULL);

-- Storage Room B: General storage (20-24°C, 55-75% RH)
INSERT INTO temperature_logs (section, room_id, room_name, temperature, humidity, recorded_at, alert_triggered, status, notes) VALUES
(1, 'RM_B', 'Storage Room B', 22.0, 65.0, DATE_SUB(NOW(), INTERVAL 7 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'RM_B', 'Storage Room B', 22.8, 68.5, DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'RM_B', 'Storage Room B', 23.2, 70.0, DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'RM_B', 'Storage Room B', 22.5, 67.0, DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'RM_B', 'Storage Room B', 22.0, 65.5, DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'RM_B', 'Storage Room B', 23.0, 69.0, DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'RM_B', 'Storage Room B', 22.5, 66.5, DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL);

-- Cold Storage: For perishables (2-6°C, 80-90% RH)
INSERT INTO temperature_logs (section, room_id, room_name, temperature, humidity, recorded_at, alert_triggered, status, notes) VALUES
(1, 'COLD', 'Cold Storage', 4.2, 85.0, DATE_SUB(NOW(), INTERVAL 7 DAY) + INTERVAL 8 HOUR, 0, 'Normal', 'Optimal cold storage'),
(1, 'COLD', 'Cold Storage', 4.5, 86.5, DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'COLD', 'Cold Storage', 3.8, 84.0, DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'COLD', 'Cold Storage', 7.5, 88.0, DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 8 HOUR, 0, 'Warning', 'Temperature rising - door left open'),
(1, 'COLD', 'Cold Storage', 8.2, 90.0, DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 10 HOUR, 1, 'Critical', 'Immediate action required'),
(1, 'COLD', 'Cold Storage', 5.5, 87.0, DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 12 HOUR, 0, 'Normal', 'Corrected - door sealed'),
(1, 'COLD', 'Cold Storage', 4.0, 85.5, DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'COLD', 'Cold Storage', 4.3, 86.0, DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'COLD', 'Cold Storage', 4.1, 85.0, DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL);

-- Drying Area: Low humidity required (25-30°C, 20-40% RH)
INSERT INTO temperature_logs (section, room_id, room_name, temperature, humidity, recorded_at, alert_triggered, status, notes) VALUES
(1, 'DRY', 'Drying Area', 27.5, 32.0, DATE_SUB(NOW(), INTERVAL 7 DAY) + INTERVAL 8 HOUR, 0, 'Normal', 'Drying process active'),
(1, 'DRY', 'Drying Area', 28.0, 30.5, DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'DRY', 'Drying Area', 27.8, 31.0, DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'DRY', 'Drying Area', 29.5, 35.0, DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'DRY', 'Drying Area', 26.5, 28.5, DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'DRY', 'Drying Area', 28.2, 33.0, DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'DRY', 'Drying Area', 27.0, 30.0, DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL);

-- Preparation Room: Cool and controlled (16-20°C, 45-65% RH)
INSERT INTO temperature_logs (section, room_id, room_name, temperature, humidity, recorded_at, alert_triggered, status, notes) VALUES
(1, 'PREP', 'Preparation Room', 18.5, 55.0, DATE_SUB(NOW(), INTERVAL 7 DAY) + INTERVAL 8 HOUR, 0, 'Normal', 'Morning prep session'),
(1, 'PREP', 'Preparation Room', 19.0, 58.5, DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'PREP', 'Preparation Room', 18.2, 54.0, DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'PREP', 'Preparation Room', 19.5, 60.0, DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'PREP', 'Preparation Room', 18.0, 52.5, DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'PREP', 'Preparation Room', 18.8, 57.0, DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL),
(1, 'PREP', 'Preparation Room', 18.5, 55.5, DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 8 HOUR, 0, 'Normal', NULL);

-- =====================================================
-- Today's Hourly Readings (Last 12 Hours)
-- =====================================================

-- Storage Room A - Today
INSERT INTO temperature_logs (section, room_id, room_name, temperature, humidity, recorded_at, alert_triggered, status) VALUES
(1, 'RM_A', 'Storage Room A', 20.0, 60.0, DATE_SUB(NOW(), INTERVAL 12 HOUR), 0, 'Normal'),
(1, 'RM_A', 'Storage Room A', 20.3, 61.5, DATE_SUB(NOW(), INTERVAL 11 HOUR), 0, 'Normal'),
(1, 'RM_A', 'Storage Room A', 20.7, 63.0, DATE_SUB(NOW(), INTERVAL 10 HOUR), 0, 'Normal'),
(1, 'RM_A', 'Storage Room A', 21.0, 64.5, DATE_SUB(NOW(), INTERVAL 9 HOUR), 0, 'Normal'),
(1, 'RM_A', 'Storage Room A', 21.3, 66.0, DATE_SUB(NOW(), INTERVAL 8 HOUR), 0, 'Normal'),
(1, 'RM_A', 'Storage Room A', 21.5, 67.0, DATE_SUB(NOW(), INTERVAL 7 HOUR), 0, 'Normal'),
(1, 'RM_A', 'Storage Room A', 21.8, 68.5, DATE_SUB(NOW(), INTERVAL 6 HOUR), 0, 'Normal'),
(1, 'RM_A', 'Storage Room A', 21.5, 67.5, DATE_SUB(NOW(), INTERVAL 5 HOUR), 0, 'Normal'),
(1, 'RM_A', 'Storage Room A', 21.2, 66.0, DATE_SUB(NOW(), INTERVAL 4 HOUR), 0, 'Normal'),
(1, 'RM_A', 'Storage Room A', 20.8, 64.5, DATE_SUB(NOW(), INTERVAL 3 HOUR), 0, 'Normal'),
(1, 'RM_A', 'Storage Room A', 20.5, 63.0, DATE_SUB(NOW(), INTERVAL 2 HOUR), 0, 'Normal'),
(1, 'RM_A', 'Storage Room A', 20.2, 61.5, DATE_SUB(NOW(), INTERVAL 1 HOUR), 0, 'Normal');

-- Storage Room B - Today
INSERT INTO temperature_logs (section, room_id, room_name, temperature, humidity, recorded_at, alert_triggered, status) VALUES
(1, 'RM_B', 'Storage Room B', 22.0, 65.0, DATE_SUB(NOW(), INTERVAL 12 HOUR), 0, 'Normal'),
(1, 'RM_B', 'Storage Room B', 22.3, 66.5, DATE_SUB(NOW(), INTERVAL 10 HOUR), 0, 'Normal'),
(1, 'RM_B', 'Storage Room B', 22.8, 68.0, DATE_SUB(NOW(), INTERVAL 8 HOUR), 0, 'Normal'),
(1, 'RM_B', 'Storage Room B', 23.0, 69.5, DATE_SUB(NOW(), INTERVAL 6 HOUR), 0, 'Normal'),
(1, 'RM_B', 'Storage Room B', 22.8, 68.5, DATE_SUB(NOW(), INTERVAL 4 HOUR), 0, 'Normal'),
(1, 'RM_B', 'Storage Room B', 22.5, 67.0, DATE_SUB(NOW(), INTERVAL 2 HOUR), 0, 'Normal');

-- Cold Storage - Today
INSERT INTO temperature_logs (section, room_id, room_name, temperature, humidity, recorded_at, alert_triggered, status) VALUES
(1, 'COLD', 'Cold Storage', 4.0, 85.0, DATE_SUB(NOW(), INTERVAL 12 HOUR), 0, 'Normal'),
(1, 'COLD', 'Cold Storage', 4.2, 85.5, DATE_SUB(NOW(), INTERVAL 10 HOUR), 0, 'Normal'),
(1, 'COLD', 'Cold Storage', 4.5, 86.0, DATE_SUB(NOW(), INTERVAL 8 HOUR), 0, 'Normal'),
(1, 'COLD', 'Cold Storage', 4.3, 85.5, DATE_SUB(NOW(), INTERVAL 6 HOUR), 0, 'Normal'),
(1, 'COLD', 'Cold Storage', 4.1, 85.0, DATE_SUB(NOW(), INTERVAL 4 HOUR), 0, 'Normal'),
(1, 'COLD', 'Cold Storage', 4.0, 84.5, DATE_SUB(NOW(), INTERVAL 2 HOUR), 0, 'Normal');

-- Drying Area - Today
INSERT INTO temperature_logs (section, room_id, room_name, temperature, humidity, recorded_at, alert_triggered, status) VALUES
(1, 'DRY', 'Drying Area', 27.0, 30.0, DATE_SUB(NOW(), INTERVAL 12 HOUR), 0, 'Normal'),
(1, 'DRY', 'Drying Area', 27.5, 31.5, DATE_SUB(NOW(), INTERVAL 10 HOUR), 0, 'Normal'),
(1, 'DRY', 'Drying Area', 28.0, 33.0, DATE_SUB(NOW(), INTERVAL 8 HOUR), 0, 'Normal'),
(1, 'DRY', 'Drying Area', 28.5, 34.5, DATE_SUB(NOW(), INTERVAL 6 HOUR), 0, 'Normal'),
(1, 'DRY', 'Drying Area', 28.0, 33.5, DATE_SUB(NOW(), INTERVAL 4 HOUR), 0, 'Normal'),
(1, 'DRY', 'Drying Area', 27.5, 32.0, DATE_SUB(NOW(), INTERVAL 2 HOUR), 0, 'Normal');

-- Preparation Room - Today
INSERT INTO temperature_logs (section, room_id, room_name, temperature, humidity, recorded_at, alert_triggered, status) VALUES
(1, 'PREP', 'Preparation Room', 18.0, 54.0, DATE_SUB(NOW(), INTERVAL 12 HOUR), 0, 'Normal'),
(1, 'PREP', 'Preparation Room', 18.5, 55.5, DATE_SUB(NOW(), INTERVAL 10 HOUR), 0, 'Normal'),
(1, 'PREP', 'Preparation Room', 19.0, 57.0, DATE_SUB(NOW(), INTERVAL 8 HOUR), 0, 'Normal'),
(1, 'PREP', 'Preparation Room', 19.2, 58.5, DATE_SUB(NOW(), INTERVAL 6 HOUR), 0, 'Normal'),
(1, 'PREP', 'Preparation Room', 18.8, 57.0, DATE_SUB(NOW(), INTERVAL 4 HOUR), 0, 'Normal'),
(1, 'PREP', 'Preparation Room', 18.5, 55.5, DATE_SUB(NOW(), INTERVAL 2 HOUR), 0, 'Normal');

-- =====================================================
-- Summary
-- =====================================================
SELECT 'Temperature monitoring data inserted successfully!' as Status;
SELECT COUNT(*) as 'Total Records', COUNT(DISTINCT room_id) as 'Rooms Monitored' 
FROM temperature_logs WHERE section = 1;
