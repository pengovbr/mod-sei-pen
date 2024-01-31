<?php

// use Tests\Funcional\Fixture;
// use Faker\Factory;
use InfraData;

class AssinaturaFixture extends \FixtureBase
{
    protected $objAssinaturaDTO;

    CONST TRATAMENTO = 'Presidente, Substituto';
    CONST ID_TARJA_ASSINATURA = 2;

    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    protected function cadastrar($dados = [])
    {
        $objAtividadeFixture = new \AtividadeFixture();
        $objAtividadeDTO = $objAtividadeFixture->cadastrar(
            [
                'IdProtocolo' => $dados['IdProtocolo'],
                'IdTarefa' => \TarefaRN::$TI_ASSINATURA_DOCUMENTO,
            ]
        );

        $dados['IdAssinatura'] = $this->getObjInfraIBanco()->getValorSequencia('seq_assinatura');

        $objAssinaturaDTO = new \AssinaturaDTO();
        $objAssinaturaDTO->setNumIdAssinatura($dados['IdAssinatura'] ?: null);
        $objAssinaturaDTO->setDblIdDocumento($dados['IdDocumento'] ?: 4);
        $objAssinaturaDTO->setNumIdAtividade($dados['IdAtividade'] ?: $objAtividadeDTO->getNumIdAtividade());
        $objAssinaturaDTO->setNumIdUsuario($dados['IdUsuario'] ?: 100000001);
        $objAssinaturaDTO->setNumIdUnidade($dados['IdUnidade'] ?: 110000001);
        $objAssinaturaDTO->setNumIdTarjaAssinatura($dados['IdTarjaAssinatura'] ?: self::ID_TARJA_ASSINATURA);
        $objAssinaturaDTO->setStrAgrupador($dados['Agrupador'] ?: null);
        $objAssinaturaDTO->setStrStaFormaAutenticacao($dados['StaFormaAutenticacao'] ?: 'S');
        $objAssinaturaDTO->setStrNome($dados['Nome'] ?: 'Usuário de Testes');
        $objAssinaturaDTO->setStrTratamento($dados['Tratamento'] ?: self::TRATAMENTO);
        $objAssinaturaDTO->setDblCpf($dados['Cpf'] ?: null);
        $objAssinaturaDTO->setStrP7sBase64($dados['P7sBase64'] ?: null);
        $objAssinaturaDTO->setStrNumeroSerieCertificado($dados['NumeroSerieCertificado'] ?: null);
        $objAssinaturaDTO->setStrSinAtivo($dados['SinAtivo'] ?: 'S');
        $objAssinaturaDTO->setStrModuloOrigem($dados['ModuloOrigem'] ?: null);
        $objAssinaturaDTO->setOrdDthAberturaAtividade(InfraDTO::$TIPO_ORDENACAO_ASC);

        $objAssinaturaDB = new \AssinaturaBD(\BancoSEI::getInstance());
        $objAssinaturaDB->cadastrar($objAssinaturaDTO);

        return $objAssinaturaDTO;
    }
}