<?php
require 'init.php';
try {
    TTransaction::open('database');
    $conn = TTransaction::get();
    $res = $conn->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'entrega'")->fetchAll(PDO::FETCH_ASSOC);
    print_r($res);
    TTransaction::close();
} catch (Exception $e) {
    echo $e->getMessage();
}
