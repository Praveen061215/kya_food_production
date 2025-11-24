<?php
// KYA Food Production - Chatbot Intent Engine
// Simple rule-based intent and entity detection

class ChatbotIntentEngine {
    public static function detectIntent($message, $context = []) {
        $text = mb_strtolower($message);
        $entities = [];

        $section = self::extractSection($text, $context);
        if ($section !== null) {
            $entities['section'] = $section;
        }

        if (self::matches($text, ['expiry', 'expiring', 'expired'])) {
            return [
                'intent' => 'view_expiry_summary',
                'entities' => $entities,
            ];
        }

        if (self::matches($text, ['stock alert', 'low stock', 'critical stock'])) {
            return [
                'intent' => 'view_stock_alerts',
                'entities' => $entities,
            ];
        }

        if (self::matches($text, ['add item', 'new item', 'create item'])) {
            return [
                'intent' => 'go_add_inventory_item',
                'entities' => $entities,
            ];
        }

        if (self::matches($text, ['inventory', 'stock']) && !self::matches($text, ['report', 'expiry', 'expiring'])) {
            return [
                'intent' => 'view_inventory_summary',
                'entities' => $entities,
            ];
        }

        if (self::matches($text, ['receiving', 'receive materials'])) {
            return [
                'intent' => 'go_section1_receiving',
                'entities' => $entities,
            ];
        }

        if (self::matches($text, ['storage', 'capacity'])) {
            return [
                'intent' => 'go_section1_storage',
                'entities' => $entities,
            ];
        }

        if (self::matches($text, ['temperature', 'humidity'])) {
            return [
                'intent' => 'go_section1_temperature',
                'entities' => $entities,
            ];
        }

        if (self::matches($text, ['processing log', 'process log', 'batches']) && self::matches($text, ['log'])) {
            return [
                'intent' => 'view_processing_logs',
                'entities' => $entities,
            ];
        }

        if (self::matches($text, ['processing report', 'yield report', 'process report'])) {
            return [
                'intent' => 'view_processing_report',
                'entities' => $entities,
            ];
        }

        if (self::matches($text, ['financial report', 'inventory value', 'stock value'])) {
            return [
                'intent' => 'view_financial_report',
                'entities' => $entities,
            ];
        }

        if (self::matches($text, ['quality report', 'inspection report', 'pass rate'])) {
            return [
                'intent' => 'view_quality_report',
                'entities' => $entities,
            ];
        }

        if (self::matches($text, ['notification', 'alerts', 'messages']) && !self::matches($text, ['stock'])) {
            return [
                'intent' => 'view_notifications',
                'entities' => $entities,
            ];
        }

        if (self::matches($text, ['profile', 'my account', 'change password'])) {
            return [
                'intent' => 'go_profile',
                'entities' => $entities,
            ];
        }

        if (self::matches($text, ['help', 'what can you do', 'how to use', 'guide'])) {
            return [
                'intent' => 'show_help',
                'entities' => $entities,
            ];
        }

        return [
            'intent' => 'small_talk',
            'entities' => $entities,
        ];
    }

    protected static function matches($text, array $keywords) {
        foreach ($keywords as $keyword) {
            if (mb_strpos($text, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    protected static function extractSection($text, $context) {
        if (preg_match('/section\s*([1-7])/i', $text, $m)) {
            return (int) $m[1];
        }

        if (mb_strpos($text, 'raw material') !== false) {
            return 1;
        }
        if (mb_strpos($text, 'dehydration') !== false) {
            return 2;
        }
        if (mb_strpos($text, 'packaging') !== false) {
            return 3;
        }
        if (mb_strpos($text, 'inventory') !== false) {
            return 4;
        }
        if (mb_strpos($text, 'processing') !== false) {
            return 5;
        }
        if (mb_strpos($text, 'orders') !== false) {
            return 6;
        }
        if (mb_strpos($text, 'report') !== false) {
            return 7;
        }

        if (!empty($context['last_section'])) {
            return (int) $context['last_section'];
        }

        return null;
    }
}
