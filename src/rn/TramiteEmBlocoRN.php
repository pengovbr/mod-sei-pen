<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Description of TramiteEmBloco
 *
 * Tramitar em bloco
 */
class TramiteEmBlocoRN extends InfraRN
{

  public static $TB_INTERNO = 'I';

  public static $TE_ABERTO = 'A';
  public static $TE_DISPONIBILIZADO = 'D';
  public static $TE_RETORNADO = 'R';
  public static $TE_CONCLUIDO = 'C';
  public static $TE_CONCLUIDO_PARCIALMENTE = 'P';

    /**
     * Inicializa o obj do banco da Infra
     *
     * @return obj
     */
  protected function inicializarObjInfraIBanco()
    {
      return BancoSEI::getInstance();
  }

  public function getNumMaxTamanhoDescricao()
    {
      return 250;
  }

  private function validarStrStaTipo(TramiteEmBlocoDTO $objTramiteEmBlocoDTO, InfraException $objInfraException)
    {
    if (InfraString::isBolVazia($objTramiteEmBlocoDTO->getStrStaTipo())) {
        $objInfraException->adicionarValidacao('Tipo não informado.');
    } else {
      if (!in_array($objTramiteEmBlocoDTO->getStrStaTipo(), InfraArray::converterArrInfraDTO($this->listarValoresTipo(), 'StaTipo'))) {
          $objInfraException->adicionarValidacao('Tipo inválido.');
      }
    }
  }

  private function validarNumIdUsuario(TramiteEmBlocoDTO $objTramiteEmBlocoDTO, InfraException $objInfraException)
    {
    if (InfraString::isBolVazia($objTramiteEmBlocoDTO->getNumIdUsuario())) {
        $objInfraException->adicionarValidacao('Usuário não informado.');
    }
  }

  private function validarStrDescricao(TramiteEmBlocoDTO $objTramiteEmBlocoDTO, InfraException $objInfraException)
    {
    if (InfraString::isBolVazia($objTramiteEmBlocoDTO->getStrDescricao())) {
        $objTramiteEmBlocoDTO->setStrDescricao(null);
    } else {
        $objTramiteEmBlocoDTO->setStrDescricao(trim($objTramiteEmBlocoDTO->getStrDescricao()));
        $objTramiteEmBlocoDTO->setStrDescricao(InfraUtil::filtrarISO88591($objTramiteEmBlocoDTO->getStrDescricao()));
      if (strlen($objTramiteEmBlocoDTO->getStrDescricao()) > $this->getNumMaxTamanhoDescricao()) {
          $objInfraException->adicionarValidacao('Descrição possui tamanho superior a ' . $this->getNumMaxTamanhoDescricao() . ' caracteres.');
      }
    }
  }

  private function validarStrIdxBloco(TramiteEmBlocoDTO $objTramiteEmBlocoDTO, InfraException $objInfraException)
    {
    if (InfraString::isBolVazia($objTramiteEmBlocoDTO->getStrIdxBloco())) {
        $objTramiteEmBlocoDTO->setStrIdxBloco(null);
    } else {
        $objTramiteEmBlocoDTO->setStrIdxBloco(trim($objTramiteEmBlocoDTO->getStrIdxBloco()));
      if (strlen($objTramiteEmBlocoDTO->getStrIdxBloco()) > 500) {
          $objInfraException->adicionarValidacao('Indexação possui tamanho superior a 500 caracteres.');
      }
    }
  }

  private function validarStrStaEstado(TramiteEmBlocoDTO $objTramiteEmBlocoDTO, InfraException $objInfraException)
    {
    if (InfraString::isBolVazia($objTramiteEmBlocoDTO->getStrStaEstado())) {
        $objInfraException->adicionarValidacao('Estado não informado.');
    } else {

      if (!in_array($objTramiteEmBlocoDTO->getStrStaEstado(), InfraArray::converterArrInfraDTO($this->listarValoresEstado(), 'StaEstado'))) {
          $objInfraException->adicionarValidacao('Estado inválido.');
      }
    }
  }

  public function listarValoresTipo()
    {
    try {

        $arrObjTipoDTO = [];

        $objTipoDTO = new TipoDTO();
        $objTipoDTO->setStrStaTipo(self::$TB_INTERNO);
        $objTipoDTO->setStrDescricao('Interno');
        $arrObjTipoDTO[] = $objTipoDTO;


        return $arrObjTipoDTO;
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro listando valores de Tipo.', $e);
    }
  }

  public function listarValoresEstado()
    {
    try {
        $arrEstadoBlocoDescricao = $this->retornarEstadoDescricao();
        $objArrEstadoBlocoDTO = [];
      foreach ($arrEstadoBlocoDescricao as $TE_Estado => $estadoDescricao) {
        $objEstadoBlocoDTO = new EstadoBlocoDTO();
        $objEstadoBlocoDTO->setStrStaEstado($TE_Estado);
        $objEstadoBlocoDTO->setStrDescricao($estadoDescricao);
        $objArrEstadoBlocoDTO[] = $objEstadoBlocoDTO;
      }

        return $objArrEstadoBlocoDTO;
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro listando valores de Estado.', $e);
    }
  }

