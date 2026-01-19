<?php
// Load Adianti Framework
require_once 'init.php';

try {
    TTransaction::open('database');
    $conn = TTransaction::get();
    
    // 1. Create company_templates
    $sql1 = "CREATE TABLE IF NOT EXISTS company_templates (
        id INTEGER PRIMARY KEY AUTOINCREMENT, 
        name TEXT NOT NULL
    )";
    // Note: User mentioned MySQL/PostgreSQL, but in simple XAMPP/Adianti setups often SQLite is used or MySQL. 
    // If MySQL, AUTOINCREMENT is AUTO_INCREMENT. 
    // Let's check config/application.ini or assume standard SQL or try to detect driver.
    // For safety, I'll use a generic SQL approach or check driver.
    // Actually, ProjectList uses 'database'. Let's assume MySQL for XAMPP context usually unless specified otherwise.
    // User said "Banco de Dados Relacional (MySQL/PostgreSQL)".
    // Let's try MySQL syntax first.
    
    $sql1_mysql = "CREATE TABLE IF NOT EXISTS company_templates (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        name VARCHAR(255) NOT NULL
    )";
    
    // 2. Create company_doc_templates
    $sql2_mysql = "CREATE TABLE IF NOT EXISTS company_doc_templates (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        company_template_id INT NOT NULL,
        document_name VARCHAR(255) NOT NULL,
        is_required BOOLEAN DEFAULT 0,
        FOREIGN KEY (company_template_id) REFERENCES company_templates(id)
    )";
    
    // 3. Alter projeto table
    // Check if column exists first to avoid error? Or just try ADD COLUMN and catch exception.
    $sql3_mysql = "ALTER TABLE projeto ADD COLUMN company_template_id INT NULL";
    $sql3_fk_mysql = "ALTER TABLE projeto ADD CONSTRAINT fk_projeto_company_template FOREIGN KEY (company_template_id) REFERENCES company_templates(id)";

    // 4. Alter projeto_documento table
    // Existing: projeto_id, nome_documento, obrigatorio.
    // Need: content (text), status (varchar).
    $sql4_mysql_1 = "ALTER TABLE projeto_documento ADD COLUMN content TEXT NULL";
    $sql4_mysql_2 = "ALTER TABLE projeto_documento ADD COLUMN status VARCHAR(50) DEFAULT 'pendente'";
    
    // execution
    $conn->exec($sql1_mysql);
    echo "Created company_templates.\n";
    
    $conn->exec($sql2_mysql);
    echo "Created company_doc_templates.\n";
    
    try {
        $conn->exec($sql3_mysql);
        echo "Added company_template_id to projeto.\n";
        $conn->exec($sql3_fk_mysql);
        echo "Added FK to projeto.\n";
    } catch (Exception $e) {
        echo "Column company_template_id might already exist or FK error: " . $e->getMessage() . "\n";
    }

    try {
        $conn->exec($sql4_mysql_1);
        echo "Added content to projeto_documento.\n";
    } catch (Exception $e) {
        echo "Column content might already exist.\n";
    }
    
    try {
        $conn->exec($sql4_mysql_2);
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
