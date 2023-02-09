<?php
require_once DIR_SEI_WEB.'/SEI.php';

class ReceberComponenteDigitalRN extends InfraRN
{
    private $objProcessoEletronicoRN;
    private $objInfraParametro;
    private $arrAnexos = array();

  public function __construct()
    {
      parent::__construct();

      $this->objInfraParametro = new InfraParametro(BancoSEI::getInstance());
      $this->objProcessoEletronicoRN = new ProcessoEletronicoRN();
  }

  public function setArrAnexos($arrAnexos){
      $this->arrAnexos = $arrAnexos;
  }

  public function getArrAnexos(){
      return $this->arrAnexos;
  }

  protected function inicializarObjInfraIBanco()
    {
      return BancoSEI::getInstance();
  }

  protected function receberComponenteDigitalControlado(ComponenteDigitalDTO $parObjComponenteDigitalDTO)
    {
    if(!isset($parObjComponenteDigitalDTO) || !isset($parObjComponenteDigitalDTO)) {
        throw new InfraException('Par�metro $parObjComponenteDigitalDTO n�o informado.');
    }

      $objAnexoDTO = null;
    foreach($this->arrAnexos as $key => $objAnexo){
      if(array_key_exists($parObjComponenteDigitalDTO->getStrHashConteudo(), $objAnexo) &&  $objAnexo['recebido'] == false){
          $objAnexoDTO = $objAnexo[$parObjComponenteDigitalDTO->getStrHashConteudo()];
          $this->arrAnexos[$key]['recebido'] = true;
          break;
      }
    }

    if(is_null($objAnexoDTO)){
        throw new InfraException('Anexo '.$parObjComponenteDigitalDTO->getStrHashConteudo().' n�o encontrado '.var_export($this->arrAnexos, true));
    }

      //Transferir documentos validados para o reposit�rio final de arquivos
      $this->cadastrarComponenteDigital($parObjComponenteDigitalDTO, $objAnexoDTO);

      //Registrar anexo relacionado com o componente digital
      $this->atualizarAnexoDoComponenteDigital($parObjComponenteDigitalDTO, $objAnexoDTO);
  }

    /**
     * Este m�todo:
     *  Atribui os anexos como recebidos
     *  Chama o m�todo que faz a compacta��o dos anexos, para caso de mais de um componente
     *
     * @param $parNumIdDocumento
     * @param $parArrObjComponenteDigitalDTO
     * @return array|mixed|null
     * @throws InfraException
     */
  public function atribuirComponentesDigitaisAoDocumento($parNumIdDocumento, $parArrObjComponenteDigitalDTO)
    {
    if(!isset($parArrObjComponenteDigitalDTO)) {
        throw new InfraException('Par�metro parArrObjComponenteDigitalDTO n�o informado.');
    }
      $arrObjAnexoDTOParaCompactacao = array();
    foreach ($parArrObjComponenteDigitalDTO as $objComponenteDigital){
      foreach($this->arrAnexos as $key => $objAnexo){
        if(array_key_exists($objComponenteDigital->getStrHashConteudo(), $objAnexo) &&  $objAnexo['recebido'] == false){
          $arrObjAnexoDTOParaCompactacao[] = $objAnexo[$objComponenteDigital->getStrHashConteudo()];
          $this->arrAnexos[$key]['recebido'] = true;
          break;
        }
      }
    }

      // Verifica se este documento possui mais de um componente digital.
      // Caso possua, ser� necess�rio compactar todos os arquivos em ZIP para vincula��o ao documento no SEI que
      // permite apenas um arquivo por documento
      $objAnexoDTODocumento = null;
    if(count($arrObjAnexoDTOParaCompactacao) == 1){
        $objAnexoDTODocumento = $arrObjAnexoDTOParaCompactacao[0];
    }elseif (count($arrObjAnexoDTOParaCompactacao) > 1){
        $objAnexoDTODocumento = self::compactarAnexosDoDocumento($parNumIdDocumento, $arrObjAnexoDTOParaCompactacao);
    }else{
        throw new InfraException("Anexo do documento $parNumIdDocumento n�o pode ser localizado.");
    }

      //Transferir documentos validados para o reposit�rio final de arquivos
      $objAnexoDTODocumento->setDblIdProtocolo($parNumIdDocumento);

      //Realiza o cadastro do anexo
      self::cadastrarAnexoDoDocumento($objAnexoDTODocumento);

      //Registrar anexo relacionado com o componente digital
    foreach ($parArrObjComponenteDigitalDTO as $objComponenteDigitalRecebido){
        $this->atualizarAnexoDoComponenteDigital($objComponenteDigitalRecebido, $objAnexoDTODocumento);
    }

      return $objAnexoDTODocumento;
  }



