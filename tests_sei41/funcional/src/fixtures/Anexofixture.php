<?php

class AnexoFixture extends FixtureBase
{
    protected $objAnexoDTO;
 
    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }
    
    protected function cadastrar($dados = [])
    {
        $dados['Nome'] = $dados['Nome'] ?: 'arquivo_pequeno_A.pdf';

        $objAnexoDTO = new \AnexoDTO();
        $objAnexoDTO->setNumIdUnidade($dados['IdUnidade'] ?: '110000001');
        $objAnexoDTO->setDblIdProtocolo($dados['IdProtocolo']);
        $objAnexoDTO->setNumTamanho($dados['Tamanho'] ?: 16); 
        $objAnexoDTO->setNumIdBaseConhecimento($dados['IdBaseConhecimento'] ?: null); 
        $objAnexoDTO->setStrNome($dados['Nome']);
        $objAnexoDTO->setDthInclusao($dados['Inclusao'] ?: \InfraData::getStrDataHoraAtual());
        $objAnexoDTO->setNumIdUsuario($dados['IdUsuario'] ?: 100000001);
        $objAnexoDTO->setStrSinAtivo($dados['SinAtivo']  ?: 'S');
        $objAnexoDTO->setStrHash($dados['Hash'] ?: 'e307098a01b40de6183583f3163ac6ed');
        
        $objAnexoBD = new \AnexoBD(\BancoSEI::getInstance());
        $objAnexoDTO = $objAnexoBD->cadastrar($objAnexoDTO);
        $this->realizarUpload($dados['Nome'],$objAnexoDTO->getNumIdAnexo());

        return $objAnexoDTO;
    }

    public function realizarUpload($caminhoOrigem, $nomeDestino) 
    {
        
        // Obtém a orgão atual
        $org = getenv('DATABASE_HOST');

        // Obtém a data atual
        $dataAtual = date('Y/m/d');

        // Define os caminhos de destino baseado na data atual e no orgão que será feito o upload
        $caminhoDestinoBase = "/var/sei/arquivos/{$org}/{$dataAtual}/";
        $caminhoOrigemBase = '/tmp/';

        // Cria o diretório de destino se não existir
        if (!file_exists($caminhoDestinoBase)) {
            mkdir($caminhoDestinoBase, 0777, true); // Cria diretórios recursivamente com permissão 0777
        }
    
        // Cria o caminho completo de destino
        $caminhoDestinoCompleto = $caminhoDestinoBase.$nomeDestino;
        
        // Cria o caminho completo de origem
        $caminhoOrigemCompleto = $caminhoOrigemBase.$caminhoOrigem;

        try {
            // Verifica se o arquivo de origem existe
            if (!file_exists($caminhoOrigemCompleto)) {
                throw new \Exception("O arquivo de origem não existe.\nArquivo=".$caminhoOrigemCompleto);
            }
    
            // Copia o arquivo para o destino
            if (!copy($caminhoOrigemCompleto, $caminhoDestinoCompleto)) {
                throw new \Exception("Falha ao copiar o arquivo para o destino.\nArquivo=".$caminhoOrigemCompleto."\nDestino=".$caminhoDestinoCompleto);
            }
    
        } catch (\Exception $e) {
            echo "\nErro: " . $e->getMessage();
        }
    }

}