<?php
// debug_entrega_status.php
if (file_exists('init.php')) {
    require_once 'init.php';
} elseif (file_exists('../init.php')) {
    require_once '../init.php';
}

TTransaction::open('database');
$entregas = Entrega::getObjects();

echo "ID | Status | Consolidado (Raw) | !empty(Consolidado)\n";
echo "---|--------|-------------------|--------------------\n";

foreach ($entregas as $e) {
    echo "{$e->id} | {$e->status} | " . var_export($e->consolidado, true) . " | " . (!empty($e->consolidado) ? 'TRUE' : 'FALSE') . "\n";
}

TTransaction::close();