    /**
     * Este m�todo recebe um id de documento e um array de anexos DTO, e � respons�vel por:
     * Buscar o array de documentos
     *
     * @param $parNumIdDocumento
     * @param $parArrAnexoDTO
     * @return array
     * @throws InfraException
     */
  protected function compactarAnexosDoDocumento($parNumIdDocumento, $parArrAnexoDTO)
    {
    try{
        ini_set('max_execution_time', '300');

        $objInfraException = new InfraException();

        /**
         * Transforma em array, o id do documento
         */
        $arrIdDocumentos = array($parNumIdDocumento);

        $objDocumentoDTO = new DocumentoDTO();
        $objDocumentoDTO->retDblIdDocumento();
        $objDocumentoDTO->retDblIdProcedimento();
        $objDocumentoDTO->retStrStaProtocoloProtocolo();
        $objDocumentoDTO->retStrNumero();
        $objDocumentoDTO->retStrNomeSerie();
        $objDocumentoDTO->retStrProtocoloDocumentoFormatado();
        $objDocumentoDTO->retStrProtocoloProcedimentoFormatado();
        $objDocumentoDTO->retStrStaDocumento();
        $objDocumentoDTO->retDblIdDocumentoEdoc();
        $objDocumentoDTO->setDblIdDocumento($arrIdDocumentos, InfraDTO::$OPER_IN);

        $objDocumentoRN = new DocumentoRN();
        $arrObjDocumentoDTO = $objDocumentoRN->listarRN0008($objDocumentoDTO);

      if (count($arrObjDocumentoDTO)==0){
        throw new InfraException('Nenhum documento informado.');
      }

        $contDocumentosDto = 0;
        $arrayRetornoObjAnexoDTO = array();
      foreach ($arrObjDocumentoDTO as $objDocumentoDTO){
          $contDocumentosDto++;
          $objAnexoRN = new AnexoRN();

          $strProtocoloDocumentoFormatado = $objDocumentoDTO->getStrProtocoloDocumentoFormatado();
          $strNomeArquivoCompactado = $objAnexoRN->gerarNomeArquivoTemporario();
          $strCaminhoCompletoArquivoZip = DIR_SEI_TEMP.'/'.$strNomeArquivoCompactado;

          $zipFile= new ZipArchive();
          $zipFile->open($strCaminhoCompletoArquivoZip, ZIPARCHIVE::CREATE);

          $arrObjDocumentoDTO = InfraArray::indexarArrInfraDTO($arrObjDocumentoDTO, 'IdDocumento');
          $numCasas=floor(log10(count($arrObjDocumentoDTO)))+1;
          $numSequencial = 0;
        foreach($arrIdDocumentos as $dblIdDocumento){
          $objDocumentoDTO = $arrObjDocumentoDTO[$dblIdDocumento];
          $strDocumento = '';
          if ($objDocumentoDTO->getStrStaProtocoloProtocolo() == ProtocoloRN::$TP_DOCUMENTO_RECEBIDO){
              $arrayAnexosExcluirFisicamente = array();
            foreach ($parArrAnexoDTO as $objAnexoDTO){
              $numSequencial++;

              if ($objAnexoDTO==null){
                    $objInfraException->adicionarValidacao('Documento '.$objDocumentoDTO->getStrProtocoloDocumentoFormatado() .' n�o encontrado.');
              }else{
                      /**
                       * Aqui ser� atribu�do um nome aos anexos
                       */
                      $ext = explode('.', $objAnexoDTO->getStrNome());
                      /**
                       * o c�digo abaixo foi comentado, pois com ele estavam sendo gerados os nomes que n�o refletiam os nomes reais dos arquivos.
                       */
                      $ext = strtolower($ext[count($ext)-1]);
                      $strNomeArquivo = $objAnexoDTO->getStrNome();

                      /**
                       * Aqui, o anexo ser� adicionado ao zip
                       */
                      $strLocalizacaoArquivo = DIR_SEI_TEMP.'/'. $objAnexoDTO->getNumIdAnexo() ;
                      //if ($zipFile->addFile($strLocalizacaoArquivo,'['.$numComponenteDigital.']-'.InfraUtil::formatarNomeArquivo($strNomeArquivo)) === false){
                if ($zipFile->addFile($strLocalizacaoArquivo, '['.$numSequencial.']-'.InfraUtil::formatarNomeArquivo($strNomeArquivo)) === false){
                          throw new InfraException('Erro adicionando arquivo externo ao zip.');
                }
                else{
                              /**
                               * Aqui quer dizer que o arquivo j� foi colocado dentro do zip.
                               * Vamos coloc�-lo em um array e depois utilizarmos este array para fazer as exclus�es.
                               */
                              array_push($arrayAnexosExcluirFisicamente, $strLocalizacaoArquivo);
                }
              }
            }
          }else{
              $objInfraException->adicionarValidacao('N�o foi poss�vel detectar o tipo do documento '.$objDocumentoDTO->getStrProtocoloDocumentoFormatado().'.');
          }
        }
          $objInfraException->lancarValidacoes();
        if ($zipFile->close() === false) {
            throw new InfraException('N�o foi poss�vel fechar arquivo zip.');
        }
          $objAnexoDTO = new AnexoDTO();
          $arrNomeArquivo = explode('/', $strCaminhoCompletoArquivoZip);
          $objAnexoDTO->setStrNome($arrNomeArquivo[count($arrNomeArquivo)-1]);
          $objAnexoDTO->setNumIdAnexo($strNomeArquivoCompactado);
          $objAnexoDTO->setDthInclusao(InfraData::getStrDataHoraAtual());
          $objAnexoDTO->setNumTamanho(filesize($strCaminhoCompletoArquivoZip));
          $objAnexoDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
          $objAnexoDTO->setStrNome($strNomeArquivoCompactado.'.zip');
          /**
           * Vamos varrer os arquivos que devem ser exclu�dos fisicamente da pasta tempor�ria e exclu�-los
           */
        foreach ($arrayAnexosExcluirFisicamente as $caminhoArquivoExcluirFisicamente){
            unlink($caminhoArquivoExcluirFisicamente);
        }
      }
        return $objAnexoDTO;
    }catch(Exception $e){
        throw new InfraException('Erro gerando zip.', $e);
    }
  }

