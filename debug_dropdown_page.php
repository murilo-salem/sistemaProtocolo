<?php
require_once 'init.php';
$_SESSION['userid'] = 1;

echo "=== Testing NotificationDropdown ===\n";
try {
    // Autoload check
    if (!class_exists('NotificationDropdown')) {
        echo "❌ Class NotificationDropdown not found by autoloader.\n";
        exit;
    }
    
    echo "Instantiating NotificationDropdown...\n";
    $page = new NotificationDropdown(); 
    
    echo "Calling getLatest...\n";
    ob_start();
    $page->getLatest([]);
    $html = ob_get_clean();
    echo "Output Length: " . strlen($html) . "\n";
    
    if (strpos($html, '<li') !== false) {
        echo "✅ Valid HTML returned.\n";
    } else {
        echo "⚠️ Output: " . substr($html, 0, 100) . "...\n";
    }
    
    echo "Calling show()...\n";
    ob_start();
    $page->show();
    $showOut = ob_get_clean();
    echo "Show called successfully (Output length: " . strlen($showOut) . ").\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
