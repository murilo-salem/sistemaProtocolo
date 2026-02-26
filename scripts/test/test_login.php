<?php
// Debugging Login
require_once 'init.php';

echo "--- Diagnostic Login ---\n";

try {
    TTransaction::open('database'); // Using default database from config
    echo "DB Connection: OK\n";
    
    // Check Config
    $ini = parse_ini_file('app/config/database.ini');
    echo "DB Name in Config: " . ($ini['name'] ?? 'Undefined') . "\n";

    echo "Searching for user 'admin'...\n";
    $user = Usuario::where('login', '=', 'admin')->first();
    
    if ($user) {
        echo "User Found: ID={$user->id}, Name={$user->nome}, Active={$user->ativo}\n";
        echo "Stored Hash: " . substr($user->senha, 0, 10) . "...\n";
        
        $pass = 'admin';
        if (password_verify($pass, $user->senha)) {
             echo "SUCCESS: Password 'admin' matches stored hash.\n";
        } else {
             echo "FAILURE: Password 'admin' DOES NOT match stored hash.\n";
             echo "Updating hash to new valid one...\n";
             $new_hash = password_hash('admin', PASSWORD_DEFAULT);
             $user->senha = $new_hash;
             $user->store();
             echo "Pass update: OK. New Hash: " . $new_hash . "\n";
        }
    } else {
        echo "FAILURE: User 'admin' NOT FOUND in database.\n";
        echo "Creating user 'admin'...\n";
        $user = new Usuario;
        $user->nome = 'Gestor Admin';
        $user->email = 'admin@sistema.com';
        $user->login = 'admin';
        $user->senha = password_hash('admin', PASSWORD_DEFAULT);
        $user->tipo = 'gestor';
        $user->ativo = '1';
        $user->store();
        echo "User 'admin' created successfully.\n";
    }
    
    TTransaction::close();
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
echo "--- End Diagnostic ---\n";
