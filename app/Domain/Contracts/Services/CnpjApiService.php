<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de integração com API pública CNPJ.ws
 * 
 * Consulta dados cadastrais de empresas via CNPJ
 */
class CnpjApiService
{
    private const API_URL = 'https://publica.cnpj.ws/cnpj/';
    
    /**
     * Consulta dados de um CNPJ na API pública
     * 
     * @param string $cnpj CNPJ com ou sem formatação
     * @return array|null Dados da API ou null em caso de erro
     */
    public function consultarCnpj(string $cnpj): ?array
    {
        try {
            // Remover formatação do CNPJ
            $cnpjLimpo = preg_replace('/\D/', '', $cnpj);
            
            // Validar se tem 14 dígitos
            if (strlen($cnpjLimpo) !== 14) {
                Log::warning("CNPJ inválido para consulta API: {$cnpj}");
                return null;
            }
            
            // Fazer requisição à API
            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->get(self::API_URL . $cnpjLimpo);
            
            // Verificar se obteve sucesso
            if (!$response->successful()) {
                Log::warning("Erro ao consultar CNPJ {$cnpjLimpo} na API: HTTP {$response->status()}");
                return null;
            }
            
            $data = $response->json();
            
            // Verificar se retornou dados válidos
            if (!isset($data['razao_social'])) {
                Log::warning("API retornou resposta inválida para CNPJ {$cnpjLimpo}");
                return null;
            }
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error("Erro ao consultar CNPJ na API: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Extrai a Inscrição Estadual dos dados da API
     * 
     * @param array $apiData Dados retornados pela API
     * @return string|null Inscrição Estadual ou null
     */
    public function extrairInscricaoEstadual(array $apiData): ?string
    {
        // A API retorna um array de estabelecimentos, cada um com suas inscrições estaduais
        if (isset($apiData['estabelecimento']['inscricoes_estaduais']) && 
            is_array($apiData['estabelecimento']['inscricoes_estaduais']) &&
            count($apiData['estabelecimento']['inscricoes_estaduais']) > 0) {
            
            // Pegar a primeira inscrição estadual ativa
            foreach ($apiData['estabelecimento']['inscricoes_estaduais'] as $ie) {
                if (isset($ie['inscricao_estadual']) && $ie['ativo']) {
                    return $ie['inscricao_estadual'];
                }
            }
        }
        
        return null;
    }
    
    /**
     * Formata os dados da API para uso no sistema
     * 
     * @param array $apiData Dados retornados pela API
     * @return array Dados formatados
     */
    public function formatarDados(array $apiData): array
    {
        $estabelecimento = $apiData['estabelecimento'] ?? [];
        
        return [
            'razao_social' => $apiData['razao_social'] ?? null,
            'nome_fantasia' => $estabelecimento['nome_fantasia'] ?? null,
            'cnpj' => $apiData['cnpj'] ?? null,
            'inscricao_estadual' => $this->extrairInscricaoEstadual($apiData),
            'situacao_cadastral' => $estabelecimento['situacao_cadastral'] ?? null,
            'data_inicio_atividade' => $estabelecimento['data_inicio_atividade'] ?? null,
            'endereco_completo' => $this->montarEnderecoCompleto($estabelecimento),
            'telefone' => $estabelecimento['ddd1'] && $estabelecimento['telefone1'] 
                ? "({$estabelecimento['ddd1']}) {$estabelecimento['telefone1']}" 
                : null,
            'email' => $estabelecimento['email'] ?? null,
        ];
    }
    
    /**
     * Monta endereço completo a partir dos dados da API
     * 
     * @param array $estabelecimento Dados do estabelecimento
     * @return string|null Endereço formatado
     */
    private function montarEnderecoCompleto(array $estabelecimento): ?string
    {
        if (!isset($estabelecimento['logradouro'])) {
            return null;
        }
        
        $partes = [
            $estabelecimento['tipo_logradouro'] ?? '',
            $estabelecimento['logradouro'] ?? '',
            isset($estabelecimento['numero']) ? ", {$estabelecimento['numero']}" : '',
            isset($estabelecimento['complemento']) ? " - {$estabelecimento['complemento']}" : '',
            isset($estabelecimento['bairro']) ? " - {$estabelecimento['bairro']}" : '',
            isset($estabelecimento['municipio']) ? " - {$estabelecimento['municipio']}" : '',
            isset($estabelecimento['uf']) ? "/{$estabelecimento['uf']}" : '',
            isset($estabelecimento['cep']) ? " - CEP: {$estabelecimento['cep']}" : '',
        ];
        
        return trim(implode('', $partes));
    }
}
