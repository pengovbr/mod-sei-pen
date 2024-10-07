<?php

/**
 * teste de cadastro e editar bloco
 *
 * Execution Groups
 * @group execute_alone_group1
 */
class TesteRestTest extends FixtureCenarioBaseTestCase
{

    /**
     * Teste de cadastro de novo bloco de tramite externo
     *
     * @return void
     */
    public function test_cadastrar_novo_bloco_para_tramite_externo()
    {
        try {
            // Cria uma nova instância do cliente Guzzle
            $client = new GuzzleHttp\Client();
        
            // Faz uma requisição GET para um site de teste
            $response = $client->request('GET', 'https://httpbin.org/get');
        
            // Exibe o status code da resposta
            echo 'Status Code: ' . $response->getStatusCode() . "\n";
        
            // Exibe o corpo da resposta
            echo 'Body: ' . $response->getBody();
            
        } catch (\Exception $e) {
            // Mostra o erro, caso ocorra
            echo 'Erro: ' . $e->getMessage();
        }
    }

}