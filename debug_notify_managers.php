<?php
require_once 'init.php';

echo "Testing NotificationService::notifyManagers...\n";

try {
    // 1. Snapshot count
    TTransaction::open('database');
    $countBefore = Notification::count();
    TTransaction::close();
    
    // 2. Call service
    NotificationService::notifyManagers(
        'Teste de Debug', 
        'Esta é uma notificação de teste gerada pelo script debug_notify_managers.php', 
        'info'
    );
    
    // 3. Check count again
    TTransaction::open('database');
    $countAfter = Notification::count();
    
    if ($countAfter > $countBefore) {
        echo "SUCCESS: Notification count increased from $countBefore to $countAfter.\n";
        
        // Show the new notification
        $new = Notification::orderBy('id', 'desc')->first();
        echo "Created Notification ID: {$new->id}\n";
        echo "Title: {$new->title}\n";
        echo "User ID: {$new->system_user_id}\n";
    } else {
        echo "FAILURE: Notification count remained $countBefore.\n";
    }
    TTransaction::close();
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
