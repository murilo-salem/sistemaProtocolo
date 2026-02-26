<?php
// Mock web environment
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';

if (file_exists('init.php')) {
    require_once 'init.php';
} else {
    die("init.php not found");
}

// Mock Session
new TSession;
// Assuming ID 1 is an admin/gestor. If this fails (no user 1), I will try finding a user.
TSession::setValue('userid', 1);
TSession::setValue('username', 'Admin');

echo "--- START OUTPUT ---\n";
NotificationService::getLatestNotifications();
echo "\n--- END OUTPUT ---\n";
