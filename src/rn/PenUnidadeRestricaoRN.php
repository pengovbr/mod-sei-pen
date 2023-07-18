<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Description of PenUnidadeRestricaoEnvioRN
 *
 *
 */
class PenUnidadeRestricaoRN extends InfraRN
{

  /**
   * Inicializa o obj do banco da Infra
   * @return obj
   */
  protected function inicializarObjInfraIBanco()
  {
    return BancoSEI::getInstance();
  }

  /**
   * Método utilizado para listagem de dados.
   * @param PenUnidadeRestricaoDTO $objPenUnidadeRestricaoDTO
   * @return array
   * @throws InfraException
   */
  protected function listarConectado(PenUnidadeRestricaoDTO $objPenUnidadeRestricaoDTO)
  {
    try {
      $objPenUnidadeRestricaoBD = new PenUnidadeRestricaoBD($this->getObjInfraIBanco());
      return $objPenUnidadeRestricaoBD->listar($objPenUnidadeRestricaoDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro listando Unidades.', $e);
    }
  }

  /**
   * Método utilizado para preparar cadastro de dados.
   * @param string $hdnRepoEstruturas
   * @param string $IdUnidade
   * @param string $IdUnidadeRH
   * @return array
   * @throws InfraException
   */
  public function prepararRepoEstruturas($IdUnidade, $IdUnidadeRH, $hdnRepoEstruturas)
  {
    $arrayObjPenUnidadeRestricaoDTO = array();
    $arrOpcoes = PaginaSEI::getInstance()->getArrOptionsSelect($hdnRepoEstruturas);
    foreach ($arrOpcoes as $opcoes) {
      $hdnRepoEstruturasUnidades = 'hdnRepoEstruturas' . $opcoes[0];
      if(array_key_exists($hdnRepoEstruturasUnidades, $_POST) && !empty($_POST[$hdnRepoEstruturasUnidades])) {
        $arrOpcoesUnidades = PaginaSEI::getInstance()->getArrOptionsSelect($_POST[$hdnRepoEstruturasUnidades]);
        foreach ($arrOpcoesUnidades as $opcoesUnidades) {
          $objPenUnidadeRestricaoDTO = new PenUnidadeRestricaoDTO();
          $objPenUnidadeRestricaoDTO->setNumIdUnidade($IdUnidade);
          $objPenUnidadeRestricaoDTO->setNumIdUnidadeRH($IdUnidadeRH);
          $objPenUnidadeRestricaoDTO->setNumIdUnidadeRestricao($opcoes[0]);
          $objPenUnidadeRestricaoDTO->setStrNomeUnidadeRestricao($opcoes[1]);
          $objPenUnidadeRestricaoDTO->setNumIdUnidadeRHRestricao($opcoesUnidades[0]);
          $objPenUnidadeRestricaoDTO->setStrNomeUnidadeRHRestricao($opcoesUnidades[1]);
          $arrayObjPenUnidadeRestricaoDTO[] = $objPenUnidadeRestricaoDTO;
        }
      } else {
        $objPenUnidadeRestricaoDTO = new PenUnidadeRestricaoDTO();
        $objPenUnidadeRestricaoDTO->setNumIdUnidade($IdUnidade);
        $objPenUnidadeRestricaoDTO->setNumIdUnidadeRH($IdUnidadeRH);
        $objPenUnidadeRestricaoDTO->setNumIdUnidadeRestricao($opcoes[0]);
        $objPenUnidadeRestricaoDTO->setStrNomeUnidadeRestricao($opcoes[1]);
        $arrayObjPenUnidadeRestricaoDTO[] = $objPenUnidadeRestricaoDTO;
      }
    }
    return $arrayObjPenUnidadeRestricaoDTO;
  }

  /**
   * Método utilizado para cadastro de lista de dados.
   * @param array $arrayObjDTO
   * @return array
   * @throws InfraException
   */
  protected function cadastrarConectado($arrayObjDTO)
  {
    try {
      $retArrayObjDTO = array();
      $objBD = new PenUnidadeRestricaoBD(BancoSEI::getInstance());
      foreach ($arrayObjDTO as $objDTO) {
        $retArrayObjDTO[] = $objBD->cadastrar($objDTO);
      }
      return $retArrayObjDTO;
    } catch (Exception $e) {
      throw new InfraException('Erro cadastrando restrição de tramite no mapeamento de unidades.', $e);
    }
  }

  /**
   * Método utilizado para exclusão de dados.
   * @param PenUnidadeRestricaoDTO $objDTO
   * @return array
   * @throws InfraException
   */
  protected function prepararExcluirControlado(PenUnidadeRestricaoDTO $objDTO)
  {
    try {
      $arrayObjPenUnidadeRestricaoDTO = array();
      $objDTO->retTodos();
      $objPenUnidadeRestricaoDTO = $this->listar($objDTO);
      if ($objPenUnidadeRestricaoDTO != null) {
        foreach ($objPenUnidadeRestricaoDTO as $value) {
          $arrayObjPenUnidadeRestricaoDTO[] = $this->excluir($value);
        }
      }
      return $arrayObjPenUnidadeRestricaoDTO;
    } catch (Exception $e) {
      throw new InfraException('Erro excluindo mapeamento de unidades.', $e);
    }
  }

  /**
   * Método utilizado para exclusão de dados.
   * @param PenUnidadeRestricaoDTO $objDTO
   * @return array
   * @throws InfraException
   */
  protected function excluirControlado(PenUnidadeRestricaoDTO $objDTO)
  {
    try {
      $objBD = new PenUnidadeRestricaoBD(BancoSEI::getInstance());
      return $objBD->excluir($objDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro excluindo mapeamento de unidades.', $e);
    }
  }

  /**
   * Método utilizado para contagem de unidades mapeadas
   * @param UnidadeDTO $objUnidadeDTO
   * @return array
   * @throws InfraException
   */
  protected function contarConectado(PenUnidadeRestricaoDTO $objPenUnidadeDTO)
  {
    try {
      //Valida Permissao
      $objPenUnidadeBD = new PenUnidadeRestricaoBD($this->getObjInfraIBanco());
      return $objPenUnidadeBD->contar($objPenUnidadeDTO);
    } catch (Exception $e) {
      throw new InfraException('Erro contando mapeamento de unidades.', $e);
    }
  }
}
