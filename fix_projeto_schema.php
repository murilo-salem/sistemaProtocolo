<?php
require 'init.php';
try {
    TTransaction::open('database');
    $conn = TTransaction::get();
    $conn->exec("UPDATE projeto SET company_template_id = NULL WHERE company_template_id = ''");
    TTransaction::close();
    echo "DB fixed\n";
} catch (Exception $e) {
    echo $e->getMessage();
}