  public function retornarEstadoDescricao($estado = null)
    {
    try {
        $arrEstadoBloco = [
        self::$TE_ABERTO => 'Aberto',
        self::$TE_DISPONIBILIZADO => 'Aguardando Processamento',
        self::$TE_CONCLUIDO => 'Concluído',
        self::$TE_CONCLUIDO_PARCIALMENTE => 'Concluído Parcialmente',
        self::$TE_RETORNADO => 'Retornado',
        ];

        return $estado ? $arrEstadoBloco[$estado] : $arrEstadoBloco;
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Estado nâo encontrado.', $e);
    }
  }

    /**
     * Metodo responsável por verificar se existe uma unidade mapeada para a unidade logada
     *
     * @return bool
     */
  public function existeUnidadeMapeadaParaUnidadeLogada()
    {
      $objPenUnidadeDTO = new PenUnidadeDTO();
      $objPenUnidadeDTO->retNumIdUnidade();
      $objPenUnidadeDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());
      $objPenUnidadeRN = new PenUnidadeRN();

      return $objPenUnidadeRN->contar($objPenUnidadeDTO) > 0;
  }

  protected function listarConectado(TramiteEmBlocoDTO $objTramiteEmBlocoDTO)
    {
    try {
        //Valida Permissao
        SessaoSEI::getInstance()->validarAuditarPermissao('md_pen_tramita_em_bloco', __METHOD__, $objTramiteEmBlocoDTO);


      if ($objTramiteEmBlocoDTO->isRetStrTipoDescricao()) {
        $objTramiteEmBlocoDTO->retStrStaTipo();
      }

        $objTramiteEmBlocoBD = new TramiteEmBlocoBD($this->getObjInfraIBanco());
        $ret = $objTramiteEmBlocoBD->listar($objTramiteEmBlocoDTO);

      if ($objTramiteEmBlocoDTO->isRetStrTipoDescricao()) {
          $arrObjTipoDTO = $this->listarValoresTipo();
        foreach ($ret as $dto) {
          foreach ($arrObjTipoDTO as $objTipoDTO) {
            if ($dto->getStrStaTipo() == $objTipoDTO->getStrStaTipo()) {
              $dto->setStrTipoDescricao($objTipoDTO->getStrDescricao());
              break;
            }
          }
        }
      }
        return $ret;
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro listando Tramite em Blocos.', $e);
    }
  }

  protected function montarIndexacaoControlado(TramiteEmBlocoDTO $obTramiteEmBlocoDTO)
    {
    try {

        $dto = new TramiteEmBlocoDTO();
        $dto->retNumId();
        $dto->retStrDescricao();

      if (is_array($obTramiteEmBlocoDTO->getNumId())) {
        $dto->setNumId($obTramiteEmBlocoDTO->getNumId(), InfraDTO::$OPER_IN);
      } else {
          $dto->setNumId($obTramiteEmBlocoDTO->getNumId());
      }

        $objTramiteEmBlocoDTOIdx = new TramiteEmBlocoDTO();
        $objInfraException = new InfraException();
        $objTramiteEmBlocoBD = new TramiteEmBlocoBD($this->getObjInfraIBanco());

        $arrObjTramiteEmBlocoDTO = $this->listar($dto);

      foreach ($arrObjTramiteEmBlocoDTO as $dto) {

          $objTramiteEmBlocoDTOIdx->setNumId($dto->getNumId());
          $objTramiteEmBlocoDTOIdx->setStrIdxBloco(InfraString::prepararIndexacao($dto->getNumId() . ' ' . $dto->getStrDescricao()));

          $this->validarStrIdxBloco($objTramiteEmBlocoDTOIdx, $objInfraException);
          $objInfraException->lancarValidacoes();

          $objTramiteEmBlocoBD->alterar($objTramiteEmBlocoDTOIdx);
      }
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro montando indexação de bloco.', $e);
    }
  }

  protected function cadastrarControlado(TramiteEmBlocoDTO $objTramiteEmBlocoDTO)
    {
    try {

        //Valida Permissao
        //           / SessaoSEI::getInstance()->validarAuditarPermissao('pen_tramite_em_bloco_cadastrar',__METHOD__,$objTramiteEmBlocoDTO);

        //Regras de Negocio
        $objInfraException = new InfraException();


        $this->validarStrStaTipo($objTramiteEmBlocoDTO, $objInfraException);
        $this->validarStrDescricao($objTramiteEmBlocoDTO, $objInfraException);
        $this->validarStrIdxBloco($objTramiteEmBlocoDTO, $objInfraException);
        $this->validarStrStaEstado($objTramiteEmBlocoDTO, $objInfraException);


        $objInfraException->lancarValidacoes();

        $objTramiteEmBlocoBD = new TramiteEmBlocoBD($this->getObjInfraIBanco());
        $ret = $objTramiteEmBlocoBD->cadastrar($objTramiteEmBlocoDTO);

        $this->montarIndexacao($ret);

        return $ret;
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro cadastrando Bloco.', $e);
    }
  }

  protected function consultarConectado(TramiteEmBlocoDTO $objTramiteEmBlocoDTO)
    {
    try {

        //Valida Permissao
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_tramite_em_bloco_consultar', __METHOD__, $objTramiteEmBlocoDTO);

      if ($objTramiteEmBlocoDTO->isRetStrTipoDescricao()) {
        $objTramiteEmBlocoDTO->retStrStaTipo();
      }

        $objTramiteEmBlocoBD = new TramiteEmBlocoBD($this->getObjInfraIBanco());
        $ret = $objTramiteEmBlocoBD->consultar($objTramiteEmBlocoDTO);

      if ($ret != null) {
        if ($objTramiteEmBlocoDTO->isRetStrTipoDescricao()) {
          $arrObjTipoDTO = $this->listarValoresTipo();
          foreach ($arrObjTipoDTO as $objTipoDTO) {
            if ($ret->getStrStaTipo() == $objTipoDTO->getStrStaTipo()) {
              $ret->setStrTipoDescricao($objTipoDTO->getStrDescricao());
              break;
            }
          }
        }
      }
        //Auditoria

        return $ret;
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro consultando Bloco.', $e);
    }
  }

  protected function alterarControlado(TramiteEmBlocoDTO $objTramiteEmBlocoDTO)
    {
    try {

        //Valida Permissao
        SessaoSEI::getInstance()->validarAuditarPermissao('pen_tramite_em_bloco_alterar', __METHOD__, $objTramiteEmBlocoDTO);

        //Regras de Negocio
        $objInfraException = new InfraException();

        $dto = new TramiteEmBlocoDTO();
        $dto->retStrStaTipo();
        $dto->setNumId($objTramiteEmBlocoDTO->getNumId());

        $dto = $this->consultar($dto);

      if ($objTramiteEmBlocoDTO->isSetStrStaTipo() && $objTramiteEmBlocoDTO->getStrStaTipo() != $dto->getStrStaTipo()) {
        $objInfraException->lancarValidacao('Não é possível alterar o tipo do bloco.');
      }

        $objTramiteEmBlocoDTO->setStrStaTipo($dto->getStrStaTipo());

      if ($objTramiteEmBlocoDTO->isSetStrStaTipo()) {
          $this->validarStrStaTipo($objTramiteEmBlocoDTO, $objInfraException);
      }
      if ($objTramiteEmBlocoDTO->isSetNumIdUsuario()) {
          $this->validarNumIdUsuario($objTramiteEmBlocoDTO, $objInfraException);
      }
      if ($objTramiteEmBlocoDTO->isSetStrDescricao()) {
          $this->validarStrDescricao($objTramiteEmBlocoDTO, $objInfraException);
      }
      if ($objTramiteEmBlocoDTO->isSetStrIdxBloco()) {
          $this->validarStrIdxBloco($objTramiteEmBlocoDTO, $objInfraException);
      }
      if ($objTramiteEmBlocoDTO->isSetStrStaEstado()) {
          $this->validarStrStaEstado($objTramiteEmBlocoDTO, $objInfraException);
      }

        $objInfraException->lancarValidacoes();

        $objTramiteEmBlocoBD = new TramiteEmBlocoBD($this->getObjInfraIBanco());
        $objTramiteEmBlocoBD->alterar($objTramiteEmBlocoDTO);

        $this->montarIndexacao($objTramiteEmBlocoDTO);

        //Auditoria

    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro alterando Bloco.', $e);
    }
  }

    /**
     * Método utilizado para exclusão de dados.
     *
     * @return array
     * @throws InfraException
     */
  protected function excluirControlado(array $arrayObjDTO)
    {
    try {
        //Valida Permissao
        SessaoSEI::getInstance()->validarAuditarPermissao('md_pen_tramita_em_bloco_excluir', __METHOD__, $arrayObjDTO);

        $arrayExcluido = [];
      foreach ($arrayObjDTO as $objDTO) {
        $objBD = new TramiteEmBlocoBD(BancoSEI::getInstance());
        $arrayExcluido[] = $objBD->excluir($objDTO);
      }
        return $arrayExcluido;
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro excluindo Bloco.', $e);
    }
  }

  protected function cancelarControlado(array $blocoIds)
    {
    try {
        $objBloco = new PenBlocoProcessoDTO();
      foreach ($blocoIds as $blocoId) {
        $objBloco->setNumIdBloco($blocoId);
        $objBloco->retDblIdProtocolo();
        $objPenBlocoProcessoRN = new PenBlocoProcessoRN();
        $protocoloIds = $objPenBlocoProcessoRN->listar($objBloco);
        $protocoloRn = new ExpedirProcedimentoRN();
        foreach ($protocoloIds as $protocoloId) {
            $protocoloRn->cancelarTramite($protocoloId->getDblIdProtocolo());
        }
      }
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro cancelando Bloco.', $e);
    }
  }
}
