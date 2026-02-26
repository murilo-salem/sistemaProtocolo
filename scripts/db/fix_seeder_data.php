<?php
// fix_seeder_data.php
if (file_exists('init.php')) {
    require_once 'init.php';
} elseif (file_exists('../init.php')) {
    require_once '../init.php';
}

TTransaction::open('database');
$entregas = Entrega::getObjects();

echo "Corrigindo dados inválidos do Seeder...\n";

foreach ($entregas as $e) {
    if ($e->consolidado == 1) {
        if (!file_exists($e->arquivo_consolidado)) {
            echo "- Entrega #{$e->id}: Marcada como consolidada, mas arquivo '{$e->arquivo_consolidado}' não existe. Resetando.\n";
            $e->consolidado = 0;
            $e->store();
        } else {
            echo "- Entrega #{$e->id}: OK (Arquivo existe).\n";
        }
    }
}

TTransaction::close();
echo "Correção concluída.\n";
