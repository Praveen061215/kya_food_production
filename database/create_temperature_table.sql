-- =====================================================
-- Create Temperature Logs Table
-- =====================================================

USE kya_food_production;

-- Drop existing table if you want to recreate
-- DROP TABLE IF EXISTS temperature_logs;

-- Create temperature_logs table
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

SELECT 'Temperature logs table created successfully!' as Status;
