<?php
require_once 'init.php';

// Mock Session via TSession
TSession::setValue('userid', 1);
TSession::setValue('username', 'Admin Test');
TSession::setValue('login', 'admin');

echo "=== Testing Edit Delivery and Self-Validation ===\n";

try {
    TTransaction::open('database');

    // 1. Setup Data
    $user = new Usuario(1); // Admin
    
    // Create Project
    $proj = new Projeto;
    $proj->nome = 'Test Project Edit ' . rand(100,999);
    $proj->ativo = 1;
    $proj->store();
    
    // Create Doc Requirement
    $docReq = new ProjetoDocumento;
    $docReq->projeto_id = $proj->id;
    $docReq->nome_documento = 'DocTeste';
    $docReq->obrigatorio = 1;
    $docReq->store();
    
    // Create Initial Delivery
    $entrega = new Entrega;
    $entrega->cliente_id = $user->id;
    $entrega->projeto_id = $proj->id;
    $entrega->mes_referencia = date('n');
    $entrega->ano_referencia = date('Y');
    $entrega->status = 'pendente';
    $entrega->data_entrega = date('Y-m-d H:i:s', strtotime('-1 hour'));
    $entrega->documentos_json = json_encode(['DocTeste' => 'app/uploads/old_file.pdf']);
    $entrega->store();
    
    echo "Created Initial Delivery ID: {$entrega->id} (Status: {$entrega->status})\n";
    
    TTransaction::close(); // Commit setup
    
    // 2. Test Editing (Simulate onSave)
    // We will create a dummy file in tmp
    if (!file_exists('tmp')) mkdir('tmp');
    file_put_contents('tmp/new_file.pdf', 'dummy content');
    
    $param = [
        'cliente_id' => $user->id,
        'projeto_id' => $proj->id,
        'mes_referencia' => date('n'),
        'ano_referencia' => date('Y'),
        'doc_' . $docReq->id => 'new_file.pdf' // Simulate upload
    ];
    
    echo "Simulating onSave with new file...\n";
    
    // We need to instantiate form or call static method? onSave is not static.
    // Use the same trick as before: manual logic verification or try instantiation.
    // Since we want to test the LOGIC mainly.
    
    // Let's rely on manual logic replication for testing OR use a helper class?
    // Actually, onSave is complex. Let's try to instantiate EntregaForm.
    // It uses TPage.
    
    try {
        $form = new EntregaForm(['projeto_id' => $proj->id]);
        $form->onSave($param);
        echo "onSave executed successfully.\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'BreadCrumb') !== false) {
             // Ignore breadcrumb error if it happens
             echo "Ignored BreadCrumb error.\n";
        } else {
             echo "Error during onSave: " . $e->getMessage() . "\n";
        }
    }
    
    // Verify Update
    TTransaction::open('database');
    $check = new Entrega($entrega->id);
    $docs = json_decode($check->documentos_json, true);
    
    if (strpos($docs['DocTeste'], 'new_file.pdf') !== false) {
        echo "✅ Doc Updated!\n";
    } else {
        echo "❌ Doc Fail.\n";
    }
    
    $count = Entrega::where('cliente_id', '=', $user->id)
                    ->where('mes_referencia', '=', date('n'))
                    ->where('ano_referencia', '=', date('Y'))
                    ->count();
                    
    if ($count == 1) {
        echo "✅ No Dups (Count: 1)\n";
    } else {
        echo "❌ Dups (Count: $count)\n";
    }
    
    // 3. Test Self-Validation Block
    echo "\nTesting Self-Validation...\n";
    echo "Session User: " . TSession::getValue('userid') . "\n";
    echo "Delivery Client: " . $entrega->cliente_id . "\n";
    
    try {
        $val = new EntregaValidacao;
        $valParam = ['entrega_id' => $entrega->id];
        $val->onConfirmar($valParam);
        echo "❌ Self-val SUCCEEDED (Fail).\n";
    } catch (Exception $e) {
        echo "✅ Self-val BLOCKED: " . $e->getMessage() . "\n";
    }
    
    // Cleanup
    $check->delete();
    $docReq->delete();
    $proj->delete();
    
    TTransaction::close();
    
} catch (Exception $e) {
    echo "Test Err: " . $e->getMessage();
}
