<?php
// Script to ensure the 'user' login exists
require_once 'init.php';

try {
    TTransaction::open('database');
    
    $login = 'user';
    $password = '123';
    
    // Check if user exists
    $user = Usuario::where('login', '=', $login)->first();
    
    if ($user) {
        echo "Usuário '$login' já existe.\n";
        // Reset password just in case
        $user->senha = password_hash($password, PASSWORD_BCRYPT);
        $user->ativo = 1;
        $user->store();
        echo "Senha redefinida para '$password'.\n";
    } else {
        echo "Criando usuário '$login'...\n";
        $user = new Usuario;
        $user->nome = 'Usuário Teste';
        $user->email = 'user@teste.com';
        $user->login = $login;
        $user->senha = password_hash($password, PASSWORD_BCRYPT);
        $user->tipo = 'cliente'; // Assuming client role
        $user->ativo = 1;
        $user->store();
        echo "Usuário '$login' criado com sucesso!\n";
    }
    
    TTransaction::close();
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
    TTransaction::rollback();
}
