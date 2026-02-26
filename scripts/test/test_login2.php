<?php
require_once 'init.php';

try {
    TTransaction::open('database');
    $user = Usuario::autenticar('admin', 'admin');
    if ($user) {
        echo "Login OK. Projetos associados:\n";
        print_r($user->get_projetos());
    } else {
        echo "Usuario incorreto\n";
    }
    TTransaction::close();
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
