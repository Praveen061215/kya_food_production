-- Verify Temperature Data
USE kya_food_production;

-- Check if table exists
SHOW TABLES LIKE 'temperature_logs';

-- Count total records
SELECT COUNT(*) as total_records FROM temperature_logs;

-- Count records for Section 1
SELECT COUNT(*) as section1_records FROM temperature_logs WHERE section = 1;

-- Show latest reading for each room
SELECT 
    room_id,
    room_name,
    temperature,
    humidity,
    recorded_at,
    status,
    alert_triggered
FROM temperature_logs tl1
WHERE section = 1
AND recorded_at = (
    SELECT MAX(recorded_at) 
    FROM temperature_logs tl2 
    WHERE tl2.room_id = tl1.room_id 
    AND tl2.section = 1
)
ORDER BY room_name;

-- Show all rooms
SELECT DISTINCT room_id, room_name FROM temperature_logs WHERE section = 1 ORDER BY room_name;

-- Show today's records
SELECT COUNT(*) as today_records 
FROM temperature_logs 
WHERE section = 1 AND DATE(recorded_at) = CURDATE();
