<?php

/**
 * Testes de mapeamento de tipos de processo reativar
 * Reativar tipos de processos
 */
class CriticasDesativarExcluirUnidadeTipoProcDocTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $penMapTipoProcessoId;
    public static $penOrgaoExternoId;    
    public static $tipoProcesso;
    public static $tipoDocumento;

    
    /**
     * Teste de desativar tipo de processo em utilização em um Relacionamento entre Órgãos
     * 
     * @large
     *
     * @return void
     */
    public function test_desativar_tipo_processo()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        $penMapUnidadesFixture = new PenMapUnidades2Fixture();
        $penMapUnidadesFixture->carregar([
            'IdUnidadeRH' => self::$remetente['ID_ESTRUTURA'],
            'SiglaUnidadeRH' => self::$remetente['SIGLA_ESTRUTURA'],
            'NomeUnidadeRH' => self::$remetente['NOME_UNIDADE']
        ]);

        $penOrgaoExternoFixture = new PenOrgaoExterno2Fixture();
        $penOrgaoExternoDTO = $penOrgaoExternoFixture->carregar([
            'IdEstrutaOrganizacionalOrigem' => self::$remetente['ID_REP_ESTRUTURAS'],
            'EstrutaOrganizacionalOrigem' => self::$remetente['REP_ESTRUTURAS'],
            'IdOrgaoDestino' => self::$remetente['ID_ESTRUTURA'],
            'OrgaoDestino' => self::$remetente['NOME_UNIDADE'],
            'IdOrgaoOrigem' => self::$destinatario['ID_ESTRUTURA'],
            'OrgaoOrigem' => self::$destinatario['NOME_UNIDADE']
        ]);

        $penMapTipoProcessoFixture = new PenMapTipoProcessoFixture();
        $penMapTipoProcessoFixture->carregar([
            'IdMapOrgao' => $penOrgaoExternoDTO->getDblId()
        ]);

        self::$penOrgaoExternoId = $penOrgaoExternoDTO->getDblId();
        self::$penMapTipoProcessoId = $penMapTipoProcessoDTO->getDblId();

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        self::$tipoProcesso = 'Contabilidade: DIRF'; 
        $this->paginaTipoProcesso->navegarTipoProcesso();
        $this->paginaTipoProcesso->pesquisarTipoProcesso(utf8_encode(self::$tipoProcesso));
        $this->paginaTipoProcesso->desativarTipoProcesso();
        $this->waitUntil(function ($testCase)  {
            $testCase->frame(null);
            $mensagemValidacao = utf8_encode('Prezado(a) usuário(a), você está tentando desativar um Tipo de Processo que se encontra mapeado para o(s) relacionamento(s)');
            $this->assertStringContainsString($mensagemValidacao, $testCase->byId('divInfraMsg0')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

    /**
     * Teste de excluir tipo de processo em utilização em um Relacionamento entre Órgãos
     * 
     * @large
     *
     * @return void
     */
    public function test_excluir_tipo_processo()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaTipoProcesso->navegarTipoProcesso();
        $this->paginaTipoProcesso->pesquisarTipoProcesso(utf8_encode(self::$tipoProcesso));
        $this->paginaTipoProcesso->excluirTipoProcesso();
        $this->waitUntil(function ($testCase)  {
            $testCase->frame(null);
            $mensagemValidacao = utf8_encode('Prezado(a) usuário(a), você está tentando excluir um Tipo de Processo que se encontra mapeado para o(s) relacionamento(s)');
            $this->assertStringContainsString($mensagemValidacao, $testCase->byId('divInfraMsg0')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

    /**
     * Teste de desativar tipo de documentos
     * 
     * @large
     *
     * @return void
     */
    public function test_desativar_tipo_documento()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        self::$tipoDocumento = 'Acórdão';
        $this->paginaTipoDocumento->navegarTipoDocumento();
        $this->paginaTipoDocumento->pesquisarTipoDocumento(utf8_encode(self::$tipoDocumento));
        $this->paginaTipoDocumento->desativarTipoDocumento();
        $this->waitUntil(function ($testCase)  {
            $testCase->frame(null);
            $mensagemValidacao = utf8_encode('Não é permitido excluir ou desativar o tipo de documento');
            $this->assertStringContainsString($mensagemValidacao, $testCase->byId('divInfraMsg0')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

    /**
     * Teste de excluir tipo de documentos
     * 
     * @large
     *
     * @return void
     */
    public function test_excluir_tipo_documento()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaTipoDocumento->navegarTipoDocumento();
        $this->paginaTipoDocumento->pesquisarTipoDocumento(utf8_encode(self::$tipoDocumento));
        $this->paginaTipoDocumento->excluirTipoDocumento();
        $this->waitUntil(function ($testCase)  {
            $testCase->frame(null);
            $mensagemValidacao = utf8_encode('Não é permitido excluir ou desativar o tipo de documento');
            $this->assertStringContainsString($mensagemValidacao, $testCase->byId('divInfraMsg0')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

    
    /**
     * Teste de desativar unidade
     * 
     * @large
     *
     * @return void
     */
    public function test_desativar_unidade()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaUnidades->navegarUnidades();
        $this->paginaUnidades->desativarUnidades();
        $this->waitUntil(function ($testCase)  {
            $testCase->frame(null);
            $mensagemValidacao = utf8_encode('Não é permitido excluir ou desativar a unidade');
            $this->assertStringContainsString($mensagemValidacao, $testCase->byId('divInfraMsg0')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

    /**
     * Teste de excluir unidade
     * 
     * @large
     *
     * @return void
     */
    public function test_excluir_unidade()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaUnidades->navegarUnidades();
        $this->paginaUnidades->excluirUnidades();
        $this->waitUntil(function ($testCase)  {
            $testCase->frame(null);
            $mensagemValidacao = utf8_encode('Não é permitido excluir ou desativar a unidade');
            $this->assertStringContainsString($mensagemValidacao, $testCase->byId('divInfraMsg0')->text());
            return true;
        }, PEN_WAIT_TIMEOUT);
    }

    public static function tearDownAfterClass(): void
    {
        $penMapTipoProcessoFixture = new PenMapTipoProcessoFixture();
        $penMapTipoProcessoFixture->remover([
            'Id' => self::$penMapTipoProcessoId,
        ]);

        $penOrgaoExternoFixture = new PenOrgaoExternoFixture();
        $penOrgaoExternoFixture->remover([
            'Id' => self::$penOrgaoExternoId,
        ]);

        parent::tearDownAfterClass();
    }
}
