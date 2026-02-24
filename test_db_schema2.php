<?php
require 'init.php';
try {
    TTransaction::open('database');
    $conn = TTransaction::get();
    $res = $conn->query("SELECT data_type FROM information_schema.columns WHERE table_name = 'entrega' AND column_name = 'consolidado'")->fetch(PDO::FETCH_ASSOC);
    echo "TIPO CONSOLIDADO: " . $res['data_type'] . "\n";
    TTransaction::close();
} catch (Exception $e) {
    echo $e->getMessage();
}
