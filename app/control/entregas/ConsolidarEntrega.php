<?php
/**
 * ConsolidarEntrega
 *
 * @version    1.0
 * @package    control
 * @subpackage entregas
 * @author     Antigravity
 * @copyright  Copyright (c) 2024
 */
class ConsolidarEntrega extends TPage
{
    public static function onConsolidar($param)
    {
        try {
            TTransaction::open('database');
            
            $entrega = new Entrega($param['id']);
            
            if ($entrega->status != 'aprovado') {
                throw new Exception('Apenas entregas aprovadas podem ser consolidadas');
            }
            
            $documentos = $entrega->get_documentos();
            
            if (empty($documentos)) {
                throw new Exception('Nenhum documento encontrado para consolidar.');
            }
            
            $pasta_consolidado = "files/consolidados/{$entrega->ano_referencia}/{$entrega->mes_referencia}";
            
            if (!is_dir($pasta_consolidado)) {
                mkdir($pasta_consolidado, 0777, true);
            }
            
            $nome_arquivo = "Consolidado_{$entrega->cliente_id}_{$entrega->projeto_id}_" . time() . ".zip";
            $caminho_completo = "{$pasta_consolidado}/{$nome_arquivo}";
            
            if (!class_exists('ZipArchive')) {
                throw new Exception('Extensão ZipArchive não encontrada no PHP.');
            }
            
            $zip = new ZipArchive;
            if ($zip->open($caminho_completo, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new Exception('Não foi possível criar o arquivo ZIP.');
            }
            
            foreach ($documentos as $doc_nome => $doc_arquivo) {
                if (file_exists($doc_arquivo)) {
                    $extensao = pathinfo($doc_arquivo, PATHINFO_EXTENSION);
                    // Sanitizar nome do arquivo dentro do ZIP (remover caracteres especiais)
                    $nome_limpo = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $doc_nome);
                    $zip->addFile($doc_arquivo, "{$nome_limpo}.{$extensao}");
                }
            }
            
            $zip->close();
            
            // Update Entity
            $entrega->consolidado = 1;
            $entrega->arquivo_consolidado = $caminho_completo;
            $entrega->store();
            
            // Notify User
            try {
                $subject = "Consolidação Disponível: " . $entrega->mes_referencia . "/" . $entrega->ano_referencia;
                $msg_body = "O relatório consolidado da sua entrega referente a " . str_pad($entrega->mes_referencia, 2, '0', STR_PAD_LEFT) . "/" . $entrega->ano_referencia . " já está disponível para download.";
                
                NotificationService::send(TSession::getValue('userid'), $entrega->cliente_id, $subject, $msg_body);
            } catch (Exception $e) {}
            
            TTransaction::close();
            
            new TMessage('info', 'Arquivo ZIP gerado e salvo com sucesso!');
            
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}