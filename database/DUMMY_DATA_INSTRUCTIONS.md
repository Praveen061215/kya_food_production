# Dummy Data Installation Instructions

## Overview
This script inserts sample data for testing the KYA Food Production system.

---

## What Data is Included?

### **Users (10 Total)**
| Username | Password | Role | Section |
|----------|----------|------|---------|
| admin | password123 | Admin | All |
| section1_manager | password123 | Manager | Section 1 |
| section1_operator1 | password123 | Operator | Section 1 |
| section1_operator2 | password123 | Operator | Section 1 |
| section2_manager | password123 | Manager | Section 2 |
| section2_operator1 | password123 | Operator | Section 2 |
| section2_operator2 | password123 | Operator | Section 2 |
| section3_manager | password123 | Manager | Section 3 |
| section3_operator1 | password123 | Operator | Section 3 |
| section3_operator2 | password123 | Operator | Section 3 |

### **Other Data**
- **5 Suppliers**: Sri Lanka Spice Traders, Kerala Organic Farms, etc.
- **14 Inventory Items**: 
  - 6 Raw Materials (Section 1)
  - 4 Processed Items (Section 2)
  - 4 Packaged Products (Section 3)
- **6 Receiving Records**: Raw material receipts
- **5 Processing Logs**: Processing activities
- **5 Orders**: Customer orders with items
- **14 Order Items**: Products in orders
- **8 Inventory History**: Stock movements
- **7 Activity Logs**: User activities
- **5 Notifications**: System notifications

---

## How to Install

### **Method 1: Using phpMyAdmin**

1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Select database: `kya_food_production`
3. Click **Import** tab
4. Choose file: `insert_dummy_data.sql`
5. Click **Go** button
6. Wait for success message

### **Method 2: Using MySQL Command Line**

```bash
cd C:\xampp\htdocs\kya-food-production\database
mysql -u root -p kya_food_production < insert_dummy_data.sql
```

### **Method 3: Using XAMPP Shell**

```bash
cd C:\xampp\mysql\bin
mysql -u root kya_food_production < C:\xampp\htdocs\kya-food-production\database\insert_dummy_data.sql
```

---

## Login Credentials

### **Admin Access**
- **URL**: http://localhost/kya-food-production/login.php
- **Username**: `admin`
- **Password**: `password123`

### **Section 1 (Raw Materials)**
- **Manager**: `section1_manager` / `password123`
- **Operator**: `section1_operator1` / `password123`

### **Section 2 (Processing)**
- **Manager**: `section2_manager` / `password123`
- **Operator**: `section2_operator1` / `password123`

### **Section 3 (Packaging)**
- **Manager**: `section3_manager` / `password123`
- **Operator**: `section3_operator1` / `password123`

---

## Test Scenarios

### **1. Admin Dashboard**
Login as `admin` and view:
- All sections overview
- Recent orders
- Inventory alerts
- System notifications

### **2. Section 1 - Receiving**
Login as `section1_manager` and:
- View receiving records
- Approve pending receipts
- Check inventory levels

### **3. Section 2 - Processing**
Login as `section2_manager` and:
- View processing logs
- Track batch progress
- Monitor yield percentages

### **4. Section 3 - Packaging**
Login as `section3_manager` and:
- View packaged products
- Check order fulfillment
- Monitor stock levels

### **5. Orders Management**
Login as `admin` and:
- View customer orders
- Check order status
- Process deliveries

---

## Sample Data Details

### **Inventory Items by Section**

**Section 1 - Raw Materials:**
- Cinnamon Sticks (500 kg)
- Black Pepper (350 kg)
- Cardamom (150 kg)
- Turmeric Powder (600 kg)
- Cumin Seeds (280 kg)
- Cloves (120 kg)

**Section 2 - Processed:**
- Cinnamon Powder (450 kg)
- Mixed Spice Blend (320 kg)
- Curry Powder Premium (280 kg)
- Garam Masala (200 kg)

**Section 3 - Packaged:**
- Cinnamon Powder 100g Pack (5000 units)
- Mixed Spice Blend 250g Pack (3500 units)
- Curry Powder 200g Pack (4200 units)
- Garam Masala 150g Pack (2800 units)

---

## Verification Steps

After installation, verify data:

```sql
-- Check users
SELECT username, role, section FROM users;

-- Check inventory
SELECT section, item_code, item_name, quantity FROM inventory;

-- Check orders
SELECT order_number, customer_name, status, total_amount FROM orders;

-- Check processing logs
SELECT batch_id, process_type, yield_percentage FROM processing_logs;

-- Check receiving records
SELECT item_name, quantity, status FROM receiving_records;
```

---

## Important Notes

⚠️ **Warning**: This script is for testing only. Do not use in production!

✅ **Safe to Run**: This script only inserts data, does not delete or modify existing data.

🔄 **Re-run**: You can run this script multiple times, but it will create duplicate data.

🗑️ **Clean Up**: To remove dummy data, delete records manually or restore database.

---

## Troubleshooting

**Error: Duplicate entry**
- Data already exists
- Solution: Skip or delete existing data first

**Error: Foreign key constraint**
- Run `fix_database_errors.sql` first
- Ensure all tables exist

**Error: Access denied**
- Check MySQL credentials
- Use root user or user with INSERT privileges

---

## For Viva Presentation

**Demo Flow:**
1. Login as Admin → Show dashboard
2. Login as Section 1 Manager → Show receiving
3. Login as Section 2 Manager → Show processing
4. Login as Section 3 Manager → Show packaging
5. Show complete order workflow

**Key Points to Highlight:**
- Multi-user system with role-based access
- Complete supply chain tracking
- Real-time inventory management
- Quality control at each stage
- Order fulfillment tracking

---

**Created**: December 2024  
**Purpose**: Testing & Demonstration  
**Status**: Ready to Use ✅
