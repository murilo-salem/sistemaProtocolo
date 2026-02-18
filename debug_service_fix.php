<?php
require_once 'init.php';
$_SESSION['userid'] = 1; // Admin

echo "=== Testing NotificationService::getLatestNotifications ===\n";
try {
    ob_start();
    NotificationService::getLatestNotifications([]);
    $output = ob_get_clean();
    echo "Output length: " . strlen($output) . "\n";
    if (strpos($output, '<li') !== false) {
        echo "✅ Valid HTML list returned.\n";
    } else {
        echo "⚠️ Output: " . substr($output, 0, 100) . "...\n";
    }
} catch (Exception $e) {
    echo "❌ Error in getLatestNotifications: " . $e->getMessage() . "\n";
}
