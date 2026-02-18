<?php
/**
 * ConsolidarEntregaV2
 *
 * Controller para consolidação de documentos de uma entrega.
 * Gera PDF único com capa, sumário e todos os documentos.
 *
 * @version    2.0
 * @package    control
 * @subpackage entregas
 * @author     Antigravity
 * @copyright  Copyright (c) 2024
 */
class ConsolidarEntregaV2 extends TPage
{
    /**
     * Executa a consolidação de uma entrega
     */
    public static function onConsolidar($param)
    {
        try {
            if (empty($param['id'])) {
                throw new Exception('ID da entrega não informado.');
            }
            
            // Verifica se já está consolidado para baixar diretamente
            TTransaction::open('database');
            $entrega = new Entrega($param['id']);
            if ($entrega->consolidado == 1 && !empty($entrega->arquivo_consolidado) && file_exists($entrega->arquivo_consolidado)) {
                TTransaction::close();
                // Trigger download via JavaScript to handle AJAX request correctly and avoid binary dump in DOM
                TScript::create("window.location.href = 'engine.php?class=ConsolidarEntregaV2&method=onDownload&id={$param['id']}';");
                return;
            }
            TTransaction::close();
            
            // Usar o serviço de consolidação
            $service = new ConsolidacaoService();
            $resultado = $service->consolidarEntrega($param['id']);
            
            if ($resultado['success']) {
                $mensagem = 'Relatório consolidado gerado com sucesso!';
                
                // Se houve avisos, mostrar também
                if (!empty($resultado['erros'])) {
                    $mensagem .= "\n\nAvisos:\n- " . implode("\n- ", $resultado['erros']);
                }
                
                new TMessage('info', $mensagem, new TAction(['EntregaList', 'onReload']));
                
                // Disparar download automático após consolidação
                TScript::create("setTimeout(function(){ window.location.href = 'engine.php?class=ConsolidarEntregaV2&method=onDownload&id={$param['id']}'; }, 500);");
            } else {
                $erros = implode("\n", $resultado['erros']);
                new TMessage('error', "Erro na consolidação:\n{$erros}");
            }
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
    
    /**
     * Download do arquivo consolidado
     */
    public static function onDownload($param)
    {
        try {
            TTransaction::open('database');
            
            $entrega = new Entrega($param['id']);
            
            if (empty($entrega->arquivo_consolidado) || !file_exists($entrega->arquivo_consolidado)) {
                throw new Exception('Arquivo consolidado não encontrado. Por favor, consolide a entrega primeiro.');
            }
            
            TTransaction::close();
            
            // Forçar download
            $arquivo = $entrega->arquivo_consolidado;
            $nome_download = "Consolidado_" . str_pad($entrega->mes_referencia, 2, '0', STR_PAD_LEFT) . "_" . $entrega->ano_referencia . ".pdf";
            
            // Clear ALL output buffers to prevent file corruption
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $nome_download . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($arquivo));
            
            readfile($arquivo);
            exit;
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }
}

