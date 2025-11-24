<?php
/**
 * KYA Food Production - Section 2 Recipes
 * Processing / Dehydration Recipe Definitions for Section 2
 */

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

// Standard session handling and access control (Section 2)
SessionManager::start();
SessionManager::requireLogin();
SessionManager::requireSection(2);

$userInfo = SessionManager::getUserInfo();

$db = (new Database())->connect();

// Create recipes table if it doesn't exist (shared, scoped by section)
$createTableSql = "
CREATE TABLE IF NOT EXISTS recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section INT NOT NULL,
    recipe_code VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    product_type VARCHAR(100) NOT NULL,
    input_items TEXT NOT NULL,
    process_steps TEXT NOT NULL,
    target_yield DECIMAL(5,2) DEFAULT 90.00,
    drying_temperature DECIMAL(5,2) NULL,
    drying_time_minutes INT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_section (section),
    INDEX idx_recipe_code (recipe_code)
) ENGINE=InnoDB";

try {
    $db->exec($createTableSql);
} catch (PDOException $e) {
    error_log('Recipes table creation failed: ' . $e->getMessage());
}

// Seed sample recipes for Section 2 if empty
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM recipes WHERE section = 2");
    $stmt->execute();
    $count = (int)$stmt->fetchColumn();

    if ($count === 0) {
        $sample = [
            [
                'S2-REC-001',
                'Dried Mango Slices',
                'Fruit Dehydration',
                "Fresh ripe mango slices (10 mm)\nCitric acid dip (0.5%)",
                "1. Wash and peel mangoes.\n2. Slice to 10 mm thickness.\n3. Dip in 0.5% citric acid for 3 minutes.\n4. Load trays evenly.\n5. Dry at 65°C for 10-12 hours until 15% moisture.",
                88.0,
                65.0,
                660,
                'Standard recipe for premium dried mango slices.'
            ],
            [
                'S2-REC-002',
                'Mixed Vegetable Chips',
                'Vegetable Dehydration',
                "Carrot, beetroot, pumpkin slices\nSalt and spice mix",
                "1. Wash and peel vegetables.\n2. Slice thinly (3-4 mm).\n3. Blanch for 2-3 minutes.\n4. Season lightly.\n5. Dry at 60°C for 8-10 hours until crisp.",
                85.0,
                60.0,
                540,
                'Used for export vegetable chips line.'
            ],
            [
                'S2-REC-003',
                'Herbal Tea Mix',
                'Herbal Dehydration',
                "Mint leaves\nLemongrass\nGinger slices",
                "1. Clean and sort raw materials.\n2. Pre-cut to uniform size.\n3. Dry at 50°C for 6-8 hours.\n4. Blend as per ratio 40:40:20.",
                92.0,
                50.0,
                420,
                'Low temperature drying for aroma retention.'
            ]
        ];

        $ins = $db->prepare("INSERT INTO recipes (section, recipe_code, name, product_type, input_items, process_steps, target_yield, drying_temperature, drying_time_minutes, notes) VALUES (2, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($sample as $row) {
            $ins->execute([
                $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $row[8]
            ]);
        }
    }
} catch (PDOException $e) {
    error_log('Recipes seed failed: ' . $e->getMessage());
}

// Filters
$searchFilter = $_GET['search'] ?? '';
$productFilter = $_GET['product_type'] ?? '';

$where = ['section = 2'];
$params = [];

if ($productFilter !== '') {
    $where[] = 'product_type = ?';
    $params[] = $productFilter;
}

if ($searchFilter !== '') {
    $where[] = '(recipe_code LIKE ? OR name LIKE ? OR product_type LIKE ?)';
    $params[] = '%' . $searchFilter . '%';
    $params[] = '%' . $searchFilter . '%';
    $params[] = '%' . $searchFilter . '%';
}

$whereSql = implode(' AND ', $where);

// Get recipes
$listSql = "
    SELECT * FROM recipes
    WHERE $whereSql
    ORDER BY name
";

$listStmt = $db->prepare($listSql);
$listStmt->execute($params);
$recipes = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Section 2 - Recipes';
include '../../includes/header.php';
?>

<div class="content-area">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <span class="badge me-2 bg-warning text-dark">Section 2</span>
                Recipes & Processing Parameters
            </h1>
            <p class="text-muted mb-0">Standard recipes and processing guidelines for dehydration operations</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Section 2 Dashboard
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form class="row g-3" method="get">
                <div class="col-md-4">
                    <label class="form-label">Product Type</label>
                    <select name="product_type" class="form-select">
                        <option value="">All Types</option>
                        <?php
                        $types = ['Fruit Dehydration', 'Vegetable Dehydration', 'Herbal Dehydration'];
                        foreach ($types as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo $productFilter === $type ? 'selected' : ''; ?>>
                                <?php echo $type; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($searchFilter); ?>" placeholder="Recipe code, name, product type...">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="recipes.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times me-1"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Recipes list -->
    <div class="row">
        <?php if (empty($recipes)): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center text-muted py-4">
                        <i class="fas fa-book-open fa-2x mb-2"></i><br>
                        No recipes found for the selected criteria.
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($recipes as $recipe): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($recipe['name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($recipe['recipe_code']); ?></small>
                                </div>
                                <span class="badge bg-primary"><?php echo htmlspecialchars($recipe['product_type']); ?></span>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Target Yield:</strong> <?php echo number_format((float)$recipe['target_yield'], 1); ?>%</p>
                            <?php if ($recipe['drying_temperature'] || $recipe['drying_time_minutes']): ?>
                                <p class="mb-1">
                                    <strong>Drying:</strong>
                                    <?php if ($recipe['drying_temperature']): ?>
                                        <?php echo number_format((float)$recipe['drying_temperature'], 1); ?>°C
                                    <?php endif; ?>
                                    <?php if ($recipe['drying_time_minutes']): ?>
                                        for <?php echo (int)$recipe['drying_time_minutes']; ?> min
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($recipe['notes'])): ?>
                                <p class="small text-muted mb-2"><?php echo nl2br(htmlspecialchars($recipe['notes'])); ?></p>
                            <?php endif; ?>
                            <hr>
                            <p class="small mb-1"><strong>Input Items:</strong><br><?php echo nl2br(htmlspecialchars($recipe['input_items'])); ?></p>
                            <p class="small mb-0"><strong>Process Steps:</strong><br><?php echo nl2br(htmlspecialchars($recipe['process_steps'])); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
