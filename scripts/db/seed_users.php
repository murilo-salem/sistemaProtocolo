<?php
// seed_users.php
require_once 'init.php';

try {
    TTransaction::open('database');

    // Create or Update Gestor
    $gestor = Usuario::where('login', '=', 'admin')->first();
    if (!$gestor) {
        $gestor = new Usuario;
        $gestor->login = 'admin';
        $gestor->nome = 'Administrador Gestor';
        $gestor->email = 'admin@test.com';
        $gestor->tipo = 'gestor';
        $gestor->ativo = '1';
        $gestor->created_at = date('Y-m-d H:i:s');
    }
    // Always reset password to 'admin' for testing
    $gestor->senha = password_hash('admin', PASSWORD_DEFAULT);
    $gestor->store();

    // Create or Update Client/User
    $cliente = Usuario::where('login', '=', 'user')->first();
    if (!$cliente) {
        $cliente = new Usuario;
        $cliente->login = 'user';
        $cliente->nome = 'Usuário Cliente';
        $cliente->email = 'user@test.com';
        $cliente->tipo = 'usuario'; // Non-gestor type
        $cliente->ativo = '1';
        $cliente->created_at = date('Y-m-d H:i:s');
    }
    // Always reset password to 'user' for testing
    $cliente->senha = password_hash('user', PASSWORD_DEFAULT);
    $cliente->store();

    TTransaction::close();

    echo "Credentials created/updated successfully:\n";
    echo "Gestor: Login='admin', Password='admin'\n";
    echo "Cliente: Login='user', Password='user'\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    TTransaction::rollback();
}
