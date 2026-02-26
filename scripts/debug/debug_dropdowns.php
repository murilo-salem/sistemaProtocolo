<?php
require_once 'init.php';

// Mock session
$_SESSION['userid'] = 1; // Admin

echo "=== SystemChat::getLatestMessages ===\n";
try {
    ob_start();
    SystemChat::getLatestMessages([]);
    $output = ob_get_clean();
    echo "Output length: " . strlen($output) . "\n";
    echo "Sample: " . substr($output, 0, 100) . "...\n";
    if (strpos($output, '<li') !== false) {
        echo "✅ Valid HTML list returned.\n";
    } else {
        echo "❌ Invalid output (expected HTML li).\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== NotificationList::getLatestNotifications ===\n";
try {
    ob_start();
    NotificationList::getLatestNotifications([]);
    $output = ob_get_clean();
    echo "Output length: " . strlen($output) . "\n";
    echo "Sample: " . substr($output, 0, 100) . "...\n";
    if (strpos($output, '<li') !== false) {
        echo "✅ Valid HTML list returned.\n";
    } else {
        echo "❌ Invalid output (expected HTML li).\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
