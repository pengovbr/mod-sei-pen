<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Controla o log de estados da expadição de um procedimento pelo modulo SEI
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
     * Invés de aproveitar o singleton do BancoSEI criamos uma nova instância para
     * não ser afetada pelo transation
     *
     * @return Infra[Driver]
     */
  protected function inicializarObjInfraIBanco()
    {
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
     * Adiciona um novo andamento à um procedimento que esta sendo expedido para outra unidade
     *
     * @param ProcedimentoAndamentoDTO $parProcedimentoAndamentoDTO
     */
  protected function cadastrarControlado($parProcedimentoAndamentoDTO)
    {
    if($this->isSetOpts === false) {
        throw new InfraException('Módulo do Tramita: Log do cadastro de procedimento não foi configurado');
    }

      $strMensagem = ($parProcedimentoAndamentoDTO->isSetStrMensagem()) ? $parProcedimentoAndamentoDTO->getStrMensagem() : 'Não informado';
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

      if(!is_null($objProcedimentoAndamentoDTORet)) {
        $this->objPenDebug->gravar("Sincronizando o recebimento de processos concorrentes...", 1);
        $objProcedimentoAndamentoDTO = $objProcedimentoAndamentoBD->bloquear($objProcedimentoAndamentoDTORet);
        $this->objPenDebug->gravar("Liberando processo concorrente de recebimento de processo ...", 1);
      }

        return true;

    } catch(InfraException $e){
        // Erros de lock significam que outro processo concorrente já está processando a requisição
        return false;
    }
  }


    /**
     * Sinaliza o início de recebimento de um trâmite de processo, recibo de conclusão de trâmite ou uma recusa
     *
     * Esta sinalização é utilizada para sincronizar o processamento concorrente que possa existir entre todos os nós de aplicação do sistema,
     * evitando inconsistências provocadas pelo cadastramentos simultâneos no sistema
     *
     * @param  array $parArrChavesSincronizacao Chaves que serã utilizadas na sincronização do processamento
     * @return void
     */
  protected function sinalizarInicioRecebimentoControlado($parArrChavesSincronizacao)
    {
      $strNumeroRegistro = $parArrChavesSincronizacao["NumeroRegistro"];
      $numIdTramite = $parArrChavesSincronizacao["IdTramite"];
      $numIdTarefa = $parArrChavesSincronizacao["IdTarefa"];

    if(!$this->sincronizarRecebimentoProcessos($strNumeroRegistro, $numIdTramite, $numIdTarefa)) {
        $this->objPenDebug->gravar("Trâmite de recebimento $numIdTramite já se encontra em processamento", 3, false);
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
