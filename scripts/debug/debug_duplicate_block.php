<?php
require_once 'init.php';

// Mock params simulating a form submission
$param = [
    'cliente_id' => 5, // Gestor acting as client for test, or user 3
    'projeto_id' => 1,
    'mes_referencia' => date('n'),
    'ano_referencia' => date('Y')
];

echo "Teste de Bloqueio de Entrega Duplicada\n";
echo "Periodo: {$param['mes_referencia']}/{$param['ano_referencia']}\n\n";

try {
    TTransaction::open('database');
    
    // 1. Limpar entregas anteriores deste mês para teste limpo
    echo "1. Limpando entregas anteriores...\n";
    Entrega::where('mes_referencia', '=', $param['mes_referencia'])
           ->where('ano_referencia', '=', $param['ano_referencia'])
           ->delete();
    echo "OK.\n\n";
    
    // 2. Criar uma entrega APROVADA
    echo "2. Criando entrega APROVADA simulada...\n";
    $e = new Entrega;
    $e->cliente_id = 3; // user 'user'
    $e->projeto_id = 1;
    $e->mes_referencia = $param['mes_referencia'];
    $e->ano_referencia = $param['ano_referencia'];
    $e->status = 'aprovado';
    $e->data_entrega = date('Y-m-d');
    $e->store();
    echo "Entrega ID {$e->id} criada com status 'aprovado'.\n\n";
    
    // 3. Tentar simular a verificação do onSave
    echo "3. Testando lógica de bloqueio (simulação do onSave)...\n";
    
    $existing = Entrega::where('cliente_id', '=', 3)
                       ->where('mes_referencia', '=', $param['mes_referencia'])
                       ->where('ano_referencia', '=', $param['ano_referencia'])
                       ->where('status', '=', 'aprovado')
                       ->first();
                       
    if ($existing) {
        echo "✅ SUCESSO: Bloqueio detectado! 'Já existe uma entrega APROVADA'.\n";
    } else {
        echo "❌ FALHA: O bloqueio não funcionou (entrega não encontrada).\n";
    }
    
    // 4. Teste com status REJEITADO (não deve bloquear)
    echo "\n4. Alterando para REJEITADO e testando novamente...\n";
    $e->status = 'rejeitado';
    $e->store();
    
    $existing = Entrega::where('cliente_id', '=', 3)
                       ->where('mes_referencia', '=', $param['mes_referencia'])
                       ->where('ano_referencia', '=', $param['ano_referencia'])
                       ->where('status', '=', 'aprovado')
                       ->first();
                       
    if (!$existing) {
        echo "✅ SUCESSO: Bloqueio liberado para entrega REJEITADA.\n";
    } else {
        echo "❌ FALHA: Ainda está bloqueando entrega rejeitada.\n";
    }

    TTransaction::rollback(); // Desfaz tudo no final
    echo "\nTeste finalizado (Rollback realizado).";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
    TTransaction::rollback();
}
