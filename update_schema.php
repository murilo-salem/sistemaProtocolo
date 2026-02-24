<?php
// Load Adianti Framework
require_once 'init.php';

try {
    TTransaction::open('database');
    $conn = TTransaction::get();
    
    // 1. Create company_templates
    $sql1_pg = "CREATE TABLE IF NOT EXISTS company_templates (
        id SERIAL PRIMARY KEY, 
        name VARCHAR(255) NOT NULL
    )";
    
    // 2. Create company_doc_templates
    $sql2_pg = "CREATE TABLE IF NOT EXISTS company_doc_templates (
        id SERIAL PRIMARY KEY, 
        company_template_id INT NOT NULL,
        document_name VARCHAR(255) NOT NULL,
        is_required BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (company_template_id) REFERENCES company_templates(id)
    )";
    
    // 3. Alter projeto table
    $sql3_pg = "ALTER TABLE projeto ADD COLUMN company_template_id INT NULL";
    $sql3_fk_pg = "ALTER TABLE projeto ADD CONSTRAINT fk_projeto_company_template FOREIGN KEY (company_template_id) REFERENCES company_templates(id)";

    // 4. Alter projeto_documento table
    $sql4_pg_1 = "ALTER TABLE projeto_documento ADD COLUMN content TEXT NULL";
    $sql4_pg_2 = "ALTER TABLE projeto_documento ADD COLUMN status VARCHAR(50) DEFAULT 'pendente'";
    
    // execution
    $conn->exec($sql1_pg);
    echo "Created company_templates.\n";
    
    $conn->exec($sql2_pg);
    echo "Created company_doc_templates.\n";
    
    try {
        $conn->exec($sql3_pg);
        echo "Added company_template_id to projeto.\n";
        $conn->exec($sql3_fk_pg);
        echo "Added FK to projeto.\n";
    } catch (Exception $e) {
        echo "Column company_template_id might already exist or FK error: " . $e->getMessage() . "\n";
    }

    try {
        $conn->exec($sql4_pg_1);
        echo "Added content to projeto_documento.\n";
    } catch (Exception $e) {
        echo "Column content might already exist.\n";
    }
    
    try {
        $conn->exec($sql4_pg_2);
        echo "Added status to projeto_documento.\n";
    } catch (Exception $e) {
        echo "Column status might already exist.\n";
    }
    
    TTransaction::close();
    echo "Schema update completed successfully.";
    
} catch (Exception $e) {
    echo "<b>Error:</b> " . $e->getMessage() . "<br>";
    echo "<b>Trace:</b> " . $e->getTraceAsString() . "<br>";
    try {
        TTransaction::rollback();
    } catch (Exception $e2) {
        echo "Rollback failed: " . $e2->getMessage();
    }
}
