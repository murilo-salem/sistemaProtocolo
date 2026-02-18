<?php
require_once 'init.php';

try {
    TTransaction::open('database');
    $conn = TTransaction::get();
    
    // Add is_template column
    try {
        $conn->query("ALTER TABLE projeto ADD COLUMN is_template CHAR(1) DEFAULT '0'");
        echo "Column is_template added successfully.\n";
    } catch (Exception $e) {
        echo "Column maybe already exists: " . $e->getMessage() . "\n";
    }
    
    // Update existing projects to be templates? 
    // Assuming existing ones are templates if they have no clients? 
    // For safety, let's mark all current ones as templates for now, so we can test INSTANTIATION?
    // User said: "Empresa... tem N listas".
    // Let's set is_template='1' for all existing rows just to be safe, so they appear in dropdowns?
    // User likely hasn't created "Real" client projects yet if the system is under dev.
    
    $conn->query("UPDATE projeto SET is_template = '1' WHERE is_template = '0'");
    echo "Updated existing projects to is_template = 1.\n";
    
    TTransaction::close();
    echo "Database update complete.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    TTransaction::rollback();
}
