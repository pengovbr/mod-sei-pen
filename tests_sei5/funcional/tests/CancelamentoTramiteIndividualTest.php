<?php

/**
 * Teste que cadastra um processo via fixture, loga no sistema e arquiva esse processo, em seguida manipula as datas de guarda antes de executar o agendamento de verificar
 * se o tempo de guarda venceu e em seguida navega até a tela de Avaliacao de Processo para validar que o processo consta nessa tela
 *
 */
class CancelamentoTramiteIndividualTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $processoTestePrincipal;
    public static $protocoloTestePrincipal;
    public static $documentoTeste1;
    public static $documentoTeste2;

    /**
     * @inheritdoc
     * @return void
     */
    function setUp(): void
    {
        parent::setUp();

        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);    
        $bancoOrgaoA->execute("update infra_agendamento_tarefa set sin_ativo = ? where comando = ?", array('N', 'PENAgendamentoRN::processarTarefasEnvioPEN'));
        $bancoOrgaoA->execute("update infra_agendamento_tarefa set sin_ativo = ? where comando = ?", array('N', 'PENAgendamentoRN::processarTarefasRecebimentoPEN'));

        $bancoOrgaoB = new DatabaseUtils(CONTEXTO_ORGAO_B);
        $bancoOrgaoB->execute("update infra_agendamento_tarefa set sin_ativo = ? where comando = ?", array('N', 'PENAgendamentoRN::processarTarefasEnvioPEN'));
        $bancoOrgaoB->execute("update infra_agendamento_tarefa set sin_ativo = ? where comando = ?", array('N', 'PENAgendamentoRN::processarTarefasRecebimentoPEN'));

    }

    /**
     * @return void
     */
    public function test_tramitar_processo()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        
        // Definição de dados de teste do processo principal
        self::$processoTestePrincipal = $this->gerarDadosProcessoTeste(self::$remetente);
        self::$documentoTeste1 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        self::$documentoTeste2 = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        
        $objProtocoloDTO = $this->cadastrarProcessoFixture(self::$processoTestePrincipal);
        $this->cadastrarDocumentoInternoFixture(self::$documentoTeste1, $objProtocoloDTO->getDblIdProtocolo());
        $this->cadastrarDocumentoInternoFixture(self::$documentoTeste2, $objProtocoloDTO->getDblIdProtocolo());

        // Preencher variaveis que serão usadas posteriormente nos testes
        self::$protocoloTestePrincipal = $objProtocoloDTO->getStrProtocoloFormatado();
        
        // Acessar sistema do this->REMETENTE do processo e conclui processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->abrirProcesso(self::$protocoloTestePrincipal);

        // Trâmitar Externamento processo para órgão/unidade destinatária
        $this->tramitarProcessoExternamente(
            self::$protocoloTestePrincipal,
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false,
            null,
            PEN_WAIT_TIMEOUT,
            false,
            false
        );

    }

    public function test_cancelar_tramite_processo_status_1_e_2()
    {
        // Acessar sistema do this->REMETENTE do processo e conclui processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->abrirProcesso(self::$protocoloTestePrincipal);

        $this->paginaProcesso->cancelarTramitacaoExterna();
        $mensagemAlerta = $this->paginaTramitar->alertTextAndClose(true);
        $mensagemEsperada = mb_convert_encoding("O trâmite externo do processo foi cancelado com sucesso!", 'UTF-8', 'ISO-8859-1');
        $this->assertStringContainsString($mensagemEsperada, $mensagemAlerta);
    }

    public function test_cancelar_tramite_processo_status_3_a_5()
    {
        $strProtocoloTeste = self::$protocoloTestePrincipal;

        // Acessar sistema do this->REMETENTE do processo e conclui processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);

        $this->abrirProcesso($strProtocoloTeste);

        // Trâmitar Externamento processo para órgão/unidade destinatária
        $this->tramitarProcessoExternamente(
            self::$protocoloTestePrincipal,
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA'],
            false,
            null,
            PEN_WAIT_TIMEOUT,
            false,
            false
        );

        $this->sairSistema();

        $this->acessarSistema(self::$destinatario['URL'], self::$destinatario['SIGLA_UNIDADE'], self::$destinatario['LOGIN'], self::$destinatario['SENHA']);
        $this->paginaAgendamentos->navegarAgendamento();
        $this->paginaAgendamentos->executarAgendamento('PENAgendamentoRN :: processarTarefasRecebimentoPEN');

        $this->paginaBase->navegarParaControleProcesso();

        // No modo assincrono (Gearman) uma execucao do agendamento apenas
        // ENFILEIRA um passo da cadeia de recebimento; os passos seguintes so
        // avancam na proxima execucao do monitoramento. Em producao o
        // agendamento do SEI repete sozinho; aqui ele e disparado uma unica vez.
        //
        // A primeira versao desta correcao usava 4 rodadas FIXAS, numero
        // calibrado observando o MySQL. Numero magico nao se sustenta: quantas
        // rodadas sao necessarias depende da velocidade do banco e de quanto o
        // barramento ja acumulou de trafego. No SQL Server, com a suite inteira
        // rodada antes, 4 nao bastaram e a espera estourou.
        //
        // Agora o monitoramento roda DENTRO da espera, uma vez por tentativa,
        // ate o processo chegar ou o tempo acabar -- sem numero para calibrar.
        // So o recebimento do DESTINATARIO: rodar a cadeia inteira faria o org1
        // processar o recibo e encerrar o tramite, tirando da arvore o icone de
        // cancelamento que este teste precisa encontrar logo adiante.
        $bolAssincrono = $this->modoSegundoPlanoAtivo();
        $this->waitUntil(function () use ($strProtocoloTeste, $bolAssincrono) {
            if ($bolAssincrono) {
                shell_exec('docker exec -e XDEBUG_MODE=off funcional-org2-http-1 '
                    . 'php /opt/sei/scripts/mod-pen/MonitoramentoRecebimentoTarefasPEN.php -d');
                $this->aguardarFilaGearmanDrenar(60);
            }
            try {
                $this->paginaBase->refresh();
                $this->paginaControleProcesso->abrirProcesso($strProtocoloTeste);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }, PEN_WAIT_TIMEOUT);

        
        $this->sairSistema();
        

        // Acessar sistema do this->REMETENTE do processo e conclui processo
        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        $this->abrirProcesso(self::$protocoloTestePrincipal);

        $this->paginaProcesso->cancelarTramitacaoExterna();
        $mensagemAlerta = $this->paginaTramitar->alertTextAndClose(true);
        $mensagemEsperada = mb_convert_encoding('O sistema destinatário já iniciou o recebimento desse processo, portanto não é possível realizar o cancelamento', 'UTF-8', 'ISO-8859-1');
        $this->assertStringContainsString($mensagemEsperada, $mensagemAlerta);

    }

    public static function tearDownAfterClass(): void
    {

        parent::tearDownAfterClass();
        $bancoOrgaoA = new DatabaseUtils(CONTEXTO_ORGAO_A);    
        $bancoOrgaoA->execute("update infra_agendamento_tarefa set sin_ativo = ? where comando = ?", array('S', 'PENAgendamentoRN::processarTarefasEnvioPEN'));
        $bancoOrgaoA->execute("update infra_agendamento_tarefa set sin_ativo = ? where comando = ?", array('S', 'PENAgendamentoRN::processarTarefasRecebimentoPEN'));

        $bancoOrgaoB = new DatabaseUtils(CONTEXTO_ORGAO_B);
        $bancoOrgaoB->execute("update infra_agendamento_tarefa set sin_ativo = ? where comando = ?", array('S', 'PENAgendamentoRN::processarTarefasEnvioPEN'));
        $bancoOrgaoB->execute("update infra_agendamento_tarefa set sin_ativo = ? where comando = ?", array('S', 'PENAgendamentoRN::processarTarefasRecebimentoPEN'));
    
    }

}
