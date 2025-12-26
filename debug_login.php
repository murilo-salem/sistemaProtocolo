<?php
// Debug login script
require_once 'init.php';

$test_login = 'admin'; // Change to your test login
$test_senha = '123';  // Change to your test password

try {
    TTransaction::open('database');
    
    echo "=== VERIFICANDO USUARIO ===\n\n";
    
    $usuario = Usuario::where('login', '=', $test_login)->first();
    
    if ($usuario) {
        echo "Usuario encontrado:\n";
        echo "  ID: {$usuario->id}\n";
        echo "  Nome: {$usuario->nome}\n";
        echo "  Login: {$usuario->login}\n";
        echo "  Tipo: {$usuario->tipo}\n";
        echo "  Ativo: {$usuario->ativo}\n";
        echo "  Senha (hash): " . substr($usuario->senha, 0, 30) . "...\n\n";
        
        echo "=== TESTANDO SENHA ===\n";
        if (password_verify($test_senha, $usuario->senha)) {
            echo "SUCESSO: Senha '$test_senha' esta CORRETA!\n";
        } else {
            echo "FALHA: Senha '$test_senha' NAO confere com o hash.\n";
            echo "\nVerificando se a senha esta em texto plano...\n";
            if ($usuario->senha === $test_senha) {
                echo "A senha esta em TEXTO PLANO, nao em hash!\n";
                echo "Corrigindo: gerando hash para a senha...\n";
                $usuario->senha = password_hash($test_senha, PASSWORD_BCRYPT);
                $usuario->store();
                echo "Senha corrigida! Tente logar novamente.\n";
            } else {
                echo "A senha no banco nao e nem hash nem texto plano igual.\n";
                echo "Senha no banco: {$usuario->senha}\n";
            }
        }
    } else {
        echo "Usuario '$test_login' NAO encontrado no banco.\n";
        
        // List all users
        echo "\n=== USUARIOS DISPONIVEIS ===\n";
        $users = Usuario::getObjects();
        foreach ($users as $u) {
            echo "  - Login: {$u->login} | Nome: {$u->nome} | Tipo: {$u->tipo}\n";
        }
    }
    
    TTransaction::close();
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
