<?php
/**
 * CheckDeadlinesService
 * Service to check for upcoming deadlines and notify users
 */
class CheckDeadlinesService
{
    public function process()
    {
        try {
            TTransaction::open('database');
            
            // Calculate target date: Today + 3 days
            $target_date = date('Y-m-d', strtotime('+3 days'));
            
            // Log execution
            // file_put_contents('cron_log.txt', date('Y-m-d H:i:s') . " - Running CheckDeadlinesService for $target_date\n", FILE_APPEND);
            
            // Find projects with documents due in 3 days
            // Note: This logic depends on where the deadline is stored. 
            // Assuming 'projeto' table has 'dia_vencimento' (int day of month) or specific deadlines.
            // Based on previous code, 'dia_vencimento' is just a day int.
            
            $day_of_target = date('d', strtotime($target_date));
            $month_of_target = date('m', strtotime($target_date));
            $year_of_target = date('Y', strtotime($target_date));
            
            // Find projects with this due day
            $projetos = Projeto::where('dia_vencimento', '=', $day_of_target)
                               ->where('ativo', '=', '1')
                               ->load();
                               
            if ($projetos) {
                foreach ($projetos as $projeto) {
                    // Find clients linked to this project
                    $links = ClienteProjeto::where('projeto_id', '=', $projeto->id)->load();
                    
                    if ($links) {
                        foreach ($links as $link) {
                            $user = Usuario::find($link->cliente_id);
                            if ($user) {
                                // Check if already notified today about this?
                                // Simplified: Just send notification
                                
                                // Notify Client
                                SystemNotification::register(
                                    $user->id,
                                    'Lembrete de Prazo',
                                    "O projeto {$projeto->nome} possui entrega prevista para dia {$day_of_target}.",
                                    'class=EntregaForm&projeto_id=' . $projeto->id,
                                    'Enviar Agora',
                                    'fa fa-clock-o'
                                );
                                
                                // Notify Managers about this near deadline
                                $gestores = Usuario::where('tipo', 'IN', ['admin', 'gestor'])->load();
                                if ($gestores) {
                                    $sent_to = [];
                                    foreach ($gestores as $ug) {
                                        if (in_array($ug->id, $sent_to)) continue;
                                        
                                        SystemNotification::register(
                                            $ug->id,
                                            'Alerta de Prazo Próximo',
                                            "O cliente {$user->nome} tem entrega prevista para daqui a 3 dias (Projeto: {$projeto->nome}).",
                                            'class=EntregaList',
                                            'Acompanhar',
                                            'fa fa-warning'
                                        );
                                        $sent_to[] = $ug->id;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            TTransaction::close();
            echo "Deadline check finished.";
            
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
            TTransaction::rollback();
        }
    }
}
