<?php
// KYA Food Production - Chatbot API Endpoint
// Handles chat messages, intent detection, and action routing

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/ChatbotIntentEngine.php';
require_once __DIR__ . '/../includes/ChatbotHandlers.php';

header('Content-Type: application/json');

SessionManager::start();

if (!SessionManager::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');

if ($message === '') {
    echo json_encode([
        'success' => true,
        'reply' => 'Please type a question or request, for example: "Show expiry items for Section 1".',
        'actions' => []
    ]);
    exit();
}

$userInfo = SessionManager::getUserInfo();
$context = [
    'user' => $userInfo,
    'last_intent' => $_SESSION['chatbot_last_intent'] ?? null,
    'last_section' => $_SESSION['chatbot_last_section'] ?? null,
];

$intentResult = ChatbotIntentEngine::detectIntent($message, $context);
$intent = $intentResult['intent'];
$entities = $intentResult['entities'];

$_SESSION['chatbot_last_intent'] = $intent;
if (isset($entities['section'])) {
    $_SESSION['chatbot_last_section'] = $entities['section'];
}

$response = ChatbotHandlers::handle($intent, $entities, $userInfo, $message);

echo json_encode(array_merge(['success' => true], $response));
