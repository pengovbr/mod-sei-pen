<?php
/**
 * Execution Groups
 * @group execute_alone_group1
 */
class TramiteBlocoExternoEstadoProcessosTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;
    public static $objBlocoDeTramiteDTO;
    public static $objProtocoloDTO;
    public static $documentoTeste;

    /**
     * Incluir processo que contém documento de outra unidade dentro de um bloco externo
     * 
     * @return void
     */
    public function test_inclusao_de_processo_com_base_no_estado()
    {
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        ); 
        
        $arrProcessosTestes = $this->cadastrarBlocoComProcessosComEstadoDeTramite();

        $arrNupsProcessosTestes = array_keys($arrProcessosTestes);

        $strProcessoNaoEnviado = $arrNupsProcessosTestes[0];
        $strProcessoSucesso = $arrNupsProcessosTestes[1];
        $strProcessoCancelado = $arrNupsProcessosTestes[2];
        $strProcessoRecusado = $arrNupsProcessosTestes[3];
        $strProcessoAguardandoProcessamento = $arrNupsProcessosTestes[4];
        
        // Testar Inclusão de processo com estado de NÃO ENVIADO no bloco de tramite
        $objBlocoDeTramiteDTO = $this->cadastrarBlocoDeTramite();

        $mensagem = $this->inserirProcessoEmUmBloco($strProcessoNaoEnviado, $objBlocoDeTramiteDTO->getNumId());
        $this->assertStringContainsString(
            mb_convert_encoding(
                "Prezado(a) usuário(a), o processo " . $strProcessoNaoEnviado
                . " encontra-se inserido no bloco"
                , 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );

         // Testar Inclusão de processo com estado de SUCESSO em outro bloco de tramite
        $objBlocoDeTramiteDTO = $this->cadastrarBlocoDeTramite();

        $mensagem = $this->inserirProcessoEmUmBloco($strProcessoSucesso, $objBlocoDeTramiteDTO->getNumId());
        $this->assertStringContainsString(
            mb_convert_encoding('Processo(s) incluído(s) com sucesso no bloco ' . $objBlocoDeTramiteDTO->getNumOrdem(), 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );
        
        // Testar Inclusão de processo com estado de CANCELADO em outro bloco de tramite
        $objBlocoDeTramiteDTO = $this->cadastrarBlocoDeTramite();

        $mensagem = $this->inserirProcessoEmUmBloco($strProcessoCancelado, $objBlocoDeTramiteDTO->getNumId());
        $this->assertStringContainsString(
            mb_convert_encoding('Processo(s) incluído(s) com sucesso no bloco ' . $objBlocoDeTramiteDTO->getNumOrdem(), 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );

        // Testar Inclusão de processo com estado de AGUARDANDO PROCESSAMENTO em outro bloco de tramite
        $objBlocoDeTramiteDTO = $this->cadastrarBlocoDeTramite();

        $mensagem = $this->inserirProcessoEmUmBloco($strProcessoAguardandoProcessamento, $objBlocoDeTramiteDTO->getNumId());
        $this->assertStringContainsString(
            mb_convert_encoding(
                "Prezado(a) usuário(a), o processo " . $strProcessoAguardandoProcessamento
                . " encontra-se inserido no bloco"
                , 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );

        $this->sairSistema();
    }


    /**
     * Cadastra o bloco de tramite
     */
    public function cadastrarBlocoDeTramite($dados = [])
    {
        $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
        return $objBlocoDeTramiteFixture->carregar($dados);
    }

    /**
     * Cadastra processo em um bloco de tramite
     */
    public function cadastrarProcessoBlocoDeTramite($dados = [])
    {
        $objBlocoDeTramiteFixture = new \BlocoDeTramiteProtocoloFixture();
        return $objBlocoDeTramiteFixture->carregar($dados);
    }

    /**
     * Cadastra o bloco de tramite
     */
    private function cadastrarProcessos()
    {
        $processoTeste = $this->gerarDadosProcessoTeste(self::$remetente);
        $documentoTeste = $this->gerarDadosDocumentoInternoTeste(self::$remetente);
        
        $objProtocoloDTO = $this->cadastrarProcessoFixture($processoTeste);
        $this->cadastrarDocumentoInternoFixture($documentoTeste, $objProtocoloDTO->getDblIdProtocolo());
        
        return $objProtocoloDTO;
    }

    /**
     * Registra um bloco contendo um processo, atribuindo-lhe o estado especificado do processo em um tramite em bloco.
     * Estados possíveis: Aberto, Em processamento, Recusado, Cancelado, Sucesso
     * 
     * @return array
     */
    private function cadastrarBlocoComProcessosComEstadoDeTramite() 
    {
        $arrEstados = [
            null,
            ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE,
            ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO,
            ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO,
            ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_ENVIADOS_REMETENTE,      
        ];

        $arrProcessosComEstado = [];
        for ($i = 0; $i < count($arrEstados); $i++) {

            $objProtocoloDTO = $this->cadastrarProcessos();
            $objBlocoDeTramiteDTO = $this->cadastrarBlocoDeTramite();
    
            $dadosBlocoProcesso = [];
            $protocoloFormatado = $objProtocoloDTO->getStrProtocoloFormatado();

            $arrProcessosComEstado[$protocoloFormatado] = $arrEstados[$i];
            
            // Popula o array com os dados necessários para esta iteração
            $dadosBlocoProcesso['IdUnidadeOrigem'] = self::$remetente['ID_ESTRUTURA'];
            $dadosBlocoProcesso['IdUnidadeDestino'] = self::$destinatario['ID_ESTRUTURA'];
            $dadosBlocoProcesso['UnidadeDestino'] = self::$destinatario['NOME_UNIDADE'];
            $dadosBlocoProcesso['IdBloco'] = $objBlocoDeTramiteDTO->getNumId();
            $dadosBlocoProcesso['IdProtocolo'] = $objProtocoloDTO->getDblIdProtocolo();
            $dadosBlocoProcesso['IdAndamento'] = $arrEstados[$i];

            $dadosBlocoProcesso['IdRepositorioOrigem'] = self::$remetente['ID_ESTRUTURA'] ?: null;
            $dadosBlocoProcesso['IdRepositorioDestino'] = self::$destinatario['ID_REP_ESTRUTURAS'] ?: null;
            $dadosBlocoProcesso['RepositorioDestino'] = self::$remetente['NOME_UNIDADE'] ?: null;

            $this->cadastrarProcessoBlocoDeTramite($dadosBlocoProcesso);
        }

        return $arrProcessosComEstado;
    }

    private function inserirProcessoEmUmBloco($strProtocoloFormatado, $numIdBloco) 
    {
        $this->paginaBase->navegarParaControleProcesso();
        self::$objBlocoDeTramiteDTO = $this->cadastrarBlocoDeTramite();
        $this->paginaTramiteEmBloco->selecionarProcessos([$strProtocoloFormatado]);
        $this->paginaTramiteEmBloco->selecionarTramiteEmBloco();
        $this->paginaTramiteEmBloco->selecionarBloco($numIdBloco);
        $this->paginaTramiteEmBloco->clicarSalvar();

        sleep(2);
        return $this->paginaCadastrarProcessoEmBloco->buscarMensagemAlerta();
    }
}