<?php
/**
 * Automa��o de processos em background para testes
 * 
 * @tutorial php console.php criarProcedimento --desc="hahahaha" --stakeholder="INTRANET" --subject="010" --auth-user="teste" --auth-pass="teste"
 *
 */
class PenConsoleActionRN extends InfraRN {
    
    const STR_PAD_LEFT = 4;
    
    protected $objInfraBanco;
    
  protected function inicializarObjInfraIBanco() {
        
    if(empty($this->objInfraBanco)) {
            
        $this->objInfraBanco = BancoSEI::getInstance();
        $this->objInfraBanco->abrirConexao();
    }
      return $this->objInfraBanco;
  }
    
    /**
     * @return ParticipanteDTO
     */
  protected function getParticipante($strInteressado){
    
      // Interessado
      $objParticipanteDTO = new ParticipanteDTO();
      $objParticipanteDTO->setStrNomeContato('%'.$strInteressado.'%', InfraDTO::$OPER_LIKE);
      $objParticipanteDTO->retTodos(true);
      $objParticipanteDTO->setNumMaxRegistrosRetorno(1);

      $objParticipanteBD = new ParticipanteBD($this->inicializarObjInfraIBanco());
      $objParticipanteDTO = $objParticipanteBD->consultar($objParticipanteDTO);

    if (empty($objParticipanteDTO)) {
        $objContatoDTO = new ContatoDTO();
        $objContatoDTO->setStrNome(urldecode($strInteressado));

        $objContatoRN = new ContatoRN();
        $objContatoDTO = $objContatoRN->cadastrarContextoTemporario($objContatoDTO);

        $objParticipanteDTO = new ParticipanteDTO();
        $objParticipanteDTO->setNumIdContato($objContatoDTO->getNumIdContato());
        $objParticipanteDTO->setStrStaParticipacao(ParticipanteRN::$TP_INTERESSADO);
        $objParticipanteDTO->setNumSequencia(0);
    }

      return $objParticipanteDTO;
  }

    /**
     * @return AssuntoDTO
     */
  protected function getAssunto($strCodigoEstruturado = ''){

      $objAssuntoDTO = new AssuntoDTO();
      $objAssuntoDTO->setStrCodigoEstruturado($strCodigoEstruturado);
      $objAssuntoDTO->setNumMaxRegistrosRetorno(1);
      $objAssuntoDTO->retNumIdAssunto();

      $objAssuntoBD = new AssuntoBD(BancoSEI::getInstance());
      $objAssuntoDTO = $objAssuntoBD->consultar($objAssuntoDTO);

    if (empty($objAssuntoDTO)) {
        throw new InfraException(sprintf('Assunto com c�digo %s n�o foi localizado', $strCodigoEstruturado));
    }

      $objRelProtocoloAssuntoDTO = new RelProtocoloAssuntoDTO();
      $objRelProtocoloAssuntoDTO->setNumIdAssunto($objAssuntoDTO->getNumIdAssunto());
      $objRelProtocoloAssuntoDTO->setNumSequencia(0);
        
      return array($objRelProtocoloAssuntoDTO);
  }
    
