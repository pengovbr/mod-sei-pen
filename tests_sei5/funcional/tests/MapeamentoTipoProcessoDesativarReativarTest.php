<?php

use PHPUnit\Framework\Attributes\{Group,Large,Depends};
use Facebook\WebDriver\Remote\RemoteWebDriver;

/**
 * Testes de mapeamento de tipos de processo e relacionamento entre orgãos
 * Desativar e reativar mapeamento entre orgãos
 *
 * Execution Groups
 * #[Group('execute_alone_group1')]
 */
class MapeamentoTipoProcessoDesativarReativarTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $penOrgaoExternoId;

    /**
     * @inheritdoc
     * @return void
     */
    function setUp(): void
    {
        parent::setUp();
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        $penOrgaoExternoFixture = new \PenOrgaoExternoFixture();
        $objPenOrgaoExternoDTO = $penOrgaoExternoFixture->carregar([
            'IdRepositorio' => self::$remetente['ID_REP_ESTRUTURAS'],
            'RepositorioEstruturas' => self::$remetente['REP_ESTRUTURAS'],
            'Id' => self::$remetente['ID_ESTRUTURA'],
            'Sigla' => self::$remetente['SIGLA_ESTRUTURA'],
            'Nome' => self::$remetente['NOME_UNIDADE'],
            'IdOrigem' => self::$destinatario['ID_ESTRUTURA'],
            'NomeOrigem' => self::$destinatario['NOME_UNIDADE']
        ]);
    
        self::$penOrgaoExternoId = $objPenOrgaoExternoDTO->getDblId();
    }

    /**
     * Teste de desativação de um Relacionamento entre Órgãos
     *
     * #[Large]
     *
     * @return void
     */
    public function test_desativacao_mapeamento_orgao_externo()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaTramiteMapeamentoOrgaoExterno->navegarRelacionamentoEntreOrgaos();

        $this->paginaTramiteMapeamentoOrgaoExterno->selectEstado("Ativo");
        $this->paginaTramiteMapeamentoOrgaoExterno->desativarMapeamento();
        $page = $this->paginaTramiteMapeamentoOrgaoExterno;
        $mensagemValidacao = mb_convert_encoding('Relacionamento entre Unidades foi desativado com sucesso.', 'UTF-8', 'ISO-8859-1');
        $this->waitUntil(
            fn() => mb_strpos(
                $this->paginaTramiteMapeamentoOrgaoExterno->buscarMensagemAlerta(),
                $mensagemValidacao
            ) !== false,
            PEN_WAIT_TIMEOUT
        );
        // ao final garante de fato que a mensagem existe
        $this->assertStringContainsString(
            $mensagemValidacao,
            $this->paginaTramiteMapeamentoOrgaoExterno->buscarMensagemAlerta()
        );
        $this->sairSistema();
    }

    /**
     * Teste de reativação de um Relacionamento entre Órgãos
     * 
     * #[Large]
     *
     * @return void
     */
    public function test_reativacao_mapeamento_orgao_externo()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaTramiteMapeamentoOrgaoExterno->navegarRelacionamentoEntreOrgaos();

        $this->paginaTramiteMapeamentoOrgaoExterno->selectEstado("Inativo");
        $this->paginaTramiteMapeamentoOrgaoExterno->reativarMapeamento();

        $page = $this->paginaTramiteMapeamentoOrgaoExterno;
        $mensagemValidacao = mb_convert_encoding('Relacionamento entre Unidades foi reativado com sucesso.', 'UTF-8', 'ISO-8859-1');
        $this->waitUntil(
            fn() => mb_strpos(
                $this->paginaTramiteMapeamentoOrgaoExterno->buscarMensagemAlerta(),
                $mensagemValidacao
            ) !== false,
            PEN_WAIT_TIMEOUT
        );
        
        // ao final garante de fato que a mensagem existe
        $this->assertStringContainsString(
            $mensagemValidacao,
            $this->paginaTramiteMapeamentoOrgaoExterno->buscarMensagemAlerta()
        );
        $this->sairSistema();
    }

    /**
     * Teste de desativação de um Relacionamento entre Órgãos via checkbox
     *
     * #[Large]
     *
     * @return void
     */
    public function test_desativacao_checkbox_mapeamento_orgao_externo()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaTramiteMapeamentoOrgaoExterno->navegarRelacionamentoEntreOrgaos();

        $this->paginaTramiteMapeamentoOrgaoExterno->selectEstado("Ativo");
        $this->paginaTramiteMapeamentoOrgaoExterno->desativarMapeamentoCheckbox();
        $page = $this->paginaTramiteMapeamentoOrgaoExterno;
        $mensagemValidacao = mb_convert_encoding('Relacionamento entre Unidades foi desativado com sucesso.', 'UTF-8', 'ISO-8859-1');
        $this->waitUntil(
            fn() => mb_strpos(
                $this->paginaTramiteMapeamentoOrgaoExterno->buscarMensagemAlerta(),
                $mensagemValidacao
            ) !== false,
            PEN_WAIT_TIMEOUT
        );
        $this->assertStringContainsString(
            $mensagemValidacao,
            $this->paginaTramiteMapeamentoOrgaoExterno->buscarMensagemAlerta()
        );
        
        $this->sairSistema();
    }

    /**
     * Teste de desativação de um Relacionamento entre Órgãos via checkbox
     *
     * #[Large]
     *
     * @return void
     */
    public function test_reativar_checkbox_mapeamento_orgao_externo()
    {
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaTramiteMapeamentoOrgaoExterno->navegarRelacionamentoEntreOrgaos();

        $this->paginaTramiteMapeamentoOrgaoExterno->selectEstado("Inativo");
        $this->paginaTramiteMapeamentoOrgaoExterno->reativarMapeamentoCheckbox();
        $page = $this->paginaTramiteMapeamentoOrgaoExterno;
        $mensagemValidacao = mb_convert_encoding('Relacionamento entre Unidades foi reativado com sucesso.', 'UTF-8', 'ISO-8859-1');
        $this->waitUntil(
            fn() => mb_strpos(
                $this->paginaTramiteMapeamentoOrgaoExterno->buscarMensagemAlerta(),
                $mensagemValidacao
            ) !== false,
            PEN_WAIT_TIMEOUT
        );
        // ao final garante de fato que a mensagem existe
        $this->assertStringContainsString(
            $mensagemValidacao,
            $this->paginaTramiteMapeamentoOrgaoExterno->buscarMensagemAlerta()
        );
        $this->sairSistema();
    }

    public static function tearDownAfterClass(): void
    {
        $importacaoTiposProcessoFixture = new \ImportacaoTiposProcessoFixture();
        $arrObjPenMapTipoProcedimentoDTO = $importacaoTiposProcessoFixture->buscar([
            'IdMapeamento' => self::$penOrgaoExternoId
        ]);

        foreach ($arrObjPenMapTipoProcedimentoDTO as $objPenMapTipoProcedimentoDTO) {
            $importacaoTiposProcessoFixture->remover([
                'Id' => $objPenMapTipoProcedimentoDTO->getDblId()
            ]);
        }

        $penOrgaoExternoFixture = new \PenOrgaoExternoFixture();
        $penOrgaoExternoFixture->remover([
            'Id' => self::$penOrgaoExternoId,
        ]);

        parent::tearDownAfterClass();
    }
}