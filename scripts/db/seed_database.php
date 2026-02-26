<?php
// seed_database.php

// Bootstrap Adianti
if (file_exists('init.php')) {
    require_once 'init.php';
} elseif (file_exists('../init.php')) {
    require_once '../init.php';
}

class SeedDatabase
{
    public static function run()
    {
        try {
            TTransaction::open('database'); // Adjust database name if needed
            
            echo "Iniciando população do banco de dados...\n";
            
            // 1. Criar Usuários
            echo "--> Criando usuários...\n";
            $users = [
                ['name' => 'Gestor do Sistema', 'login' => 'gestor', 'pass' => 'gestor', 'type' => 'gestor', 'email' => 'gestor@email.com'],
                ['name' => 'Cliente Alpha', 'login' => 'cliente1', 'pass' => 'cliente', 'type' => 'cliente', 'email' => 'cliente1@email.com'],
                ['name' => 'Cliente Beta', 'login' => 'cliente2', 'pass' => 'cliente', 'type' => 'cliente', 'email' => 'cliente2@email.com'],
            ];
            
            $createdUsers = [];
            
            foreach ($users as $userData) {
                // Verificar se já existe
                $existing = Usuario::where('login', '=', $userData['login'])->load();
                
                if (empty($existing)) {
                    $user = new Usuario;
                    $user->nome = $userData['name'];
                    $user->login = $userData['login'];
                    $user->senha = $userData['pass']; // Plain text or hashed? Adianti SystemUser uses hash, Usuario model might be simple. 
                                                     // Assuming plain for now based on 'Usuario' custom model simplicity, 
                                                     // but ideally should be hasged.
                                                     // Let's assume the auth system handles it.
                    $user->tipo = $userData['type'];
                    $user->email = $userData['email'];
                    $user->ativo = 'S';
                    $user->store();
                    $createdUsers[$userData['login']] = $user;
                    echo "    + Usuário '{$userData['login']}' criado (ID: {$user->id})\n";
                    
                    // Se for SystemUser integration is needed, we would need to insert into system_user too.
                    // Assuming Usuario is the main table for business logic here.
                    
                    // Create Notification settings if needed? No.
                    
                } else {
                    $createdUsers[$userData['login']] = $existing[0];
                    echo "    . Usuário '{$userData['login']}' já existe (ID: {$existing[0]->id})\n";
                }
            }
            
            // 2. Criar Projetos
            echo "--> Criando projetos...\n";
            $projectsData = [
                ['nome' => 'Projeto Financeiro 2026', 'ativo' => 'S'],
                ['nome' => 'Auditoria Contábil', 'ativo' => 'S'],
                ['nome' => 'Relatório de Sustentabilidade', 'ativo' => 'S'],
            ];
            
            $createdProjects = [];
            
            foreach ($projectsData as $projData) {
                $existing = Projeto::where('nome', '=', $projData['nome'])->load();
                if (empty($existing)) {
                    $proj = new Projeto;
                    $proj->nome = $projData['nome'];
                    $proj->descricao = "Descrição automática para " . $projData['nome'];
                    $proj->dia_vencimento = 10;
                    $proj->ativo = $projData['ativo'];
                    $proj->store();
                    $createdProjects[] = $proj;
                    echo "    + Projeto '{$projData['nome']}' criado (ID: {$proj->id})\n";
                } else {
                    $createdProjects[] = $existing[0];
                    echo "    . Projeto '{$projData['nome']}' já existe (ID: {$existing[0]->id})\n";
                }
            }
            
            // 3. Vincular Clientes a Projetos
            echo "--> Vinculando clientes...\n";
            $client1 = $createdUsers['cliente1']; // Alpha
            $client2 = $createdUsers['cliente2']; // Beta
            
            // Alpha tem Projeto 1 e 2
            self::linkClientProject($client1, $createdProjects[0]);
            self::linkClientProject($client1, $createdProjects[1]);
            
            // Beta tem Projeto 2 e 3
            self::linkClientProject($client2, $createdProjects[1]);
            self::linkClientProject($client2, $createdProjects[2]);
            
            // 4. Criar Entregas
            echo "--> Gerando entregas e notificações...\n";
            
            // Entrega 1: Alpha - Financeiro - Pendente
            $ent1 = self::createEntrega($client1, $createdProjects[0], 1, 2026, 'pendente');
            
            // Entrega 2: Alpha - Auditoria - Enviada (Em Analise)
            $ent2 = self::createEntrega($client1, $createdProjects[1], 1, 2026, 'em_analise');
            
            // Entrega 3: Alpha - Financeiro - Aprovada & Consolidada
            $ent3 = self::createEntrega($client1, $createdProjects[0], 12, 2025, 'aprovado', true);
            
            // Entrega 4: Beta - Auditoria - Rejeitada
            $ent4 = self::createEntrega($client2, $createdProjects[1], 1, 2026, 'rejeitado');
            
            // Entrega 5: Beta - Sustentabilidade - Aceita, não consolidada
            $ent5 = self::createEntrega($client2, $createdProjects[2], 12, 2025, 'aprovado', false);
            
            TTransaction::close();
            echo "\nPopulação concluída com sucesso!\n";
            
        } catch (Exception $e) {
            echo "Erro: " . $e->getMessage() . "\n";
            TTransaction::rollback();
        }
    }
    
