<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

class PENAgendamentoRN extends InfraRN {

    public function __construct() {
        parent::__construct();
    }

    protected function inicializarObjInfraIBanco() {
        return BancoSEI::getInstance();
    }

    public function processarPendencias() {
        try {

            ini_set('max_execution_time', '0');
            ini_set('memory_limit', '-1');

            InfraDebug::getInstance()->setBolLigado(true);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);
            InfraDebug::getInstance()->limpar();

            SessaoSEI::getInstance(false)->simularLogin(SessaoSEI::$USUARIO_SEI, SessaoSEI::$UNIDADE_TESTE);

            $numSeg = InfraUtil::verificarTempoProcessamento();

            InfraDebug::getInstance()->gravar('ANALISANDO OS TRÂMITES PENDENTES ENVIADOS PARA O ÓRGÃO (PEN)');

            // Verifica todas as pendências de trâmite para o órgão atual
            $objReceberProcedimentoRN = new ReceberProcedimentoRN();
            $objEnviarReciboTramiteRN = new EnviarReciboTramiteRN();
            $objReceberReciboTramiteRN = new ReceberReciboTramiteRN();

            $result = $objReceberProcedimentoRN->listarPendencias();

            if (isset($result) && count($result) > 0) {

                //Identificar à natureza da pendência
                foreach ($result as $pendencia) {

                    try {

                        switch ($pendencia->getStrStatus()) {
                            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_INICIADO:
                                ;
                                break;

                            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_ENVIADOS_REMETENTE:
                                $objReceberProcedimentoRN->receberProcedimento($pendencia->getNumIdentificacaoTramite());
                                $objEnviarReciboTramiteRN->enviarReciboTramiteProcesso($pendencia->getNumIdentificacaoTramite());
                                break;

                            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_METADADOS_RECEBIDO_DESTINATARIO:
                                $objReceberProcedimentoRN->receberProcedimento($pendencia->getNumIdentificacaoTramite());
                                $objEnviarReciboTramiteRN->enviarReciboTramiteProcesso($pendencia->getNumIdentificacaoTramite());
                                break;

                            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_COMPONENTES_RECEBIDOS_DESTINATARIO:
                                $objReceberProcedimentoRN->receberProcedimento($pendencia->getNumIdentificacaoTramite());
                                $objEnviarReciboTramiteRN->enviarReciboTramiteProcesso($pendencia->getNumIdentificacaoTramite());
                                break;

                            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_ENVIADO_DESTINATARIO:
                                $objReceberReciboTramiteRN->receberReciboDeTramite($pendencia->getNumIdentificacaoTramite());
                                break;

                            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE:
                                ;
                                break;

                            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO:
                                ;
                                break;

                            case ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO:
                                ;
                                break;

                            default:
                                //TODO: Alterar lógica para não deixar de processar demais pendências retornadas pelo PEN
                                throw new Exception('Situação do trâmite não pode ser identificada.');
                                break;
                        }
                    } catch (InfraException $e) {
                        $strAssunto = 'Erro executando agendamentos.';
                        $strErro = InfraException::inspecionar($e);
                        LogSEI::getInstance()->gravar($strAssunto . "\n\n" . $strErro);
                    }
                }
            }

            $numSeg = InfraUtil::verificarTempoProcessamento($numSeg);
            InfraDebug::getInstance()->gravar('TEMPO TOTAL DE EXECUCAO: ' . $numSeg . ' s');
            InfraDebug::getInstance()->gravar('FIM');

            LogSEI::getInstance()->gravar(InfraDebug::getInstance()->getStrDebug());
        } catch (Exception $e) {
            InfraDebug::getInstance()->setBolLigado(false);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);