    /**
     * @return TipoProcedimentoDTO
     */
  protected function getTipoProcedimento($strTipoProcedimento = ''){
        
    if(empty($strTipoProcedimento)) {
        $strTipoProcedimento = 'Manuais';
    }
        
      // Tipo Procedimento
      $objTipoProcedimentoDTO = new TipoProcedimentoDTO();
      $objTipoProcedimentoDTO->setStrNome('%'.$strTipoProcedimento, InfraDTO::$OPER_LIKE);
      $objTipoProcedimentoDTO->setBolExclusaoLogica(false);
      $objTipoProcedimentoDTO->setNumMaxRegistrosRetorno(1);
      $objTipoProcedimentoDTO->retNumIdTipoProcedimento();
      $objTipoProcedimentoDTO->retStrNome();
        
      $objTipoProcedimentoRN = new TipoProcedimentoRN();
      $objTipoProcedimentoDTO = $objTipoProcedimentoRN->consultarRN0267($objTipoProcedimentoDTO);

    if (empty($objTipoProcedimentoDTO)) {
        $objNivelAcessoPermitidoDTO = new NivelAcessoPermitidoDTO();
        $objNivelAcessoPermitidoDTO->setStrStaNivelAcesso(ProtocoloRN::$NA_PUBLICO);
        $arrObjNivelAcessoPermitidoDTO[] = $objNivelAcessoPermitidoDTO;

        $objTipoProcedimentoDTO = new TipoProcedimentoDTO();
        $objTipoProcedimentoDTO->setNumIdTipoProcedimento(null);
        $objTipoProcedimentoDTO->setStrNome($strTipoAssentamento);
        $objTipoProcedimentoDTO->setStrDescricao(null);
        $objTipoProcedimentoDTO->setStrStaGrauSigiloSugestao(null);
        $objTipoProcedimentoDTO->setNumIdHipoteseLegalSugestao(null);
        $objTipoProcedimentoDTO->setStrSinInterno('N');
        $objTipoProcedimentoDTO->setStrSinOuvidoria('N');
        $objTipoProcedimentoDTO->setStrSinIndividual('N');
        $objTipoProcedimentoDTO->setArrObjNivelAcessoPermitidoDTO($arrObjNivelAcessoPermitidoDTO);
        $objTipoProcedimentoDTO->setArrObjRelTipoProcedimentoAssuntoDTO(array());
        $objTipoProcedimentoDTO->setStrStaNivelAcessoSugestao(0);
        $objTipoProcedimentoDTO->setStrSinAtivo('S');

        $objTipoProcedimentoRN = new TipoProcedimentoRN();
        $objTipoProcedimentoDTO = $objTipoProcedimentoRN->cadastrarRN0265($objTipoProcedimentoDTO);
    }

      return $objTipoProcedimentoDTO;
  }
    
  protected function getSerie($strNome = ''){
        
    if(empty($strNome)) {
        $strNome = 'CERTIDAO';
    }

      $objSerieDTO = new SerieDTO();
      $objSerieDTO->setStrNome($strNome, InfraDTO::$OPER_LIKE);
      $objSerieDTO->setNumMaxRegistrosRetorno(1);
      $objSerieDTO->retNumIdSerie();

      $objSerieDB = new SerieBD($this->inicializarObjInfraIBanco());
      $objSerieDTO = $objSerieDB->consultar($objSerieDTO);
        
    if(empty($objSerieDTO)) {
        throw new InfraException(sprintf('Nenhuma serie com o nome "%s" foi encontrada', $strNome));
    }
        
      return $objSerieDTO;
  }
    
    /**
     * Inicializa sess�o em background baseado na sigla da unidade
     * 
     * @return UnidadeDTO
     */
  protected function inicializarUnidade($strSiglaUnidade = ''){
        
    if(empty($strSiglaUnidade)) {
            
        $strSiglaUnidade = 'TESTE';
    }
        
      $objUsuarioDTO = new UsuarioDTO();
      $objUsuarioDTO->setStrSigla('SEI');
      $objUsuarioDTO->setNumMaxRegistrosRetorno(1);
      $objUsuarioDTO->retNumIdUsuario();
        
      $objUsuarioBD = new UsuarioBD($this->inicializarObjInfraIBanco());
      $objUsuarioDTO = $objUsuarioBD->consultar($objUsuarioDTO);
        
    if(empty($objUsuarioDTO)) {
        throw new InfraException(sprintf('Nenhum usu�rio foi localizado pela sigla %s', $strSiglaUnidade));
    }
        
      $objUnidadeDTO = new UnidadeDTO();
      $objUnidadeDTO->setStrSigla($strSiglaUnidade);
      $objUnidadeDTO->retTodos();

      $objUnidadeRN = new UnidadeBD($this->inicializarObjInfraIBanco());
      $objUnidadeDTO = $objUnidadeRN->consultar($objUnidadeDTO);

    if(empty($objUnidadeDTO)) {
        throw new InfraException(sprintf('Unidade com a sigla %s n�o foi localizada', $strSiglaUnidade));
    }
        
      $objSessao = SessaoSEI::getInstance(false, false);
      $numIdUsuario = $objSessao->getNumIdUsuario();
        
        
    if(empty($numIdUsuario)) {
        
        
        $objSessao->simularLogin(null, null, $objUsuarioDTO->getNumIdUsuario(), $objUnidadeDTO->getNumIdUnidade());
    }

      return $objUnidadeDTO;
        
  }
    
