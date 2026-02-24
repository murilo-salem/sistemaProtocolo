<?php
require_once 'init.php';

try {
    TTransaction::open('database');
    
    echo "[TESTE] Contagem de Usuários: \n";
    $count_users = Usuario::count();
    echo $count_users . "\n\n";
    
    echo "[TESTE] Projetos completos: \n";
    $projetos = Projeto::getObjects();
    foreach($projetos as $p) {
        echo " - " . $p->nome . " (ID: " . $p->id . ") \n";
        echo "   template id: " . ($p->company_template_id ?: 'null') . "\n";
    }
    
    echo "\n[TESTE] Inserindo nova notificação no PostgreSQL... \n";
    $notificacao = new Notification();
    $notificacao->system_user_id = 1;
    $notificacao->title = "Bem-vindo ao PostgreSQL";
    $notificacao->message = "Teste automatizado da migração da tabela notification.";
    $notificacao->store();
    echo "Notificação salva com ID " . $notificacao->id . ".\n";
    
    echo "\n[TESTE] Buscando Notificações do Admin...\n";
    $notifs = Notification::where('system_user_id', '=', 1)->load();
    foreach($notifs as $n) {
        echo " - " . $n->title . " | " . $n->message . "\n";
    }

    TTransaction::close();
    echo "\nTodos os testes no Model CRUD do framework rodaram sem erros.\n";
} catch (Exception $e) {
    echo "ERRO DURANTE O TESTE: " . $e->getMessage() . "\n";
}
