<?php
require_once 'init.php';

echo "=== Testing NotificationList Instantiation ===\n";

try {
    // Simulate TXMLBreadCrumb context if needed, but it just reads file
    $page = new NotificationList();
    echo "✅ NotificationList instantiated successfully (Construct passed).\n";
} catch (Exception $e) {
    echo "❌ Error during instantiation: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Testing getLatestNotifications ===\n";
try {
    ob_start();
    NotificationList::getLatestNotifications([]);
    $output = ob_get_clean();
    if (strpos($output, '<li') !== false) {
        echo "✅ getLatestNotifications returned HTML.\n";
    } else {
        echo "⚠️ Output: " . substr($output, 0, 50) . "...\n";
    }
} catch (Exception $e) {
    echo "❌ Error in getLatestNotifications: " . $e->getMessage() . "\n";
}