    /**
     * Assina um documento por background task
     * 
     * @param array $args Description
     */
  public function assinarDocumento($args = array()){

    if(!array_key_exists('doc-id', $args)) {
        throw new InfraException('Param�tro "doc-id" � obrigat�rio');
    }
        
    if(!array_key_exists('auth-user', $args)) {
        throw new InfraException('Param�tro "doc-id" � obrigat�rio');
    }
        
    if(!array_key_exists('auth-pass', $args)) {
        throw new InfraException('Param�tro "doc-id" � obrigat�rio');
    }
        
      $objDocumentoRN = new DocumentoRN();
        
      $objDocumentoDTO = new DocumentoDTO();
      $objDocumentoDTO->setNumMaxRegistrosRetorno(1);        
      $objDocumentoDTO->setDblIdDocumento($args['doc-id']);
      $objDocumentoDTO->retTodos();
        
      $objDocumentoBD = new DocumentoBD($this->inicializarObjInfraIBanco());
      $objDocumentoDTO = $objDocumentoBD->consultar($objDocumentoDTO);
        
    if(empty($objDocumentoDTO)) {
        throw new InfraException('Nenhum documento foi localizado pela ID '.$args['doc-id']);
    }
      $this->inicializarUnidade($args['sigla']);
        
        
      $objUsuarioDTO = new UsuarioDTO();
      $objUsuarioDTO->setStrSigla($args['auth-user'], InfraDTO::$OPER_LIKE);
      $objUsuarioDTO->setNumMaxRegistrosRetorno(1);
      $objUsuarioDTO->retNumIdUsuario();
        
      $objUsuarioBD = new UsuarioBD($this->inicializarObjInfraIBanco());
      $objUsuarioDTO = $objUsuarioBD->consultar($objUsuarioDTO);
        
    if(empty($objUsuarioDTO)) {
        throw new InfraException('Usu�rio TESTE n�o foi localizado');
    }
        
      $objAssinaturaDTO = new AssinaturaDTO();
      $objAssinaturaDTO->setStrStaFormaAutenticacao('S');
      $objAssinaturaDTO->setNumIdOrgaoUsuario('0');
      $objAssinaturaDTO->setNumIdContextoUsuario(null);
      $objAssinaturaDTO->setNumIdUsuario($objUsuarioDTO->getNumIdUsuario());
      $objAssinaturaDTO->setStrSenhaUsuario($args['auth-pass']);
      $objAssinaturaDTO->setStrCargoFuncao('Testador');
      $objAssinaturaDTO->setArrObjDocumentoDTO(array($objDocumentoDTO));
        
      $objDocumentoRN->assinar($objAssinaturaDTO);
        
      $strRetorno = 'Documento foi assinado';
        
      return PenConsoleRN::format($strRetorno, 'blue');
  }
    
    /**
     * Cria um novo documento por background task
     * 
     * @param array $args Description
     */
  public function criarDocumento($args = array()){
        
    if(!array_key_exists('desc', $args)) {
        throw new InfraException('Param�tro "desc" � obrigat�rio');
    }
        
    if(!array_key_exists('subject', $args)) {
        throw new InfraException('Param�tro c�digo do "subject" � obrigat�rio');
    }
        
    if(!array_key_exists('proc-id', $args)) {
        throw new InfraException('Param�tro "proc-id" � obrigat�rio');
    }
      $objUnidadeDTO = $this->inicializarUnidade($args['sigla']);
      $objSerieDTO = $this->getSerie();
      $objAssuntoDTO = $this->getAssunto($args['subject']);
      $objParticipanteDTO = $this->getParticipante($args['stakeholder']);

      $objProtocoloDTO = new ProtocoloDTO();
      $objProtocoloDTO->setDtaGeracao(InfraData::getStrDataAtual());
      $objProtocoloDTO->setStrStaNivelAcessoLocal(0);
      $objProtocoloDTO->setNumIdHipoteseLegal(null);
      $objProtocoloDTO->setStrStaGrauSigilo(null);
      $objProtocoloDTO->setStrDescricao($args['desc']);
      $objProtocoloDTO->setArrObjParticipanteDTO(array($objParticipanteDTO));
      $objProtocoloDTO->setArrObjRelProtocoloAssuntoDTO(array($objAssuntoDTO));
      $objProtocoloDTO->setArrObjObservacaoDTO(array());
      $objProtocoloDTO->setArrObjAnexoDTO(array());
                
      $objDocumentoDTO = new DocumentoDTO();
      $objDocumentoDTO->setDblIdDocumento(null);
      $objDocumentoDTO->setDblIdProcedimento($args['proc-id']);
      $objDocumentoDTO->setNumIdSerie($objSerieDTO->getNumIdSerie());
      $objProtocoloDTO->setNumIdSerieDocumento($objSerieDTO->getNumIdSerie());
      $objDocumentoDTO->setNumIdUnidadeResponsavel($objUnidadeDTO->getNumIdUnidade());
      $objDocumentoDTO->setStrNumero(null);
      $objDocumentoDTO->setStrSinFormulario('N');
      $objDocumentoDTO->setStrSinBloqueado('N');
      $objDocumentoDTO->setStrStaEditor('I');
      $objDocumentoDTO->setNumVersaoLock(0);
      $objProtocoloDTO->setStrStaNivelAcessoLocal(0);
      $objProtocoloDTO->setStrDescricao($args['desc']);
      $objProtocoloDTO->setDtaGeracao(InfraData::getStrDataAtual());
      $objProtocoloDTO->setArrObjRelProtocoloAssuntoDTO($objAssuntoDTO);
      $objProtocoloDTO->setArrObjParticipanteDTO(array($objParticipanteDTO));
      $objDocumentoDTO->setObjProtocoloDTO($objProtocoloDTO);
        
      $objDocumentoRN = new DocumentoRN();
      $objDocumentoDTO = $objDocumentoRN->gerarRN0003Interno($objDocumentoDTO);
        
      $args['doc-id'] = $objDocumentoDTO->getDblIdDocumento();
        
    if(array_key_exists('auth-user', $args) && array_key_exists('auth-pass', $args)) {
        $this->assinarDocumento($args);
    }
      $strRetorno = sprintf('Gerado documento %s', $objDocumentoDTO->getStrProtocoloDocumentoFormatado());
        
      return PenConsoleRN::format($strRetorno, 'blue');
  }
    