            throw new InfraException('Erro processando pendências de integração com o PEN - Processo Eletrônico Nacional.', $e);
        }
    }

    public function verificarTramitesRecusados() {

        try {

            ini_set('max_execution_time', '0');
            ini_set('memory_limit', '-1');

            InfraDebug::getInstance()->setBolLigado(true);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);
            InfraDebug::getInstance()->limpar();

            SessaoSEI::getInstance(false)->simularLogin(SessaoSEI::$USUARIO_SEI, SessaoSEI::$UNIDADE_TESTE);

            //BUSCA OS TRÂMITES PENDENTES
            $tramitePendenteDTO = new TramitePendenteDTO();
            $tramitePendenteDTO->retNumIdTramite();
            $tramitePendenteDTO->retNumIdAtividade();
            $tramitePendenteDTO->retNumIdTabela();
            $tramitePendenteDTO->setOrd('IdTabela', InfraDTO::$TIPO_ORDENACAO_ASC);

            $tramitePendenteBD = new TramiteBD($this->getObjInfraIBanco());
            $pendentes = $tramitePendenteBD->listar($tramitePendenteDTO);


            if ($pendentes) {

                //Instancia a RN de ProcessoEletronico
                $processoEletronicoRN = new ProcessoEletronicoRN();
                $arrProtocolos = array();

                foreach ($pendentes as $tramite) {
                    $objTramite = $processoEletronicoRN->consultarTramites($tramite->getNumIdTramite());
                    $objTramite = $objTramite[0];

                    if (isset($arrProtocolos[$objTramite->protocolo])) {
                        if ($arrProtocolos[$objTramite->protocolo]['objTramite']->situacaoAtual == ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE || $arrProtocolos[$objTramite->protocolo]['objTramite']->situacaoAtual == ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO) {
                            $tramitePendenteBD->excluir($arrProtocolos[$objTramite->protocolo]['tramitePendente']);
                        }
                    }

                    $arrProtocolos[$objTramite->protocolo]['objTramite'] = $objTramite;
                    $arrProtocolos[$objTramite->protocolo]['tramitePendente'] = $tramite;
                }



                //Percorre as pendências
                foreach ($arrProtocolos as $protocolo) {

                    //Busca o status do trâmite
                    $tramite = $protocolo['tramitePendente'];
                    $objTramite = $protocolo['objTramite'];
                    $status = $objTramite->situacaoAtual;

                    if ($status == ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECUSADO) {

                        //Verifica se o processo do trâmite se encontra de fato recusado
                        //Busca os dados do procedimento
                        $processoEletronicoDTO = new ProcessoEletronicoDTO();
                        $processoEletronicoDTO->setStrNumeroRegistro($objTramite->NRE);
                        $processoEletronicoDTO->retDblIdProcedimento();

                        $processoEletronicoBD = new ProcessoEletronicoBD($this->getObjInfraIBanco());
                        $objProcessoEletronico = $processoEletronicoBD->consultar($processoEletronicoDTO);

                        if ($objProcessoEletronico) {

                            //Busca o processo
                            $objProtocolo = new PenProtocoloDTO();
                            $objProtocolo->setDblIdProtocolo($objProcessoEletronico->getDblIdProcedimento());

                            $protocoloBD = new ProtocoloBD($this->getObjInfraIBanco());

                            //Verifica se o protocolo foi encontrado nessa tabela
                            if ($protocoloBD->contar($objProtocolo) > 0) {

                                //Altera o registro
                                $objProtocolo->setStrSinObteveRecusa('S');
                                $protocoloBD->alterar($objProtocolo);

                                //Busca a unidade de destino
                                $atributoAndamentoDTO = new AtributoAndamentoDTO();
                                $atributoAndamentoDTO->setNumIdAtividade($tramite->getNumIdAtividade());
                                $atributoAndamentoDTO->setStrNome('UNIDADE_DESTINO');
                                $atributoAndamentoDTO->retStrValor();

                                $atributoAndamentoBD = new AtributoAndamentoBD($this->getObjInfraIBanco());
                                $atributoAndamento = $atributoAndamentoBD->consultar($atributoAndamentoDTO);

                                $motivo = $objTramite->motivoDaRecusa;
                                $unidadeDestino = $atributoAndamento->getStrValor();

                                //Realiza o registro da recusa
                                ExpedirProcedimentoRN::receberRecusaProcedimento(ProcessoEletronicoRN::$MOTIVOS_RECUSA[$motivo], $unidadeDestino, null, $objProcessoEletronico->getDblIdProcedimento());

                                $tramitePendenteBD->excluir($tramite);
                            }
                        }
                    } else if ($status == ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_RECIBO_RECEBIDO_REMETENTE || $status == ProcessoEletronicoRN::$STA_SITUACAO_TRAMITE_CANCELADO) {

                        $tramitePendenteBD->excluir($tramite);
                    }
                }
            }
        } catch (Exception $e) {
            InfraDebug::getInstance()->setBolLigado(false);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);

            throw new InfraException('Erro na Verificação de Processos Recusados.', $e);
        }
    }

    public function seiVerificarServicosBarramento() {
        try {

            $cont = 0;
            $servico = array();
            $exec = shell_exec('ps -ef');
            if (strpos($exec, '/usr/bin/supervisord') == false) {
                $cont++;
                $servico[] = 'supervisord';
            }
            if (strpos($exec, '/usr/sbin/gearmand') == false) {
                $cont++;
                $servico[] = ' gearmand';
            }
            if (strpos($exec, 'PendenciasTramiteRN.php') == false) {
                $cont++;
                $servico[] = ' PendenciasTramiteRN.php';
            }
            if (strpos($exec, 'ProcessarPendenciasRN.php') == false) {
                $cont++;
                $servico[] = 'ProcessarPendenciasRN.php';
            }

			$strServicos = array_map(function($item){ return "- $item"; }, $servico);
			$strServicos = implode("\n", $strServicos);

            if ($cont > 0) {
                $msg = "Identificada inconsistência nos serviços de integração com o Processo Eletrônico Nacional - PEN.\n" .
               		"Os seguintes serviços necessários para o correto funcionamento da integração não estão ativos: \n $strServicos \n\n" .
                	"Favor, entrar em contato com a equipe de suporte técnico.";
                throw new InfraException($msg, $e);
            } else {
                LogSEI::getInstance()->gravar("Todos os serviços necessários na integração com o Processo Eletrônico Nacional - PEN estão ativos.");
            }
        } catch (Exception $e) {
            throw new InfraException('Erro ao analisar status do serviços Gearmand e Supervisord', $e);
        }
    }

    /**
     * Atualiza??o das hip?teses legais vindas do barramento
     * @throws InfraException
     */
    public function atualizarHipotesesLegais() {
        try {

            PENIntegracao::validarCompatibilidadeModulo();
            $objBD = new PenHipoteseLegalBD($this->inicializarObjInfraIBanco());
            $processoEletronicoRN = new ProcessoEletronicoRN();
            $hipotesesPen = $processoEletronicoRN->consultarHipotesesLegais();

            if(empty($hipotesesPen)){
                throw new InfraException('Não foi possível obter as hipóteses legais dos serviços de integração');
            }

            //Para cada hipótese vinda do PEN será verificado a existencia.
            foreach ($hipotesesPen->hipotesesLegais->hipotese as $hipotese) {

                $objDTO = new PenHipoteseLegalDTO();
                $objDTO->setNumIdentificacao($hipotese->identificacao);
                $objDTO->setNumMaxRegistrosRetorno(1);
                $objDTO->retStrNome();
                $objDTO->retNumIdHipoteseLegal();
                $objConsulta = $objBD->consultar($objDTO);

                //Caso não haja um nome para a hipótese legal, ele pula para a próxima.
                if (empty($hipotese->nome)) {
                    continue;
                }

                $objDTO->setStrNome(utf8_decode($hipotese->nome));

                if ($hipotese->status) {
                    $objDTO->setStrAtivo('S');
                } else {
                    $objDTO->setStrAtivo('N');
                }

                //Caso n?o exista a hipótese irá cadastra-la no sei.
                if (empty($objConsulta)) {

                    $objBD->cadastrar($objDTO);
                } else {
                    //Caso contrário apenas irá atualizar os dados.
                    $objDTO->setNumIdHipoteseLegal($objConsulta->getNumIdHipoteseLegal());
                    $objBD->alterar($objDTO);
                }
            }

            LogSEI::getInstance()->gravar("Hipóteses Legais atualizadas.");
        } catch (Exception $e) {
            throw new InfraException('Erro no agendamento das Hipóteses Legais', $e);
        }
    }
}