    /**
     * @param $parObjComponenteDigitalDTO
     * @param $parObjAnexoDTO
     * @throws InfraException
     */
  private function atualizarAnexoDoComponenteDigital($parObjComponenteDigitalDTO, $parObjAnexoDTO)
    {
      $objComponenteDigitalDTO = new ComponenteDigitalDTO();
      $objComponenteDigitalDTO->setNumIdTramite($parObjComponenteDigitalDTO->getNumIdTramite());
      $objComponenteDigitalDTO->setStrNumeroRegistro($parObjComponenteDigitalDTO->getStrNumeroRegistro());
      $objComponenteDigitalDTO->setDblIdDocumento($parObjComponenteDigitalDTO->getDblIdDocumento());
      $objComponenteDigitalDTO->setDblIdProcedimento($parObjComponenteDigitalDTO->getDblIdProcedimento());
      //$objComponenteDigitalDTO->setNumOrdem($parObjComponenteDigitalDTO->getNumOrdem());
      $objComponenteDigitalDTO->setNumIdAnexo($parObjAnexoDTO->getNumIdAnexo());
      $objComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());
      $objComponenteDigitalDTO = $objComponenteDigitalBD->alterar($objComponenteDigitalDTO);
  }

    /**
     * @param $objComponenteDigital
     * @return AnexoDTO
     *
     * Pelo que entendi, aqui os arquivos s�o gerados na pasta tempor�ria.
     */
  public function copiarComponenteDigitalPastaTemporaria($parObjComponenteDigital, $parObjConteudo)
    {
    if(!isset($parObjComponenteDigital)){
        throw new InfraException("Componente Digital n�o informado");
    }
      $objAnexoRN = new AnexoRN();
      $strNomeArquivoUpload = $objAnexoRN->gerarNomeArquivoTemporario();
      $strConteudoCodificado = $parObjConteudo->conteudoDoComponenteDigital;

      $fp = fopen(DIR_SEI_TEMP.'/'.$strNomeArquivoUpload, 'w');
      fwrite($fp, $strConteudoCodificado);
      fclose($fp);

      //Atribui informa��es do arquivo anexo
      $objAnexoDTO = new AnexoDTO();
      $objAnexoDTO->setNumIdAnexo($strNomeArquivoUpload);
      $objAnexoDTO->setDthInclusao(InfraData::getStrDataHoraAtual());
      $objAnexoDTO->setNumTamanho(filesize(DIR_SEI_TEMP.'/'.$strNomeArquivoUpload));
      $objAnexoDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());
      $objAnexoDTO->setStrNome($parObjComponenteDigital->nome);
      return $objAnexoDTO;
  }

  public function validarIntegridadeDoComponenteDigital(AnexoDTO $objAnexoDTO, $strHashConteudo, $parNumIdentificacaoTramite, $parNumOrdemComponente)
    {
      $strHashInformado = $strHashConteudo;
      $strHashInformado = base64_decode($strHashInformado);

      //$objAnexoRN = new AnexoRN();
      $strCaminhoAnexo = DIR_SEI_TEMP.'/'.$objAnexoDTO->getNumIdAnexo();
      $strHashDoArquivo = hash_file("sha256", $strCaminhoAnexo, true);

    if(strcmp($strHashInformado, $strHashDoArquivo) != 0) {
        $strMensagem = "Hash do componente digital de ordem $parNumOrdemComponente n�o confere com o valor informado pelo remetente.";
        $this->objProcessoEletronicoRN->recusarTramite($parNumIdentificacaoTramite, $strMensagem, ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_CORROMPIDO);

        $strHashInformadoBase64 = base64_encode($strHashInformado);
        $strHashDoArquivoBase64 = base64_encode($strHashDoArquivo);
        $strDetalhes = "Hash do componente digital informado pelo PEN: $strHashInformadoBase64 \n";
        $strDetalhes .= "Hash do componente digital calculado pelo SEI: $strHashDoArquivoBase64 \n";
        throw new InfraException($strMensagem, null, $strDetalhes);
    }
  }

    /**
     * M�todo para cadastramento do anexo correspondente ao componente digital recebido
     * @param ComponenteDigitalDTO $parObjComponenteDigitalDTO
     * @param AnexoDTO $parObjAnexoDTO
     * @throws InfraException
     */
  public function cadastrarComponenteDigital(ComponenteDigitalDTO $parObjComponenteDigitalDTO, AnexoDTO $parObjAnexoDTO)
    {
      //Obter dados do documento
      $objDocumentoDTO = new DocumentoDTO();
      $objDocumentoDTO->retDblIdDocumento();
      $objDocumentoDTO->retDblIdProcedimento();
      $objDocumentoDTO->setDblIdDocumento($parObjComponenteDigitalDTO->getDblIdDocumento());

      $objDocumentoRN = new DocumentoRN();
      $objDocumentoDTO = $objDocumentoRN->consultarRN0005($objDocumentoDTO);

    if ($objDocumentoDTO==null){
      throw new InfraException("Registro n<E3>o encontrado.");
    }

      $objProtocoloDTO = new ProtocoloDTO();
      $objProtocoloDTO->retDblIdProtocolo();
      $objProtocoloDTO->retStrProtocoloFormatado();
      $objProtocoloDTO->setDblIdProtocolo($objDocumentoDTO->getDblIdDocumento());

      $objProtocoloRN = new ProtocoloRN();
      $objProtocoloDTO = $objProtocoloRN->consultarRN0186($objProtocoloDTO);

      // Complementa informa��es do componente digital
      $parObjAnexoDTO->setStrNome($parObjComponenteDigitalDTO->getStrNome());
      $arrStrNome = explode('.', $parObjComponenteDigitalDTO->getStrNome());
      $strProtocoloFormatado = current($arrStrNome);
      $objDocumentoDTO->setObjProtocoloDTO($objProtocoloDTO);
      $objProtocoloDTO->setArrObjAnexoDTO(array($parObjAnexoDTO));
      $objDocumentoRN->alterarRN0004($objDocumentoDTO);
  }

    /**
     * M�todo respons�vel por cadastrar o anexo correspondente aos componentes digitais recebidos pelo PEN
     * @param ComponenteDigitalDTO $parObjComponenteDigitalDTO
     * @param AnexoDTO $parObjAnexoDTO
     * @throws InfraException
     */
  public function cadastrarAnexoDoDocumento(AnexoDTO $parObjAnexoDTO)
    {
      $dblIdDocumento = $parObjAnexoDTO->getDblIdProtocolo();
      //Obter dados do documento
      $objDocumentoDTO = new DocumentoDTO();
      $objDocumentoDTO->retDblIdDocumento();
      $objDocumentoDTO->retDblIdProcedimento();
      $objDocumentoDTO->setDblIdDocumento($dblIdDocumento);

      $objDocumentoRN = new DocumentoRN();
      $objDocumentoDTO = $objDocumentoRN->consultarRN0005($objDocumentoDTO);

    if ($objDocumentoDTO == null){
        throw new InfraException("Documento (id: $dblIdDocumento) n�o pode ser localizado.");
    }

      $objProtocoloDTO = new ProtocoloDTO();
      $objProtocoloDTO->retDblIdProtocolo();
      $objProtocoloDTO->retStrProtocoloFormatado();
      $objProtocoloDTO->setDblIdProtocolo($objDocumentoDTO->getDblIdDocumento());

      $objProtocoloRN = new ProtocoloRN();
      $objProtocoloDTO = $objProtocoloRN->consultarRN0186($objProtocoloDTO);

      //Complementa informa��es do componente digital
      $nomeArquivoZip = $parObjAnexoDTO->getStrNome();
      $parObjAnexoDTO->setStrNome($nomeArquivoZip);

      $objDocumentoDTO->setObjProtocoloDTO($objProtocoloDTO);
      $objProtocoloDTO->setArrObjAnexoDTO(array($parObjAnexoDTO));

      $objDocumentoRN->alterarRN0004($objDocumentoDTO);

  }
}
