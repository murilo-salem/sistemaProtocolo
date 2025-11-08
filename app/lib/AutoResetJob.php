<?php
// ============================================================================
// ARQUIVO: app/lib/AutoResetJob.php
// Este arquivo deve ser executado mensalmente via CRON ou manualmente
// ============================================================================

class AutoResetJob
{
    /**
     * Cria novas entregas pendentes para o mês atual
     * Deve ser executado no dia 1º de cada mês
     */
    public static function executar()
    {
        try {
            TTransaction::open('database');
            
            $mes_atual = date('n');
            $ano_atual = date('Y');
            
            echo "Iniciando reset mensal para {$mes_atual}/{$ano_atual}\n";
            
            // Buscar todos os vínculos ativos
            $vinculos = ClienteProjeto::all();
            
            $criadas = 0;
            $existentes = 0;
            
            foreach ($vinculos as $vinculo) {
                // Verificar se cliente e projeto estão ativos
                $cliente = new Usuario($vinculo->cliente_id);
                $projeto = new Projeto($vinculo->projeto_id);
                
                if (!$cliente->ativo || !$projeto->ativo) {
                    continue;
                }
                
                // Verificar se já existe entrega para este mês
                $entrega_existe = Entrega::where('cliente_id', '=', $vinculo->cliente_id)
                                        ->where('projeto_id', '=', $vinculo->projeto_id)
                                        ->where('mes_referencia', '=', $mes_atual)
                                        ->where('ano_referencia', '=', $ano_atual)
                                        ->first();
                
                if (!$entrega_existe) {
                    // Criar nova entrega pendente
                    $entrega = new Entrega;
                    $entrega->cliente_id = $vinculo->cliente_id;
                    $entrega->projeto_id = $vinculo->projeto_id;
                    $entrega->mes_referencia = $mes_atual;
                    $entrega->ano_referencia = $ano_atual;
                    $entrega->status = 'pendente';
                    $entrega->store();
                    
                    $criadas++;
                    echo "Entrega criada: Cliente {$cliente->nome} - Projeto {$projeto->nome}\n";
                } else {
                    $existentes++;
                }
            }
            
            TTransaction::close();
            
            echo "\nReset concluído!\n";
            echo "Entregas criadas: {$criadas}\n";
            echo "Entregas já existentes: {$existentes}\n";
            
            return [
                'sucesso' => true,
                'criadas' => $criadas,
                'existentes' => $existentes
            ];
            
        } catch (Exception $e) {
            TTransaction::rollback();
            echo "Erro: " . $e->getMessage() . "\n";
            
            return [
                'sucesso' => false,
                'erro' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Verifica se precisa executar o reset
     * Pode ser chamado em qualquer página do sistema
     */
    public static function verificarReset()
    {
        try {
            $ultimo_reset = TSession::getValue('ultimo_reset');
            $hoje = date('Y-m-d');
            
            // Se é dia 1 e ainda não executou hoje
            if (date('j') == 1 && $ultimo_reset != $hoje) {
                self::executar();
                TSession::setValue('ultimo_reset', $hoje);
            }
        } catch (Exception $e) {
            // Log do erro mas não interrompe a aplicação
            error_log("Erro no reset automático: " . $e->getMessage());
        }
    }
}