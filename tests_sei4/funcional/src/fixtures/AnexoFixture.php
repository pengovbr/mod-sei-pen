<?php

class AnexoFixture extends FixtureBase
{
    protected $objAnexoDTO;
    
    public function __construct()
    {
        $this->objAnexoDTO = new \AnexoDTO();
    }
 
    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }
    
    protected function cadastrar($dados = [])
    {
        $dados['Nome'] = $dados['Nome'] ?: 'arquivo_pequeno.txt';

        $this->objAnexoDTO->setNumIdUnidade($dados['IdUnidade'] ?: '110000001');
        $this->objAnexoDTO->setDblIdProtocolo($dados['IdProtocolo']);
        $this->objAnexoDTO->setNumTamanho($dados['Tamanho'] ?: 16); 
        $this->objAnexoDTO->setNumIdBaseConhecimento($dados['IdBaseConhecimento'] ?: null); 
        $this->objAnexoDTO->setStrNome($dados['Nome']);
        $this->objAnexoDTO->setDthInclusao($dados['Inclusao'] ?: \InfraData::getStrDataHoraAtual());
        $this->objAnexoDTO->setNumIdUsuario($dados['IdUsuario'] ?: 100000001);
        $this->objAnexoDTO->setStrSinAtivo($dados['SinAtivo']  ?: 'S');
        $this->objAnexoDTO->setStrHash($dados['Hash'] ?: 'e307098a01b40de6183583f3163ac6ed');
        
        $objAnexoBD = new \AnexoBD(\BancoSEI::getInstance());
        $objAnexoDTO = $objAnexoBD->cadastrar($this->objAnexoDTO);
        $this->realizarUpload($dados['Nome'],$objAnexoDTO->getNumIdAnexo());

        return $this->objAnexoDTO;
    }

    public function realizarUpload($caminhoOrigem, $nomeDestino) {
        
        // Obtém a orgão atual
        $org = getenv('DATABASE_HOST');

        // Obtém a data atual
        $dataAtual = date('Y/m/d');

        // Define os caminhos de destino baseado na data atual e no orgão que será feito o upload
        $caminhoDestinoBase = "/var/sei/arquivos/{$org}/{$dataAtual}/";
        $caminhoOrigemBase = "/tmp/arquivos/";

        // Cria o diretório de destino se não existir
        if (!file_exists($caminhoDestinoBase)) {
            mkdir($caminhoDestinoBase, 0777, true); // Cria diretórios recursivamente com permissão 0777
        }
    
        // Cria o caminho completo de destino
        $caminhoDestinoCompleto = $caminhoDestinoBase . $nomeDestino;
        
        // Cria o caminho completo de origem
        $caminhoOrigemCompleto = $caminhoOrigemBase . $caminhoOrigem;

        try {
            // Verifica se o arquivo de origem existe
            if (!file_exists($caminhoOrigemCompleto)) {
                throw new Exception("\nO arquivo de origem não existe.\nArquivo=".$caminhoOrigemCompleto);
            }
    
            // Copia o arquivo para o destino
            if (!copy($caminhoOrigemCompleto, $caminhoDestinoCompleto)) {
                throw new Exception("\nFalha ao copiar o arquivo para o destino.\nArquivo=".$caminhoOrigemCompleto."\nDestino=".$caminhoDestinoCompleto);
            }
    
        } catch (Exception $e) {
            echo "Erro: " . $e->getMessage();
        }
    }

}