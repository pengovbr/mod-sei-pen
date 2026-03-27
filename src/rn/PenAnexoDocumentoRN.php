<?php

require_once DIR_SEI_WEB . '/SEI.php';

class PenAnexoDocumentoRN extends InfraRN
{

  public function __construct()
    {
      parent::__construct();
  }

  protected function inicializarObjInfraIBanco()
    {
      return BancoSEI::getInstance();
  }

  protected function cadastrarConectado(PenAnexoDocumentoDTO $objPenAnexoDocumentoDTO)
    {
    try {
        $objPenAnexoDocumentoBD = new PenAnexoDocumentoBD($this->getObjInfraIBanco());
        return $objPenAnexoDocumentoBD->cadastrar($objPenAnexoDocumentoDTO);
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro cadastrando anexo de documentos.', $e);
    }
  }

  protected function consultarConectado(PenAnexoDocumentoDTO $objPenAnexoDocumentoDTO)
    {
    try {
        $objPenAnexoDocumentoBD = new PenAnexoDocumentoBD($this->getObjInfraIBanco());
        return $objPenAnexoDocumentoBD->consultar($objPenAnexoDocumentoDTO);
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro consultando anexo de documentos.', $e);
    }
  }

  protected function excluirConectado(PenAnexoDocumentoDTO $objPenAnexoDocumentoDTO)
    {
    try {
        $objPenAnexoDocumentoBD = new PenAnexoDocumentoBD($this->getObjInfraIBanco());
        return $objPenAnexoDocumentoBD->excluir($objPenAnexoDocumentoDTO);
    } catch (Exception $e) {
        throw new InfraException('Módulo do Tramita: Erro excluindo anexo de documentos.', $e);
    }
  }

  private function obterDiretorioAnexoModuloPen(PenAnexoDocumentoDTO $objPenAnexoDocumentoDTO)
    {
      return ConfiguracaoSEI::getInstance()->getValor('SEI', 'RepositorioArquivos') . '/mod-pen/' .
        substr($objPenAnexoDocumentoDTO->getDthInclusao(), 6, 4) . '/' .
        substr($objPenAnexoDocumentoDTO->getDthInclusao(), 3, 2) . '/' .
        substr($objPenAnexoDocumentoDTO->getDthInclusao(), 0, 2);
  }

  protected function obterLocalizacaoAnexoModuloPenConectado(PenAnexoDocumentoDTO $objPenAnexoDocumentoDTO)
    {
      return $this->obterDiretorioAnexoModuloPen($objPenAnexoDocumentoDTO) . '/' . $objPenAnexoDocumentoDTO->getNumIdAnexo();
  }

  protected function consultarAnexoModuloPenConectado($numIdAnexo)
    {
      $objPenAnexoDocumentoDTO = new PenAnexoDocumentoDTO();
      $objPenAnexoDocumentoDTO->setNumIdAnexo($numIdAnexo);
      $objPenAnexoDocumentoDTO->setStrSinAtivo('S');
      $objPenAnexoDocumentoDTO->retTodos();

      $objPenAnexoDocumentoBD = new PenAnexoDocumentoBD($this->getObjInfraIBanco());
      return $objPenAnexoDocumentoBD->consultar($objPenAnexoDocumentoDTO);
  }

  protected function cadastrarAnexoModuloPenControlado(PenAnexoDocumentoDTO $objAnexoDTO)
    {
      $strNomeUpload = $objAnexoDTO->getNumIdAnexo();
      $strNomeUploadCompleto = DIR_SEI_TEMP . '/' . $strNomeUpload;

    if (!file_exists($strNomeUploadCompleto) || !is_readable($strNomeUploadCompleto)) {
        throw new InfraException('Módulo do Tramita: Anexo temporário não encontrado ou sem permissão de leitura para armazenamento no repositório do módulo PEN.');
    }

      $objAnexoDTO->setStrHash(hash_file('md5', $strNomeUploadCompleto));

      $objAnexoPenDTO = new PenAnexoDocumentoDTO();
      $objAnexoPenDTO->setNumIdAnexo($objAnexoDTO->getNumIdAnexo());
      $objAnexoPenDTO->setStrNome($objAnexoDTO->getStrNome());
      $objAnexoPenDTO->setDblIdProtocolo($objAnexoDTO->getDblIdProtocolo());
      $objAnexoPenDTO->setStrSinAtivo($objAnexoDTO->getStrSinAtivo());
      $objAnexoPenDTO->setNumIdUnidade($objAnexoDTO->getNumIdUnidade());
      $objAnexoPenDTO->setNumIdUsuario($objAnexoDTO->getNumIdUsuario());
      $objAnexoPenDTO->setNumTamanho($objAnexoDTO->getNumTamanho());
      $objAnexoPenDTO->setDthInclusao($objAnexoDTO->getDthInclusao());
    if ($objAnexoDTO->isSetNumIdBaseConhecimento()) {
      $objAnexoPenDTO->setNumIdBaseConhecimento($objAnexoDTO->getNumIdBaseConhecimento());
    }
    if ($objAnexoDTO->isSetNumIdProjeto()) {
      $objAnexoPenDTO->setNumIdProjeto($objAnexoDTO->getNumIdProjeto());
    }
      $objAnexoPenDTO->setStrHash($objAnexoDTO->getStrHash());

      $objAnexoPenDTO = $this->cadastrarConectado($objAnexoPenDTO);

    try {
        $this->consolidarAnexoFilesystemModuloPen($objAnexoPenDTO, $strNomeUploadCompleto);
    } catch (Exception $e) {
      try {
          $this->excluir($objAnexoPenDTO);
      } catch (Exception $e) {
      }

        throw new InfraException('Módulo do Tramita: Erro consolidando anexo no repositório de arquivos do módulo PEN.', $e);
    }

      return $objAnexoPenDTO;
  }

  public function consolidarAnexoFilesystemModuloPen(PenAnexoDocumentoDTO $objAnexoPenDTO, $strNomeUploadCompleto)
  {
    if (!file_exists($strNomeUploadCompleto) || !is_readable($strNomeUploadCompleto)) {
        throw new InfraException('Módulo do Tramita: Arquivo de origem do anexo não encontrado ou sem permissão de leitura.');
    }

    $strDiretorio = $this->obterDiretorioAnexoModuloPen($objAnexoPenDTO);
    if (!is_dir($strDiretorio) && mkdir($strDiretorio, 0755, true) === false) {
        throw new InfraException('Módulo do Tramita: Erro criando diretório do repositório de arquivos do módulo PEN.');
    }

      $strCaminhoRepositorio = $strDiretorio . '/' . $objAnexoPenDTO->getNumIdAnexo();
    if (copy($strNomeUploadCompleto, $strCaminhoRepositorio) === false) {
        throw new InfraException('Módulo do Tramita: Falha copiando anexo para o repositório de arquivos do módulo PEN.');
    }

    if ($objAnexoPenDTO->getStrHash() !== hash_file('md5', $strCaminhoRepositorio)) {
      if (file_exists($strCaminhoRepositorio)) {
          @unlink($strCaminhoRepositorio);
      }
        throw new InfraException('Módulo do Tramita: Cópia do anexo no repositório de arquivos do módulo PEN corrompida.');
    }

    if (unlink($strNomeUploadCompleto) === false) {
        throw new InfraException('Módulo do Tramita: Falha removendo arquivo temporário após consolidar anexo no repositório do módulo PEN.');
    }
  }

}