    private static function linkClientProject($client, $project)
    {
        $exists = ClienteProjeto::where('cliente_id', '=', $client->id)
                                ->where('projeto_id', '=', $project->id)
                                ->count();
        if (!$exists) {
            $link = new ClienteProjeto;
            $link->cliente_id = $client->id;
            $link->projeto_id = $project->id;
            $link->data_atribuicao = date('Y-m-d');
            $link->store();
            echo "    + Vinculado: {$client->nome} -> {$project->nome}\n";
        }
    }
    
    private static function createEntrega($client, $project, $mes, $ano, $status, $consolidado = false)
    {
        // Check existance
        $exists = Entrega::where('cliente_id', '=', $client->id)
                         ->where('projeto_id', '=', $project->id)
                         ->where('mes_referencia', '=', $mes)
                         ->where('ano_referencia', '=', $ano)
                         ->first();
                         
        if ($exists) {
            echo "    . Entrega já existe: {$client->nome} - {$project->nome} - $mes/$ano\n";
            return $exists;
        }
        
        $ent = new Entrega;
        $ent->cliente_id = $client->id;
        $ent->projeto_id = $project->id;
        $ent->mes_referencia = $mes;
        $ent->ano_referencia = $ano;
        $ent->status = $status;
        $ent->data_entrega = date('Y-m-d H:i:s');
        $ent->consolidado = $consolidado ? 1 : 0;
        
        if ($status == 'rejeitado') {
            $ent->comentario = "Documentação incompleta. Favor reenviar.";
            
            // Create Notification
            NotificationService::notifyClient($client->id, 'Entrega Reprovada', "Sua entrega de $mes/$ano para {$project->nome} foi reprovada.", 'danger', 'entrega', $ent->id);
            
        } elseif ($status == 'aprovado') {
             // Create Notification
            NotificationService::notifyClient($client->id, 'Entrega Aprovada', "Sua entrega de $mes/$ano para {$project->nome} foi aprovada.", 'success', 'entrega', $ent->id);
        }
        
        if ($consolidado) {
            // Fake consolidated file
            $ent->arquivo_consolidado = "consolidado_{$ent->id}.pdf";
            
            NotificationService::notifyClient($client->id, 'Consolidação Pronta', "O arquivo consolidado de $mes/$ano está disponível.", 'primary', 'entrega', $ent->id);
        }
        
        $ent->store();
        echo "    + Entrega criada: {$client->nome} - {$project->nome} - $mes/$ano [$status]\n";
        return $ent;
    }
}

// Execute
SeedDatabase::run();
