<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Actions;

use App\Domain\Contracts\Models\Contract;
use App\Domain\Contracts\Models\Invoice;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Action para fazer upload de nota fiscal e extrair dados do XML
 */
class UploadInvoiceAction
{
    /**
     * Faz upload da nota fiscal (PDF e/ou XML) e extrai dados
     * 
     * @param Contract $contract
     * @param array $data Dados do formulário
     * @param UploadedFile|null $pdfFile
     * @param UploadedFile|null $xmlFile
     * @param int $userId
     * @param int $teamId
     * @return Invoice
     */
    public function handle(
        Contract $contract,
        array $data,
        ?UploadedFile $pdfFile,
        ?UploadedFile $xmlFile,
        int $userId,
        int $teamId
    ): Invoice {
        $invoiceData = [
            'contract_id' => $contract->id,
            'team_id' => $teamId,
            'uploaded_by' => $userId,
            'invoice_date' => $data['invoice_date'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'amount' => $data['amount'] ?? null,
            'invoice_number' => $data['invoice_number'] ?? null,
            'description' => $data['description'] ?? null,
            'is_paid' => $data['is_paid'] ?? false,
            'paid_at' => $data['paid_at'] ?? null,
        ];

        // Upload do PDF
        if ($pdfFile) {
            $pdfPath = $this->savePdf($pdfFile, $contract->id, $teamId);
            $invoiceData['pdf_path'] = $pdfPath;
        }

        // Upload e extração do XML
        if ($xmlFile) {
            $xmlPath = $this->saveXml($xmlFile, $contract->id, $teamId);
            $invoiceData['xml_path'] = $xmlPath;
            
            // Tentar extrair dados do XML (apenas para salvar dados brutos)
            $extractedData = $this->extractDataFromXml($xmlFile);
            
            if ($extractedData) {
                // Salvar dados brutos do XML
                $invoiceData['xml_data'] = $extractedData;
                
                // Preencher campos apenas se não foram enviados do frontend
                // (os dados já foram extraídos no frontend via JavaScript)
                if (empty($data['invoice_number']) && isset($extractedData['numero'])) {
                    $invoiceData['invoice_number'] = $extractedData['numero'];
                }
                
                if (empty($data['invoice_date']) && isset($extractedData['data_emissao'])) {
                    $invoiceData['invoice_date'] = $extractedData['data_emissao'];
                }
                
                if (empty($data['amount']) && isset($extractedData['valor_total'])) {
                    $invoiceData['amount'] = $extractedData['valor_total'];
                }
                
                if (empty($data['due_date']) && isset($extractedData['data_vencimento'])) {
                    $invoiceData['due_date'] = $extractedData['data_vencimento'];
                }
            }
        }

        return Invoice::create($invoiceData);
    }

    /**
     * Salva o PDF no storage
     */
    private function savePdf(UploadedFile $file, int $contractId, int $teamId): string
    {
        $fileName = 'nf_' . time() . '_' . uniqid() . '.pdf';
        $path = "invoices/team_{$teamId}/contract_{$contractId}";
        
        return $file->storeAs($path, $fileName, 'private');
    }

    /**
     * Salva o XML no storage
     */
    private function saveXml(UploadedFile $file, int $contractId, int $teamId): string
    {
        $fileName = 'nf_' . time() . '_' . uniqid() . '.xml';
        $path = "invoices/team_{$teamId}/contract_{$contractId}";
        
        return $file->storeAs($path, $fileName, 'private');
    }

    /**
     * Extrai dados do XML da NF-e
     * 
     * Suporta formato padrão de NF-e e NFS-e
     */
    private function extractDataFromXml(UploadedFile $file): ?array
    {
        try {
            $xmlContent = file_get_contents($file->getRealPath());
            
            // Carregar XML
            $xml = simplexml_load_string($xmlContent);
            
            if (!$xml) {
                return null;
            }

            // Registrar namespaces comuns de NF-e
            $xml->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
            $xml->registerXPathNamespace('nfse', 'http://www.abrasf.org.br/nfse.xsd');

            $data = [];

            // Tentar extrair dados de NF-e (produto)
            $infNFe = $xml->xpath('//nfe:infNFe') ?: $xml->xpath('//infNFe');
            if ($infNFe && count($infNFe) > 0) {
                $nfe = $infNFe[0];
                
                // Número da NF
                $numero = $nfe->xpath('.//nfe:nNF') ?: $nfe->xpath('.//nNF');
                if ($numero && count($numero) > 0) {
                    $data['numero'] = (string) $numero[0];
                }
                
                // Data de emissão
                $dataEmissao = $nfe->xpath('.//nfe:dhEmi') ?: $nfe->xpath('.//dhEmi') ?: $nfe->xpath('.//dEmi');
                if ($dataEmissao && count($dataEmissao) > 0) {
                    $dateString = (string) $dataEmissao[0];
                    // Formato pode ser datetime ou apenas data
                    $data['data_emissao'] = substr($dateString, 0, 10);
                }
                
                // Valor total
                $valorTotal = $nfe->xpath('.//nfe:vNF') ?: $nfe->xpath('.//vNF');
                if ($valorTotal && count($valorTotal) > 0) {
                    $data['valor_total'] = (float) $valorTotal[0];
                }
            }

            // Tentar extrair dados de NFS-e (serviço)
            if (empty($data)) {
                // Número
                $numero = $xml->xpath('//Numero') ?: $xml->xpath('//NumeroNfse');
                if ($numero && count($numero) > 0) {
                    $data['numero'] = (string) $numero[0];
                }
                
                // Data de emissão
                $dataEmissao = $xml->xpath('//DataEmissao') ?: $xml->xpath('//DtEmi');
                if ($dataEmissao && count($dataEmissao) > 0) {
                    $dateString = (string) $dataEmissao[0];
                    $data['data_emissao'] = substr($dateString, 0, 10);
                }
                
                // Valor (múltiplas possibilidades)
                $valor = $xml->xpath('//ValorServicos') ?: 
                         $xml->xpath('//ValorTotal') ?: 
                         $xml->xpath('//ValorLiquidoNfse') ?:
                         $xml->xpath('//Valor');
                if ($valor && count($valor) > 0) {
                    $data['valor_total'] = (float) $valor[0];
                }
                
                // Tentar extrair data de vencimento de InformacoesComplementares
                $infosComplementares = $xml->xpath('//InformacoesComplementares');
                if ($infosComplementares && count($infosComplementares) > 0) {
                    $texto = (string) $infosComplementares[0];
                    // Procurar padrão: VENCIMENTO: DD/MM/YYYY
                    if (preg_match('/VENCIMENTO:\s*(\d{2})\/(\d{2})\/(\d{4})/i', $texto, $matches)) {
                        $data['data_vencimento'] = "{$matches[3]}-{$matches[2]}-{$matches[1]}";
                    }
                }
            }

            // Se não conseguiu extrair nada, retornar null
            if (empty($data)) {
                return null;
            }

            // Adicionar XML completo para referência
            $data['xml_completo'] = $xmlContent;

            return $data;
        } catch (\Exception $e) {
            \Log::error('Erro ao extrair dados do XML da NF: ' . $e->getMessage());
            return null;
        }
    }
}
