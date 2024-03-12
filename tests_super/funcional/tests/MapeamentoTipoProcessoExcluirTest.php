<?php

/**
 * Testes de mapeamento de tipos de processo e relacionamento entre orgãos
 * Excluir mapeamento entre orgãos
 */
class MapeamentoTipoProcessoExcluirTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $penOrgaoExternoId;

    /**
     * Teste para excluir de mapeamento de orgão exteno
     *
     * @group MapeamentoOrgaoExterno
     *
     * @return void
     */
    public function test_excluir_mapeamento_orgao_externo()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        $penOrgaoExternoFixture = new PenOrgaoExterno2Fixture();
        $penOrgaoExternoDTO = $penOrgaoExternoFixture->carregar([
            'IdEstrutaOrganizacionalOrigem' => self::$remetente['ID_REP_ESTRUTURAS'],
            'EstrutaOrganizacionalOrigem' => self::$remetente['REP_ESTRUTURAS'],
            'IdOrgaoDestino' => self::$remetente['ID_ESTRUTURA'],
            'OrgaoDestino' => self::$remetente['NOME_UNIDADE'],
            'IdOrgaoOrigem' => self::$destinatario['ID_ESTRUTURA'],
            'OrgaoOrigem' => self::$destinatario['NOME_UNIDADE']
        ]);

        self::$penOrgaoExternoId = $penOrgaoExternoDTO->getDblId();

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaTramiteMapeamentoOrgaoExterno->navegarRelacionamentoEntreOrgaos();
        sleep(5);
        $this->paginaCadastroOrgaoExterno->selecionarExcluirMapOrgao(self::$penOrgaoExternoId);
        sleep(1);
        $mensagem = $this->paginaCadastroOrgaoExterno->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Relacionamento entre unidades foi excluído com sucesso.'),
            $mensagem
        );
    }

    public static function tearDownAfterClass(): void
    {
        $importacaoTiposProcessoFixture = new ImportacaoTiposProcessoFixture(CONTEXTO_ORGAO_A);
        $importacaoTiposProcessoFixture->deletar(['idMapeamento' => self::$penOrgaoExternoId]);

        // $penOrgaoExternoFixture = new PenOrgaoExternoFixture(CONTEXTO_ORGAO_A);
        // $penOrgaoExternoFixture->deletar(self::$penOrgaoExternoId);

        parent::tearDownAfterClass();
    }
}
