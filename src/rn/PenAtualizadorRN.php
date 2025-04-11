<?php

/**
 * Atualizador abstrato para sistema do SEI para instalar/atualizar o módulo PEN
 */
abstract class PenAtualizadorRN extends InfraRN
{

    protected $sei_versao;

    /**
     * @var string Versão mínima requirida pelo sistema para instalação do PEN
     */
    protected $versaoMinRequirida;

    /**
     * @var InfraIBanco Instância da classe de persistência com o banco de dados
     */
    protected $objBanco;

    /**
     * @var InfraMetaBD Instância do metadata do banco de dados
     */
    protected $objMeta;

    /**
     * @var InfraDebug Instância do debuger
     */
    protected $objDebug;

    /**
     * @var integer Tempo de execução do script
     */
    protected $numSeg = 0;

    protected $objInfraBanco ;

  protected function inicializarObjInfraIBanco()
    {

    if (empty($this->objInfraBanco)) {
        $this->objInfraBanco = BancoSEI::getInstance();
        $this->objInfraBanco->abrirConexao();
    }

      return $this->objInfraBanco;
  }

    /**
     * Inicia a conexão com o banco de dados
     */
  protected function inicializarObjMetaBanco()
    {
    if (empty($this->objMeta)) {
        $this->objMeta = new PenMetaBD($this->inicializarObjInfraIBanco());
    }
      return $this->objMeta;
  }

    /**
     * Adiciona uma mensagem ao output para o usuário
     */
  protected function logar($strMsg)
    {
      $this->objDebug->gravar($strMsg);
  }

    /**
     * Inicia o script criando um contator interno do tempo de execução
     */
  protected function inicializar($strTitulo)
    {

      $this->numSeg = InfraUtil::verificarTempoProcessamento();

      $this->logar($strTitulo);
  }

    /**
     * Finaliza o script informando o tempo de execução.
     */
  protected function finalizar($strMsg = null, $bolErro = false)
    {
    if (!$bolErro) {
        $this->numSeg = InfraUtil::verificarTempoProcessamento($this->numSeg);
        $this->logar('TEMPO TOTAL DE EXECUCAO: ' . $this->numSeg . ' s');
    }else{
        $strMsg = 'ERRO: '.$strMsg;
    }

    if ($strMsg!=null) {
        $this->logar($strMsg);
    }

      InfraDebug::getInstance()->setBolLigado(false);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(false);
      $this->numSeg = 0;
  }

    /**
     * Construtor
     *
     * @param array $arrArgs Argumentos enviados pelo script
     */
  public function __construct()
    {

      parent::__construct();
      ini_set('max_execution_time', '0');
      ini_set('memory_limit', '-1');
      // ini_set('zlib.output_compression', '0');
      ini_set('implicit_flush', '1');
      ob_implicit_flush();

      $this->inicializarObjInfraIBanco();
      $this->inicializarObjMetaBanco();

      $this->objDebug = InfraDebug::getInstance();
      $this->objDebug->setBolLigado(true);
      $this->objDebug->setBolDebugInfra(true);
      $this->objDebug->setBolEcho(true);
      $this->objDebug->limpar();
  }

}
