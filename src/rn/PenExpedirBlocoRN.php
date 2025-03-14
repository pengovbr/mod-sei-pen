<?php

require_once DIR_SEI_WEB . '/SEI.php';

class PenExpedirBlocoRN extends InfraRN
{

    private $barraProgresso;
    private $objExpedirProcedimentoRN;
    private $objPenDebug;

  public function __construct()
    {
      parent::__construct();

      //TODO: Remover criao de objetos de negcio no construtor da classe para evitar problemas de performance desnecessrios
      $this->objProcessoEletronicoRN = new ProcessoEletronicoRN();
      $this->objProcedimentoAndamentoRN = new ProcedimentoAndamentoRN();
      $this->objExpedirProcedimentoRN = new ExpedirProcedimentoRN();

      $this->barraProgresso = new InfraBarraProgresso();
      $this->barraProgresso->setNumMin(0);
      $this->objPenDebug = DebugPen::getInstance("PROCESSAMENTO");
  }

  protected function inicializarObjInfraIBanco()
    {
      return BancoSEI::getInstance();
  }

  public function gravarLogDebug($parStrMensagem, $parNumIdentacao = 0, $parBolLogTempoProcessamento = true)
    {
      $this->objPenDebug->gravar($parStrMensagem, $parNumIdentacao, $parBolLogTempoProcessamento);
  }
    
  private function validarParametrosBloco(InfraException $objInfraException, PenBlocoProcessoDTO $objBlocoDTO)
    {
    if(!isset($objBlocoDTO)) {
        $objInfraException->adicionarValidacao('Par�metro $objBlocoDTO n�o informado.');
    }

      //TODO: Validar se repositrio de origem foi informado
    if (InfraString::isBolVazia($objBlocoDTO->getNumIdRepositorioOrigem())) {
        $objInfraException->adicionarValidacao('Identifica��o do reposit�rio de estruturas da unidade atual n�o informado.');
    }

      //TODO: Validar se unidade de origem foi informado
    if (InfraString::isBolVazia($objBlocoDTO->getNumIdUnidadeOrigem())) {
        $objInfraException->adicionarValidacao('Identifica��o da unidade atual no reposit�rio de estruturas organizacionais n�o informado.');
    }

      //TODO: Validar se repositrio foi devidamente informado
    if (InfraString::isBolVazia($objBlocoDTO->getNumIdRepositorioDestino())) {
        $objInfraException->adicionarValidacao('Reposit�rio de estruturas organizacionais n�o informado.');
    }

      //TODO: Validar se unidade foi devidamente informada
    if (InfraString::isBolVazia($objBlocoDTO->getNumIdUnidadeDestino()) || InfraString::isBolVazia($objBlocoDTO->getStrUnidadeDestino())) {
        $objInfraException->adicionarValidacao('Unidade de destino n�o informado.');
    }

      //TODO: Validar se usu�rio foi devidamente informada
    if (InfraString::isBolVazia($objBlocoDTO->getNumIdUsuario())) {
        $objInfraException->adicionarValidacao('Usu�rio n�o informado.');
    }
        
      //TODO: Validar se usu�rio foi devidamente informada
    if (InfraString::isBolVazia($objBlocoDTO->getDthRegistro())) {
        $objInfraException->adicionarValidacao('Data do registro n�o informada.');
    }

  }

  protected function cadastrarBlocoControlado(PenBlocoProcessoDTO $objPenBlocoProcessoDTO)
    {
    try {
        //Valida Permiss�o
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_expedir_bloco', __METHOD__, $objPenBlocoProcessoDTO);

        $this->barraProgresso->exibir();
        $this->barraProgresso->setStrRotulo(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_VALIDACAO);

        //Obt�m o tamanho total da barra de progreso
        $nrTamanhoTotalBarraProgresso = count($objPenBlocoProcessoDTO->getArrListaProcedimento());

        //Atribui o tamanho m�ximo da barra de progresso
        $this->barraProgresso->setNumMax($nrTamanhoTotalBarraProgresso);

        //Exibe a barra de progresso ap�s definir o seu tamanho
        $this->barraProgresso->mover(ProcessoEletronicoINT::NEE_EXPEDICAO_ETAPA_PROCEDIMENTO);

        $objInfraException = new InfraException();
        $this->validarParametrosBloco($objInfraException, $objPenBlocoProcessoDTO);

      if ($objPenBlocoProcessoDTO->isSetArrListaProcedimento()) {

        $objPenBlocoProcessoRN = new PenBlocoProcessoRN();

        foreach ($objPenBlocoProcessoDTO->getArrListaProcedimento() as $dblIdProcedimento) {
          try {

            $objProcedimentoDTO = $this->objExpedirProcedimentoRN->consultarProcedimento($dblIdProcedimento);
            $this->barraProgresso->setStrRotulo(sprintf(ProcessoEletronicoINT::TEE_EXPEDICAO_ETAPA_PROCEDIMENTO, $objProcedimentoDTO->getStrProtocoloProcedimentoFormatado()));

            //Bloquea o processo para atualiza��o
            $idAtividadeExpedicao = $this->objExpedirProcedimentoRN->bloquearProcedimentoExpedicao($objPenBlocoProcessoDTO, $dblIdProcedimento);

            $objDto = new PenBlocoProcessoDTO();
            $objDto->setNumIdBloco($objPenBlocoProcessoDTO->getNumIdBloco());
            $objDto->setDblIdProtocolo($dblIdProcedimento);
            $objDto->retTodos();

            $objPenBlocoProcesso = $objPenBlocoProcessoRN->consultar($objDto);

            $objPenBlocoProcesso->setNumIdAtividade($idAtividadeExpedicao);
            $objPenBlocoProcessoRN->alterar($objPenBlocoProcesso);

            $this->barraProgresso->mover($this->barraProgresso->getNumMax());
            $this->barraProgresso->setStrRotulo(ProcessoEletronicoINT::TEE_EXPEDICAO_BLOCO_ETAPA_CONCLUSAO);
          } catch (\Exception $e) {
                //Realiza o desbloqueio do processo
            try {
              $this->objExpedirProcedimentoRN->desbloquearProcessoExpedicao($objPenBlocoProcessoDTO->getDblIdProcedimento());
            } catch (InfraException $ex) {
            }
                throw $e;
          }
        }
      }
    } catch (\Exception $e) {
        throw new InfraException('M�dulo do Tramita: Falha de comunica��o com o servi�os de integra��o. Por favor, tente novamente mais tarde.', $e);
    }
  }

}
