<?php
// KYA Food Production - Chatbot Handlers
// Bridge between intents and existing modules/data

require_once __DIR__ . '/../config/database.php';

class ChatbotHandlers {
    public static function handle($intent, $entities, $userInfo, $rawMessage) {
        switch ($intent) {
            case 'view_expiry_summary':
                return self::expirySummary($entities, $userInfo);
            case 'view_stock_alerts':
                return self::stockAlertsSummary($entities, $userInfo);
            case 'go_add_inventory_item':
                return self::goAddItem($entities, $userInfo);
            case 'view_inventory_summary':
                return self::inventorySummary($entities, $userInfo);
            case 'go_section1_receiving':
                return self::goSimplePage('modules/section1/receiving.php', 'Opening Section 1 Receiving page for you.', 1, $userInfo);
            case 'go_section1_storage':
                return self::goSimplePage('modules/section1/storage.php', 'Opening Storage Locations for Section 1.', 1, $userInfo);
            case 'go_section1_temperature':
                return self::goSimplePage('modules/section1/temperature_monitoring.php', 'Opening Temperature & Humidity monitoring for Section 1.', 1, $userInfo);
            case 'view_processing_logs':
                return self::goSimplePage('modules/processing/logs.php', 'Opening Processing Logs.', 5, $userInfo);
            case 'view_processing_report':
                return self::goSimplePage('modules/reports/processing_report.php', 'Opening Processing Report with detailed analytics.', 7, $userInfo);
            case 'view_financial_report':
                return self::goSimplePage('modules/reports/financial.php', 'Opening Financial Report for inventory value and performance.', 7, $userInfo);
            case 'view_quality_report':
                return self::goSimplePage('modules/reports/quality.php', 'Opening Quality Control Report.', 7, $userInfo);
            case 'view_notifications':
                return self::goSimplePage('modules/notifications/index.php', 'Showing your system notifications.', null, $userInfo);
            case 'go_profile':
                return self::goSimplePage('modules/profile/profile.php', 'Opening your profile and security settings.', null, $userInfo);
            case 'show_help':
                return self::helpResponse($userInfo);
            case 'small_talk':
            default:
                return self::fallbackResponse($rawMessage, $userInfo);
        }
    }