    /**
     * Cria um novo procedimento por background task
     */
  public function criarProcedimento($args = array()){
        
    if(!array_key_exists('desc', $args)) {
        throw new InfraException('Param�tro "desc" � obrigat�rio');
    }
        
    if(!array_key_exists('stakeholder', $args)) {
        throw new InfraException('Param�tro "interessado" � obrigat�rio');
    }
        
    if(!array_key_exists('subject', $args)) {
        throw new InfraException('Param�tro c�digo do "subject" � obrigat�rio');
    }
      
      $objUnidadeDTO = $this->inicializarUnidade($args['sigla']);
        
      // Cadastrar protocolo
      $objProtocoloDTO = new ProtocoloDTO();
      $objProtocoloDTO->setDtaGeracao(InfraData::getStrDataAtual());
      $objProtocoloDTO->setStrStaNivelAcessoLocal(0);
      $objProtocoloDTO->setNumIdHipoteseLegal(null);
      $objProtocoloDTO->setStrStaGrauSigilo(null);
      $objProtocoloDTO->setStrDescricao($args['desc']);
      $objProtocoloDTO->setArrObjParticipanteDTO(array($this->getParticipante($args['stakeholder'])));
      $objProtocoloDTO->setArrObjAnexoDTO(array());
      $objProtocoloDTO->setArrObjRelProtocoloAssuntoDTO($this->getAssunto($args['subject']));
      $objProtocoloDTO->setArrObjObservacaoDTO(array());

      // Tipo Procedimento        
      $objTipoProcedimentoDTO = $this->getTipoProcedimento();
        
      // Cadastra o procedimento
      $objProcedimentoDTO = new ProcedimentoDTO(); 
      $objProcedimentoDTO->setDblIdProcedimento(null);
      //$objProcedimentoDTO->setStrProtocoloProcedimentoFormatado($args['tipo']);        
      $objProcedimentoDTO->setNumIdTipoProcedimento($objTipoProcedimentoDTO->getNumIdTipoProcedimento());
      $objProcedimentoDTO->setStrNomeTipoProcedimento($objTipoProcedimentoDTO->getStrNome());
      $objProcedimentoDTO->setStrSinGerarPendencia('S');
      $objProcedimentoDTO->setNumVersaoLock(0);
      $objProcedimentoDTO->setStrStaEstadoProtocolo(ProtocoloRN::$TE_NORMAL);
        
      $objProcedimentoDTO->setNumIdUnidadeGeradoraProtocolo($objUnidadeDTO->getNumIdUnidade());
        
      // J� salva o protocolo pela RN
      $objProcedimentoDTO->setObjProtocoloDTO($objProtocoloDTO);
        
      $objProcedimentoRN = new ProcedimentoRN();
      $objProcedimentoDTO = $objProcedimentoRN->gerarRN0156($objProcedimentoDTO);

      $args['proc-id'] = $objProcedimentoDTO->getDblIdProcedimento();
        
      $this->criarDocumento($args);
        
      $strRetorno =  sprintf('Gerado procedimento %s com protocolo %s', 
          $objProcedimentoDTO->getDblIdProcedimento(), 
          $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado()
      );
        
      return PenConsoleRN::format($strRetorno, 'blue');
  }
    
