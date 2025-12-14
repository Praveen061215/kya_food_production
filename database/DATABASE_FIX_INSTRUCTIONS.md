# Database Fix Instructions

## Problem Identified

The database error you're experiencing is caused by schema mismatches between the database tables and the PHP code:

1. **inventory_history table**: Column names don't match
   - Database has: `change_type` and `created_by`
   - PHP expects: `transaction_type` and `user_id`

2. **inventory table**: Alert status enum values don't match
   - Database has: 'ok', 'warning', 'critical'
   - PHP expects: 'normal', 'low_stock', 'critical', 'expiring_soon'

3. **orders table**: Missing `order_date` column
   - PHP code references this column but it doesn't exist in the database

4. **receiving_records table**: Table doesn't exist
   - Section 1 receiving module requires this table but it's missing from the database

5. **processing_logs table**: Table doesn't exist
   - Processing logs module requires this table but it's missing from the database

## Solution

Two options to fix the database:

### Option 1: Update Existing Database (Recommended - Preserves Data)

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Select the `kya_food_production` database
3. Click on the "SQL" tab
4. Copy and paste the contents of `fix_database_errors.sql` file
5. Click "Go" to execute the script

### Option 2: Recreate Database from Scratch (Only if you don't have important data)

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Drop the existing `kya_food_production` database
3. Click on "Import" tab
4. Choose the `kya_food_production.sql` file
5. Click "Go" to import

## Verification

After running the fix, verify the changes:

1. Go to phpMyAdmin
2. Select `kya_food_production` database
3. Check these tables:
   - `inventory`: Check that `alert_status` column has values: normal, low_stock, critical, expiring_soon
   - `inventory_history`: Check that columns are named `transaction_type` and `user_id`
   - `orders`: Check that `order_date` column exists
   - `receiving_records`: Check that the table exists with all required columns
   - `processing_logs`: Check that the table exists with all required columns

## Testing

After applying the fix:

1. Navigate to: http://localhost/kya-food-production/modules/inventory/index.php
2. The page should load without database errors
3. Try clicking "View Details" on any inventory item
4. Try clicking "Edit" on any inventory item
5. Navigate to: http://localhost/kya-food-production/modules/section1/receiving.php
6. The receiving page should load without errors
7. Navigate to: http://localhost/kya-food-production/modules/processing/logs.php?section=2
8. The processing logs page should load without errors
9. All operations should work without errors

## Files Modified

- `database/kya_food_production.sql` - Updated schema with correct column names and enum values
- `database/fix_database_errors.sql` - Migration script to update existing database

## Need Help?

If you encounter any errors during the migration:
1. Check the error message in phpMyAdmin
2. Make sure XAMPP MySQL service is running
3. Ensure you have proper permissions to modify the database