    protected static function expirySummary($entities, $userInfo) {
        $section = $entities['section'] ?? null;

        if ($section !== null && !SessionManager::canAccessSection($section)) {
            return self::noAccessReply($section);
        }

        try {
            $db = new Database();
            $conn = $db->connect();

            $where = "status = 'active'";
            $params = [];
            if ($section !== null) {
                $where .= ' AND section = ?';
                $params[] = $section;
            }

            $stmt = $conn->prepare("SELECT 
                    COUNT(*) AS total_items,
                    SUM(CASE WHEN expiry_date < CURDATE() THEN 1 ELSE 0 END) AS expired,
                    SUM(CASE WHEN expiry_date >= CURDATE() AND DATEDIFF(expiry_date, CURDATE()) <= 7 THEN 1 ELSE 0 END) AS critical,
                    SUM(CASE WHEN expiry_date >= CURDATE() AND DATEDIFF(expiry_date, CURDATE()) BETWEEN 8 AND 30 THEN 1 ELSE 0 END) AS warning
                FROM inventory
                WHERE $where");
            $stmt->execute($params);
            $row = $stmt->fetch();

            $reply = 'Here is a quick expiry summary';
            if ($section !== null) {
                $reply .= ' for Section ' . $section;
            }
            $reply .= ': ';            
            $reply .= 'Total active items: ' . (int) ($row['total_items'] ?? 0) . '. ';
            $reply .= 'Expired: ' . (int) ($row['expired'] ?? 0) . '. ';
            $reply .= 'Critical (≤7 days): ' . (int) ($row['critical'] ?? 0) . '. ';
            $reply .= 'Warning (8–30 days): ' . (int) ($row['warning'] ?? 0) . '.';

            return [
                'reply' => $reply,
                'actions' => [
                    [
                        'type' => 'open_url',
                        'label' => 'Open Expiry Tracking Page',
                        'url' => 'modules/inventory/expiry_tracking.php' . ($section ? ('?section=' . $section) : ''),
                    ],
                ],
            ];
        } catch (Exception $e) {
            error_log('Chatbot expirySummary error: ' . $e->getMessage());
            return [
                'reply' => 'I could not load the expiry summary right now. Please open the Expiry Tracking page from the menu.',
                'actions' => [],
            ];
        }
    }

    protected static function stockAlertsSummary($entities, $userInfo) {
        $section = $entities['section'] ?? null;

        if ($section !== null && !SessionManager::canAccessSection($section)) {
            return self::noAccessReply($section);
        }

        try {
            $db = new Database();
            $conn = $db->connect();

            $where = '1=1';
            $params = [];
            if ($section !== null) {
                $where .= ' AND section = ?';
                $params[] = $section;
            }

            $stmt = $conn->prepare("SELECT 
                    COUNT(*) AS total_alerts,
                    SUM(CASE WHEN alert_status = 'critical' THEN 1 ELSE 0 END) AS critical,
                    SUM(CASE WHEN alert_status = 'low_stock' THEN 1 ELSE 0 END) AS low_stock
                FROM inventory
                WHERE $where");
            $stmt->execute($params);
            $row = $stmt->fetch();

            $reply = 'Stock alert summary';
            if ($section !== null) {
                $reply .= ' for Section ' . $section;
            }
            $reply .= ': ';
            $reply .= 'Total alerts: ' . (int) ($row['total_alerts'] ?? 0) . '. ';
            $reply .= 'Critical: ' . (int) ($row['critical'] ?? 0) . '. ';
            $reply .= 'Low stock: ' . (int) ($row['low_stock'] ?? 0) . '.';

            return [
                'reply' => $reply,
                'actions' => [
                    [
                        'type' => 'open_url',
                        'label' => 'Open Stock Alerts Page',
                        'url' => 'modules/inventory/stock_alerts.php' . ($section ? ('?section=' . $section) : ''),
                    ],
                ],
            ];
        } catch (Exception $e) {
            error_log('Chatbot stockAlertsSummary error: ' . $e->getMessage());
            return [
                'reply' => 'I could not load stock alerts right now. Please open the Stock Alerts page from the menu.',
                'actions' => [],
            ];
        }
    }

    protected static function inventorySummary($entities, $userInfo) {
        $section = $entities['section'] ?? null;

        if ($section !== null && !SessionManager::canAccessSection($section)) {
            return self::noAccessReply($section);
        }

        try {
            $db = new Database();
            $conn = $db->connect();

            $where = '1=1';
            $params = [];
            if ($section !== null) {
                $where .= ' AND section = ?';
                $params[] = $section;
            }

            $stmt = $conn->prepare("SELECT 
                    COUNT(*) AS total_items,
                    SUM(quantity) AS total_quantity,
                    SUM(quantity * unit_cost) AS total_value
                FROM inventory
                WHERE $where");
            $stmt->execute($params);
            $row = $stmt->fetch();

            $totalItems = (int) ($row['total_items'] ?? 0);
            $totalQty = (float) ($row['total_quantity'] ?? 0);
            $totalValue = (float) ($row['total_value'] ?? 0);

            $reply = 'Inventory overview';
            if ($section !== null) {
                $reply .= ' for Section ' . $section;
            }
            $reply .= ': ';
            $reply .= 'Items: ' . $totalItems . ', ';
            $reply .= 'Total quantity: ' . $totalQty . ', ';
            $reply .= 'Approx. value: ' . formatCurrency($totalValue, 'LKR') . '.';

            return [
                'reply' => $reply,
                'actions' => [
                    [
                        'type' => 'open_url',
                        'label' => 'Open Inventory Page',
                        'url' => 'modules/inventory/index.php' . ($section ? ('?section=' . $section) : ''),
                    ],
                ],
            ];
        } catch (Exception $e) {
            error_log('Chatbot inventorySummary error: ' . $e->getMessage());
            return [
                'reply' => 'I could not load the inventory summary right now. Please open the Inventory page from the menu.',
                'actions' => [],
            ];
        }
    }

    protected static function goAddItem($entities, $userInfo) {
        $section = $entities['section'] ?? null;

        if ($section !== null && !SessionManager::canAccessSection($section)) {
            return self::noAccessReply($section);
        }

        $reply = 'I will take you to the Add Inventory Item form.';
        if ($section !== null) {
            $reply .= ' You can set Section ' . $section . ' in the form.';
        }

        return [
            'reply' => $reply,
            'actions' => [
                [
                    'type' => 'open_url',
                    'label' => 'Go to Add Item',
                    'url' => 'modules/inventory/add_item.php' . ($section ? ('?section=' . $section) : ''),
                ],
            ],
        ];
    }

    protected static function goSimplePage($url, $text, $section, $userInfo) {
        if ($section !== null && !SessionManager::canAccessSection($section)) {
            return self::noAccessReply($section);
        }

        return [
            'reply' => $text,
            'actions' => [
                [
                    'type' => 'open_url',
                    'label' => 'Open Page',
                    'url' => $url,
                ],
            ],
        ];
    }

    protected static function noAccessReply($section) {
        return [
            'reply' => 'You do not have permission to access Section ' . (int) $section . '. Please contact an administrator if you think this is a mistake.',
            'actions' => [],
        ];
    }

    protected static function helpResponse($userInfo) {
        $reply = "I am your KYA Assistant. You can ask me to:\n";
        $reply .= "- Show inventory, expiry, and stock alerts (e.g., 'expiry for section 1')\n";
        $reply .= "- Open key pages (receiving, storage, temperature, processing logs, reports)\n";
        $reply .= "- Open your profile or notifications\n";
        $reply .= "- Get quick summaries instead of navigating manually.\n";
        $reply .= "\nTry something like: 'Show expiry summary for Section 1' or 'Open financial report'.";

        return [
            'reply' => nl2br($reply),
            'actions' => [
                [
                    'type' => 'suggest',
                    'label' => 'Expiry summary Section 1',
                    'value' => 'Show expiry summary for Section 1',
                ],
                [
                    'type' => 'suggest',
                    'label' => 'Financial report',
                    'value' => 'Open financial report',
                ],
            ],
        ];
    }

    protected static function fallbackResponse($rawMessage, $userInfo) {
        $reply = 'I did not fully understand that, but I can help you with inventory, expiry, stock alerts, processing, quality, financial, and navigation. ';
        $reply .= 'Try asking: "Show expiry summary for Section 1" or "Open processing report".';

        return [
            'reply' => $reply,
            'actions' => [
                [
                    'type' => 'suggest',
                    'label' => 'Help & examples',
                    'value' => 'help',
                ],
            ],
        ];
    }
}
