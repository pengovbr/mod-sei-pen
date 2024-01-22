<?php

// use Tests\Funcional\Fixture;
// use Faker\Factory;
use InfraData;

class AssinaturaFixture extends \FixtureBase
{
    protected $objAssinaturaDTO;

    CONST TRATAMENTO = 'Presidente, Substituto';
    CONST ID_TARJA_ASSINATURA = 2;

    public function __construct()
    {
        $this->objAssinaturaDTO = new \AssinaturaDTO();
    }
 
    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    protected function cadastrar($dados = [])
    {
        $dados['IdAssinatura'] = $this->getObjInfraIBanco()->getValorSequencia('seq_assinatura');

        $this->objAssinaturaDTO->setNumIdAssinatura($dados['IdAssinatura'] ?: null);
        $this->objAssinaturaDTO->setDblIdDocumento($dados['IdDocumento'] ?: 4);
        $this->objAssinaturaDTO->setNumIdAtividade($dados['IdAtividade'] ?: null);
        $this->objAssinaturaDTO->setNumIdUsuario($dados['IdUsuario'] ?: 100000001);
        $this->objAssinaturaDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
        $this->objAssinaturaDTO->setNumIdTarjaAssinatura($dados['IdTarjaAssinatura'] ?: self::ID_TARJA_ASSINATURA);
        $this->objAssinaturaDTO->setStrAgrupador($dados['Agrupador'] ?: null);
        $this->objAssinaturaDTO->setStrStaFormaAutenticacao($dados['StaFormaAutenticacao'] ?: 'S');
        $this->objAssinaturaDTO->setStrNome($dados['Nome'] ?: 'Usuário de Testes');
        $this->objAssinaturaDTO->setStrTratamento($dados['Tratamento'] ?: self::TRATAMENTO);
        $this->objAssinaturaDTO->setDblCpf($dados['Cpf'] ?: null);
        $this->objAssinaturaDTO->setStrP7sBase64($dados['P7sBase64'] ?: null);
        $this->objAssinaturaDTO->setStrNumeroSerieCertificado($dados['NumeroSerieCertificado'] ?: null);
        $this->objAssinaturaDTO->setStrSinAtivo($dados['SinAtivo'] ?: 'S');
        $this->objAssinaturaDTO->setStrModuloOrigem($dados['ModuloOrigem'] ?: null);
        
        $objAssinaturaDB = new \AssinaturaBD(\BancoSEI::getInstance());
        $objAssinaturaDB->cadastrar($this->objAssinaturaDTO);

        $objAtividadeFixture = new AtividadeFixture();
        $objAtividadeDTO = $objAtividadeFixture->cadastrar(
            [
                'IdProtocolo' => $dados['IdProtocolo'],
                'IdTarefa' => TarefaRN::$TI_ASSINATURA_DOCUMENTO,
            ]
        );
        

        return $this->objAssinaturaDTO;
    }
}
