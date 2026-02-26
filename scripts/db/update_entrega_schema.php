<?php
$pdo = new PDO('pgsql:host=localhost;port=5432;dbname=banco_completo', 'postgres', '123');
$pdo->exec("ALTER TABLE entrega ADD COLUMN resumo_documentos TEXT;");
echo "Migrated!\n";
