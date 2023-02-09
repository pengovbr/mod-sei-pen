<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Controla o log de estados da expadi��o de um procedimento pelo modulo SEI
 *
 *
 */
class ProcedimentoAndamentoRN extends InfraRN
{
    protected $isSetOpts = false;
    protected $dblIdProcedimento;
    protected $dblIdTramite;
    protected $numTarefa;
    protected $strNumeroRegistro;
    private $objPenDebug;


  public function __construct()
    {
          parent::__construct();
          $this->objPenDebug = DebugPen::getInstance("PROCESSAMENTO");
  }

    /**
     * Inv�s de aproveitar o singleton do BancoSEI criamos uma nova inst�ncia para
     * n�o ser afetada pelo transation
     *
     * @return Infra[Driver]
     */
  protected function inicializarObjInfraIBanco(){
      return BancoSEI::getInstance();
  }

  public function setOpts($strNumeroRegistro, $dblIdTramite, $numTarefa, $dblIdProcedimento = null)
    {
      $this->strNumeroRegistro = $strNumeroRegistro;
      $this->dblIdTramite = $dblIdTramite;
      $this->dblIdProcedimento = $dblIdProcedimento;
      $this->numTarefa = $numTarefa;
      $this->isSetOpts = true;
  }

    /**
     * Adiciona um novo andamento � um procedimento que esta sendo expedido para outra unidade
     *
     * @param ProcedimentoAndamentoDTO $parProcedimentoAndamentoDTO
     */
  protected function cadastrarControlado($parProcedimentoAndamentoDTO)
    {
    if($this->isSetOpts === false) {
        throw new InfraException('Log do cadastro de procedimento n�o foi configurado');
    }

      $strMensagem = ($parProcedimentoAndamentoDTO->isSetStrMensagem()) ? $parProcedimentoAndamentoDTO->getStrMensagem() : 'N�o informado';
      $strSituacao = ($parProcedimentoAndamentoDTO->isSetStrSituacao()) ? $parProcedimentoAndamentoDTO->getStrSituacao() : 'N';

      $hash = md5($this->dblIdProcedimento . $strMensagem);
      $objProcedimentoAndamentoDTO = new ProcedimentoAndamentoDTO();
      $objProcedimentoAndamentoDTO->setStrSituacao($strSituacao);
      $objProcedimentoAndamentoDTO->setDthData(date('d/m/Y H:i:s'));
      $objProcedimentoAndamentoDTO->setDblIdProcedimento($this->dblIdProcedimento);
      $objProcedimentoAndamentoDTO->setStrNumeroRegistro($this->strNumeroRegistro);
      $objProcedimentoAndamentoDTO->setDblIdTramite($this->dblIdTramite);
      $objProcedimentoAndamentoDTO->setStrSituacao($strSituacao);
      $objProcedimentoAndamentoDTO->setStrMensagem($strMensagem);
      $objProcedimentoAndamentoDTO->setStrHash($hash);
      $objProcedimentoAndamentoDTO->setNumTarefa($this->numTarefa);

      $objProcedimentoAndamentoBD = new ProcedimentoAndamentoBD($this->getObjInfraIBanco());
      $objProcedimentoAndamentoBD->cadastrar($objProcedimentoAndamentoDTO);
  }


  public function sincronizarRecebimentoProcessos($parStrNumeroRegistro, $parNumIdentificacaoTramite, $numIdTarefa)
    {
    try{
        $objProcedimentoAndamentoDTO = new ProcedimentoAndamentoDTO();
        $objProcedimentoAndamentoDTO->retTodos();
        $objProcedimentoAndamentoDTO->setStrNumeroRegistro($parStrNumeroRegistro);
        $objProcedimentoAndamentoDTO->setDblIdTramite($parNumIdentificacaoTramite);
        $objProcedimentoAndamentoDTO->setNumTarefa($numIdTarefa);
        $objProcedimentoAndamentoDTO->setNumMaxRegistrosRetorno(1);

        $objProcedimentoAndamentoBD = new ProcedimentoAndamentoBD($this->getObjInfraIBanco());
        $objProcedimentoAndamentoDTORet = $objProcedimentoAndamentoBD->consultar($objProcedimentoAndamentoDTO);

      if(!is_null($objProcedimentoAndamentoDTORet)){
        $this->objPenDebug->gravar("Sincronizando o recebimento de processos concorrentes...", 1);
        $objProcedimentoAndamentoDTO = $objProcedimentoAndamentoBD->bloquear($objProcedimentoAndamentoDTORet);
        $this->objPenDebug->gravar("Liberando processo concorrente de recebimento de processo ...", 1);
      }

        return true;

    } catch(InfraException $e){
        // Erros de lock significam que outro processo concorrente j� est� processando a requisi��o
        return false;
    }
  }


    /**
     * Sinaliza o in�cio de recebimento de um tr�mite de processo, recibo de conclus�o de tr�mite ou uma recusa
     *
     * Esta sinaliza��o � utilizada para sincronizar o processamento concorrente que possa existir entre todos os n�s de aplica��o do sistema,
     * evitando inconsist�ncias provocadas pelo cadastramentos simult�neos no sistema
     *
     * @param array $parArrChavesSincronizacao Chaves que ser� utilizadas na sincroniza��o do processamento
     * @return void
     */
  protected function sinalizarInicioRecebimentoControlado($parArrChavesSincronizacao)
    {
      $strNumeroRegistro = $parArrChavesSincronizacao["NumeroRegistro"];
      $numIdTramite = $parArrChavesSincronizacao["IdTramite"];
      $numIdTarefa = $parArrChavesSincronizacao["IdTarefa"];

    if(!$this->sincronizarRecebimentoProcessos($strNumeroRegistro, $numIdTramite, $numIdTarefa)){
        $this->objPenDebug->gravar("Tr�mite de recebimento $numIdTramite j� se encontra em processamento", 3, false);
        return false;
    }

      $this->setOpts($strNumeroRegistro, $numIdTramite, $numIdTarefa);
      $this->cadastrar(ProcedimentoAndamentoDTO::criarAndamento('Iniciando recebimento de processo externo', 'S'));

      return true;
  }

  protected function listarConectado($parObjProcedimentoAndamentoDTO)
    {
      $objProcedimentoAndamentoBD = new ProcedimentoAndamentoBD($this->getObjInfraIBanco());
      return $objProcedimentoAndamentoBD->listar($parObjProcedimentoAndamentoDTO);
  }
}