    /**
     * Remover um procedimento por background task
     */ 
  public function removerProcedimento($args = array()){
        
    if(!array_key_exists('proc-id', $args)) {
        throw new InfraException('Param�tro "proc-id" � obrigat�rio');
    }
        
      $objDTO = new ProcedimentoDTO();
      $objDTO->setDblIdProcedimento($args['proc-id']);
        
      $objRN = new ProcedimentoRN();
      $objRN->excluirRN0280Interno($objDTO);
        
      return PenConsoleRN::format('Procedimento foi removido com sucesso', 'blue'); 
  }
    
    /**
     * Adiciona um log por command line no sei. Utilizado ao reiniciar os servi�os
     * do gearmand ou supervisor para informar ao interface
     * 
     * @return string
     */ 
  public function log($args = array()){
        
    if(!array_key_exists('msg', $args)) {
        throw new InfraException('Param�tro "msg" � obrigat�rio');
    }
        
      LogSEI::getInstance()->gravar($args['msg']);
        
      return 'Log foi efetuado no SEI';
  }
    
    /**
     * Sincroniza um procedimento com o api-pen remota caso ele n�o tenha a
     * a resposta
     * 
     * @return string
     */ 
  public function syncProcedimento($args = array()){
        
    if(!array_key_exists('protocolo', $args)) {
        throw new InfraException('Param�tro "protocolo" � obrigat�rio');
    }
        
    if(!array_key_exists('sigla', $args)) {
        throw new InfraException('Param�tro "sigla" � obrigat�rio');
    }
        
      $this->inicializarUnidade($args['sigla']);        
        
      $objRN = new ProcessoEletronicoRN();
      return $objRN->consultarEstadoProcedimento($args['protocolo']);
  }
    
    /**
     * 
     * @return string
     */
  public function ajuda(){

      $string .= PHP_EOL;
      $string .= PenConsoleRN::format('Uso: ', 'yellow').PHP_EOL; 
      $string .= '    criarProcedimento --desc="String" --stakeholder="String" --sigla="CHAR(3)" --subject="Number" --auth-user="String" --auth-pass="String"'.PHP_EOL;
      $string .= '    criarDocumento --proc-id="81" --desc="String" --stakeholder="String" --sigla="CHAR(3)" --subject="Number" --auth-user="String" --auth-pass="String"'.PHP_EOL;
      $string .= '    syncProcedimento --protocolo="00000.000000/0000-00"'.PHP_EOL;
      $string .= PHP_EOL;
      $string .= PenConsoleRN::format('Op��es: ', 'yellow').PHP_EOL;      
      $string .= '    --desc          Valor dos campos "Especificacao", "Observacoes desta unidade" e "Descricao".'.PHP_EOL;
      $string .= '    --stakeholder   Valor do campo "Interessados".'.PHP_EOL;
      $string .= '    --subject       Valor no banco de [sei].[assunto].[codigo_estruturado].'.PHP_EOL;
      $string .= '    --auth-user     [Opcional] Login de acesso para assinar o documento.'.PHP_EOL;
      $string .= '    --auth-pass     [Opcional] Senha de acesso para assinar o documento.'.PHP_EOL;
      $string .= '    --sigla         [Opcional] Sigla da unidade que o procedimento sera cadastrado [ Padrao TESTE ].'.PHP_EOL;
      $string .= '    --doc-id        ID do Documento. Obrigatorio em assinarDocumento.'.PHP_EOL;
      $string .= '    --proc-id       ID do Procedimento. Obrigatorio em criarDocumento, assinarDocumento e removerProcedimento'.PHP_EOL;
      $string .= '    --protocolo     Protocolo formatado. Obrigatorio em syncProcedimento'.PHP_EOL;
      $string .= PHP_EOL;
      $string .= PenConsoleRN::format('Comandos: ', 'yellow').PHP_EOL; 
      $string .= '    criarProcedimento'.PHP_EOL;
      $string .= '    criarDocumento'.PHP_EOL;
      $string .= '    assinarDocumento'.PHP_EOL;
      $string .= '    removerProcedimento'.PHP_EOL;
      $string .= '    syncProcedimento'.PHP_EOL;
      $string .= PHP_EOL;
        
      return $string;
  }
}
