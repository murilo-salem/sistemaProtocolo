<?php
// Script to seed database with sample data
// Usage: php seed_dashboard.php

if (file_exists('init.php')) {
    require_once 'init.php';
} elseif (file_exists('engine.php')) {
    require_once 'engine.php';
} else {
    // Try to manually guess the init
    // Assuming we are in project root
    if (file_exists('lib/adianti/core/AdiantiCoreApplication.php')) {
        require_once 'lib/adianti/core/AdiantiCoreApplication.php';
        $loader = require 'vendor/autoload.php';
        $loader->register();
    } 
}

// Bootstrap Adianti (minimal)
// If there is an existing index.php that loads everything, we might need to mimic it
// Or just try to use the classes directly if autoload works.

// Assuming standard structure:
// We need to load config
try {
    TTransaction::open('database');

    // 1. Create Clients (Users)
    $clients = [];
    $client_names = ['ACME Corp', 'Stark Industries', 'Wayne Enterprises'];
    foreach ($client_names as $name) {
        $login = strtolower(str_replace(' ', '', $name));
        $exists = Usuario::where('login', '=', $login)->first();
        if (!$exists) {
            $user = new Usuario;
            $user->nome = $name;
            $user->login = $login;
            $user->email = "$login@example.com";
            $user->senha = password_hash('123', PASSWORD_BCRYPT);
            $user->tipo = 'cliente';
            $user->ativo = 1;
            $user->store();
            $clients[] = $user;
            echo "Client created: $name\n";
        } else {
            $clients[] = $exists;
        }
    }

    // 2. Create Projects
    $projects = [];
    $proj_names = ['Project Alpha', 'Project Beta', 'Project Gamma'];
    foreach ($proj_names as $i => $name) {
        $exists = Projeto::where('nome', '=', $name)->first();
        if (!$exists) {
            $proj = new Projeto;
            $proj->nome = $name;
            $proj->descricao = "Description for $name";
            $proj->ativo = 1;
            $proj->dia_vencimento = ($i + 5); // 5, 6, 7
            $proj->store();
            $projects[] = $proj;
            echo "Project created: $name\n";
            
            // Link to a random client
            if (!empty($clients)) {
                $client = $clients[$i % count($clients)];
                $link = new ClienteProjeto;
                $link->cliente_id = $client->id;
                $link->projeto_id = $proj->id;
                $link->store();
            }
        } else {
            $projects[] = $exists;
        }
    }

    // 3. Create Deliveries (Entregas)
    // We want:
    // - 2 Pendentes (Backlog, last month) -> Should count as Pending and Late
    // - 1 Pendente (Current month, future date) -> Should count as Pending, not Late
    // - 2 Aprovadas (Current month) -> Should count as Approved
    // - 1 Rejeitada (Current month) -> Count as Rejected
    // - 1 Em analise (Current month) -> Count as In Analysis

    $mes_atual = date('n');
    $ano_atual = date('Y');
    
    $last_month = $mes_atual - 1;
    $last_month_year = $ano_atual;
    if ($last_month == 0) { $last_month = 12; $last_month_year--; }

    if (!empty($projects) && !empty($clients)) {
        // Current Month Approved
        for ($k=0; $k<2; $k++) {
            $entrega = new Entrega;
            $entrega->cliente_id = $clients[0]->id;
            $entrega->projeto_id = $projects[0]->id;
            $entrega->mes_referencia = $mes_atual;
            $entrega->ano_referencia = $ano_atual;
            $entrega->status = 'aprovado';
            $entrega->data_entrega = date('Y-m-d H:i:s');
            $entrega->store();
        }
        
        // Late Entregas (Last Month Pending)
        for ($k=0; $k<2; $k++) {
            $entrega = new Entrega;
            $entrega->cliente_id = $clients[1]->id;
            $entrega->projeto_id = $projects[1]->id;
            $entrega->mes_referencia = $last_month;
            $entrega->ano_referencia = $last_month_year;
            $entrega->status = 'pendente';
            $entrega->store();
        }

        // Current Month Pending (Not Late if day < today, but let's make it future so not late)
        $future_day_proj = $projects[2]; // day 7
        // Wait, if today is > 7, it's late.
        // Let's assume today is 25th.
        // To be NOT late in current month, day_vencimento must be > 25.
        // Or we just don't create "not late" for simplicity if day is small.
        // Let's force one "Em Analise"
        $entrega = new Entrega;
        $entrega->cliente_id = $clients[2]->id;
        $entrega->projeto_id = $projects[2]->id;
        $entrega->mes_referencia = $mes_atual;
        $entrega->ano_referencia = $ano_atual;
        $entrega->status = 'em_analise';
        $entrega->data_entrega = date('Y-m-d H:i:s');
        $entrega->store();

         echo "Deliveries seeded.\n";
    }

    TTransaction::close();
    echo "Done.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    TTransaction::rollback();
}
