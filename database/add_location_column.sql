-- =====================================================
-- Add location column to temperature_logs table
-- This allows temperature_monitoring.php to work
-- =====================================================

USE kya_food_production;

-- Add location column if it doesn't exist
ALTER TABLE temperature_logs 
ADD COLUMN IF NOT EXISTS location VARCHAR(100) AFTER section;

-- Populate location from room_name for existing records
UPDATE temperature_logs 
SET location = room_name 
WHERE location IS NULL OR location = '';

-- Create index on location
CREATE INDEX IF NOT EXISTS idx_location ON temperature_logs(location);

-- Verify the change
SELECT 'Location column added successfully!' as Status;
SELECT COUNT(*) as 'Total Records', 
       COUNT(DISTINCT location) as 'Locations',
       COUNT(DISTINCT room_id) as 'Room IDs'
FROM temperature_logs WHERE section = 1;

-- Show sample data
SELECT id, section, room_id, room_name, location, temperature, humidity, recorded_at 
FROM temperature_logs 
WHERE section = 1 
ORDER BY recorded_at DESC 
LIMIT 5;
