<?php
require_once 'init.php';

echo "=== Testing Global Project Logic ===\n";

try {
    TTransaction::open('database');
    
    // 1. Create Global Template
    $global = new Projeto;
    $global->nome = 'Global Template ' . rand(100,999);
    $global->is_template = '1';
    $global->ativo = 1;
    $global->company_template_id = NULL; // Explicitly NULL
    $global->store();
    echo "Created Global Template ID: {$global->id}\n";
    
    // 2. Create Company Template
    $company = new CompanyTemplate; // Assuming this model exists
    $company->name = 'Test Company';
    $company->store();
    
    $local = new Projeto;
    $local->nome = 'Local Template ' . rand(100,999);
    $local->is_template = '1';
    $local->ativo = 1;
    $local->company_template_id = $company->id;
    $local->store();
    echo "Created Local Template ID: {$local->id} for Company {$company->id}\n";
    
    // 3. Test Query for Global Templates
    $results = Projeto::where('company_template_id', 'IS', NULL)
                      ->where('ativo', '=', 1)
                      ->where('is_template', '=', '1')
                      ->load();
                      
    $foundGlobal = false;
    $foundLocal = false;
    
    foreach ($results as $p) {
        if ($p->id == $global->id) $foundGlobal = true;
        if ($p->id == $local->id) $foundLocal = true;
    }
    
    if ($foundGlobal) echo "✅ Global Template FOUND in query.\n";
    else echo "❌ Global Template NOT found.\n";
    
    if (!$foundLocal) echo "✅ Local Template NOT found in query (Correct).\n";
    else echo "❌ Local Template FOUND in query (Incorrect).\n";
    
    // Cleanup
    $global->delete();
    $local->delete();
    $company->delete();
    
    TTransaction::close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    TTransaction::rollback();
}
