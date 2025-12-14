-- =====================================================
-- Recreate Temperature Logs Table with Correct Structure
-- =====================================================

USE kya_food_production;

-- Drop existing table
DROP TABLE IF EXISTS temperature_logs;

-- Create temperature_logs table with correct structure
CREATE TABLE temperature_logs (
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

-- Insert quick test data
INSERT INTO temperature_logs (section, room_id, room_name, temperature, humidity, recorded_at, status) VALUES
(1, 'RM_A', 'Storage Room A', 20.5, 62.0, NOW(), 'Normal'),
(1, 'RM_B', 'Storage Room B', 22.0, 65.0, NOW(), 'Normal'),
(1, 'COLD', 'Cold Storage', 4.2, 85.0, NOW(), 'Normal'),
(1, 'DRY', 'Drying Area', 27.5, 32.0, NOW(), 'Normal'),
(1, 'PREP', 'Preparation Room', 18.5, 55.0, NOW(), 'Normal');

SELECT 'Temperature table recreated and test data inserted!' as Status;
SELECT COUNT(*) as 'Total Records', COUNT(DISTINCT room_id) as 'Rooms' FROM temperature_logs WHERE section = 1;
