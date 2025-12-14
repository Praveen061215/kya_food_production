-- =====================================================
-- Fix Inventory Table Structure
-- Add missing columns for stock alerts functionality
-- =====================================================

USE kya_food_production;

-- Add missing columns to inventory table if they don't exist
ALTER TABLE inventory 
ADD COLUMN IF NOT EXISTS unit_price DECIMAL(10,2) DEFAULT 0.00 AFTER unit,
ADD COLUMN IF NOT EXISTS reorder_level INT DEFAULT 0 AFTER unit_price,
ADD COLUMN IF NOT EXISTS critical_level INT DEFAULT 0 AFTER reorder_level,
ADD COLUMN IF NOT EXISTS alert_status ENUM('normal', 'low_stock', 'critical') DEFAULT 'normal' AFTER location,
ADD COLUMN IF NOT EXISTS alert_acknowledged BOOLEAN DEFAULT 0 AFTER alert_status,
ADD COLUMN IF NOT EXISTS alert_acknowledged_by INT NULL AFTER alert_acknowledged,
ADD COLUMN IF NOT EXISTS alert_acknowledged_at DATETIME NULL AFTER alert_acknowledged_by,
ADD COLUMN IF NOT EXISTS expiry_date DATE NULL AFTER alert_acknowledged_at;

-- Add foreign key for alert_acknowledged_by if not exists
-- ALTER TABLE inventory 
-- ADD CONSTRAINT fk_inventory_alert_ack_user 
-- FOREIGN KEY (alert_acknowledged_by) REFERENCES users(id) ON DELETE SET NULL;

-- Verify the changes
SELECT 'Inventory table structure updated successfully!' as Status;

DESCRIBE inventory;
