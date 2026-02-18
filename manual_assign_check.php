<?php
require_once 'init.php';

echo "=== Manual Logic Verification ===\n";

try {
    TTransaction::open('database');
    
    // 1. Create User
    $user = new Usuario;
    $user->nome = 'Test User Manual';
    $user->email = 'manual@test.com';
    $user->login = 'manual' . rand(1000,9999);
    $user->senha = password_hash('123', PASSWORD_DEFAULT);
    $user->tipo = 'cliente';
    $user->ativo = 1;
    $user->store();
    $userId = $user->id;
    echo "Created User ID: {$userId}\n";

    // 2. Logic to test: Check if user has link
    $hasLink = false;
    if (!empty($userId)) {
        $hasLink = ClienteProjeto::where('cliente_id', '=', $userId)->count() > 0;
    }
    echo "Has Link (Should be No): " . ($hasLink ? 'Yes' : 'No') . "\n";
    
    $simulatedProjectParam = 1; // ID of template
    
    // 3. Logic Condition
    if (!empty($simulatedProjectParam) && !$hasLink) {
        echo "✅ Condition (Project Selected AND No Link) PASSED.\n";
        
        // Create dummy project for linking
        $proj = new Projeto;
        $proj->nome = 'Dummy Project';
        $proj->ativo = 1;
        $proj->is_template = '0';
        $proj->store();
        $projId = $proj->id;
        echo "Created dummy project ID $projId.\n";
        
        // Verify User exists
        $uCheck = new Usuario($userId);
        if ($uCheck->id) echo "User $userId found in DB.\n";
        else echo "User $userId NOT found.\n";
        
        // Verify Project exists
        $pCheck = new Projeto($projId);
        if ($pCheck->id) echo "Project $projId found in DB.\n";
        else echo "Project $projId NOT found.\n";
        
        // Simulate Assignment
        $vinculo = new ClienteProjeto;
        $vinculo->cliente_id = $userId;
        $vinculo->projeto_id = $projId;
        $vinculo->store();
        echo "Linked dummy project ID $projId.\n";
    } else {
        echo "❌ Condition FAILED.\n";
    }
    
    // 4. Test AGAIN with link
    $hasLink2 = ClienteProjeto::where('cliente_id', '=', $userId)->count() > 0;
    echo "Has Link Now (Should be Yes): " . ($hasLink2 ? 'Yes' : 'No') . "\n";
    
    if (!empty($simulatedProjectParam) && !$hasLink2) {
         echo "❌ Condition Passed (Should Fail).\n";
    } else {
         echo "✅ Condition (Project Selected But Has Link) BLOCKED as expected.\n";
    }
    
    // Cleanup
    $user->delete();
    if (isset($vinculo)) $vinculo->delete();
    
    TTransaction::close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    TTransaction::rollback();
}
