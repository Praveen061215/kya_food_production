# KYA Food Production - Test Cases

## Table of Contents
1. [Admin Test Cases](#admin-test-cases)
2. [Section 1 - Raw Material Storage Test Cases](#section-1-raw-material-storage-test-cases)
3. [Section 2 - Food Cutting Test Cases](#section-2-food-cutting-test-cases)
4. [Section 3 - Food Dehydration Test Cases](#section-3-food-dehydration-test-cases)
5. [Common Functionality Test Cases](#common-functionality-test-cases)

## Admin Test Cases

### User Management
| ID | Test Case | Test Steps | Expected Result | Status |
|----|-----------|------------|-----------------|--------|
| AD-001 | Admin Login | 1. Navigate to login page<br>2. Enter admin credentials<br>3. Click Login | Admin dashboard should load with full system access | |
| AD-002 | Create New User | 1. Go to Users > Add New<br>2. Fill in user details<br>3. Assign role (admin/section manager)<br>4. Save | New user should be created and visible in users list | |
| AD-003 | Edit User | 1. Find user in list<br>2. Click Edit<br>3. Update details<br>4. Save | User details should be updated | |
| AD-004 | Deactivate User | 1. Find user in list<br>2. Click Deactivate | User status should change to inactive | |
| AD-005 | Reset Password | 1. Select user<br>2. Click Reset Password<br>3. Set new password | User should be able to login with new password | |

### System Configuration
| ID | Test Case | Test Steps | Expected Result | Status |
|----|-----------|------------|-----------------|--------|
| AD-006 | System Settings Update | 1. Go to System Settings<br>2. Update settings<br>3. Save | Settings should be saved and applied | |
| AD-007 | Database Backup | 1. Go to Backup<br>2. Click Backup Now | Backup file should be created | |
| AD-008 | System Logs Access | 1. Go to System Logs | Should display all system activities | |

## Section 1 - Raw Material Storage Test Cases

### Receiving Raw Materials
| ID | Test Case | Test Steps | Expected Result | Status |
|----|-----------|------------|-----------------|--------|
| S1-001 | Receive New Delivery | 1. Go to Receiving > New<br>2. Enter delivery details<br>3. Submit | Delivery should be recorded in system | |
| S1-002 | Quality Check | 1. Record quality metrics<br>2. Enter inspection results | Quality data should be saved | |
| S1-003 | Storage Assignment | 1. Select storage location<br>2. Assign batch to location | Storage assignment should be updated | |

### Storage Management
| ID | Test Case | Test Steps | Expected Result | Status |
|----|-----------|------------|-----------------|--------|
| S1-004 | View Stock Levels | 1. Go to Inventory > Stock | Should display current stock levels | |
| S1-005 | Update Storage Conditions | 1. Select storage location<br>2. Update conditions | Conditions should be updated | |
| S1-006 | Expiry Monitoring | 1. Check expiry report | Should show items near expiry | |

## Section 2 - Food Cutting Test Cases

### Cutting Operations
| ID | Test Case | Test Steps | Expected Result | Status |
|----|-----------|------------|-----------------|--------|
| S2-001 | Start Cutting Batch | 1. Select raw material<br>2. Enter cutting details<br>3. Start batch | New cutting batch should be created | |
| S2-002 | Record Waste | 1. Enter waste amount<br>2. Save | Waste should be recorded | |
| S2-003 | Complete Batch | 1. Mark batch as complete | Status should update to completed | |

### Quality Control
| ID | Test Case | Test Steps | Expected Result | Status |
|----|-----------|------------|-----------------|--------|
| S2-004 | Perform Quality Check | 1. Select batch<br>2. Enter quality metrics | Quality data should be saved | |
| S2-005 | Record Defects | 1. Note any defects<br>2. Save | Defects should be recorded | |

## Section 3 - Food Dehydration Test Cases

### Dehydration Process
| ID | Test Case | Test Steps | Expected Result | Status |
|----|-----------|------------|-----------------|--------|
| S3-001 | Start Dehydration | 1. Select material<br>2. Set parameters<br>3. Start | Dehydration should begin | |
| S3-002 | Monitor Process | 1. View current status | Should show real-time metrics | |
| S3-003 | Complete Process | 1. Mark as complete | Status should update | |

### Packaging
| ID | Test Case | Test Steps | Expected Result | Status |
|----|-----------|------------|-----------------|--------|
| S3-004 | Package Product | 1. Select batch<br>2. Enter package details | Package record created | |
| S3-005 | Labeling | 1. Generate labels<br>2. Print | Labels should be correct | |

## Common Functionality Test Cases

### Authentication
| ID | Test Case | Test Steps | Expected Result | Status |
|----|-----------|------------|-----------------|--------|
| CF-001 | User Login | 1. Enter credentials<br>2. Submit | Should login successfully | |
| CF-002 | Invalid Login | 1. Enter wrong credentials<br>2. Submit | Should show error | |
| CF-003 | Session Timeout | 1. Stay idle<br>2. Try to perform action | Should redirect to login | |

### Reporting
| ID | Test Case | Test Steps | Expected Result | Status |
|----|-----------|------------|-----------------|--------|
| CF-004 | Generate Report | 1. Select report type<br>2. Set date range<br>3. Generate | Report should be generated | |
| CF-005 | Export Report | 1. Generate report<br>2. Click Export > PDF/Excel | File should download | |

### Notifications
| ID | Test Case | Test Steps | Expected Result | Status |
|----|-----------|------------|-----------------|--------|
| CF-006 | Receive Notification | 1. Trigger notification event | Notification should appear | |
| CF-007 | Mark as Read | 1. Click notification | Should mark as read | |

## Test Execution Instructions
1. Ensure test environment is properly set up
2. Use test accounts with appropriate roles
3. Execute test cases in sequence
4. Document any issues found
5. Retest after fixes

## Test Data Requirements
- Test user accounts for each role
- Sample inventory items
- Test supplier data
- Sample production batches
