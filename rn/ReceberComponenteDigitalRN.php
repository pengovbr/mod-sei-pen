<?php
require_once dirname(__FILE__) . '/../../../SEI.php';

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
            throw new InfraException('Parâmetro $parObjComponenteDigitalDTO não informado.');
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
            throw new InfraException('Anexo '.$parObjComponenteDigitalDTO->getStrHashConteudo().' não encontrado '.var_export($this->arrAnexos, true));
        }

        //Validar o hash do documento recebido com os dados informados pelo remetente
        //$this->validarIntegridadeDoComponenteDigital($objAnexoDTO, $parObjComponenteDigitalDTO);

        //Transferir documentos validados para o repositório final de arquivos
        $this->cadastrarComponenteDigital($parObjComponenteDigitalDTO, $objAnexoDTO);

        //Registrar anexo relacionado com o componente digital
        $this->registrarAnexoDoComponenteDigital($parObjComponenteDigitalDTO, $objAnexoDTO);
    }

    private function registrarAnexoDoComponenteDigital($parObjComponenteDigitalDTO, $parObjAnexoDTO)
    {
        $objComponenteDigitalDTO = new ComponenteDigitalDTO();
        $objComponenteDigitalDTO->setNumIdTramite($parObjComponenteDigitalDTO->getNumIdTramite());
        $objComponenteDigitalDTO->setStrNumeroRegistro($parObjComponenteDigitalDTO->getStrNumeroRegistro());
        $objComponenteDigitalDTO->setDblIdDocumento($parObjComponenteDigitalDTO->getDblIdDocumento());
        $objComponenteDigitalDTO->setNumIdAnexo($parObjAnexoDTO->getNumIdAnexo());
        $objComponenteDigitalBD = new ComponenteDigitalBD($this->getObjInfraIBanco());
        $objComponenteDigitalDTO = $objComponenteDigitalBD->alterar($objComponenteDigitalDTO);
    }

    public function copiarComponenteDigitalPastaTemporaria($objComponenteDigital)
    {
        $objAnexoRN = new AnexoRN();
        $strNomeArquivoUpload = $objAnexoRN->gerarNomeArquivoTemporario();
        $strConteudoCodificado = $objComponenteDigital->conteudoDoComponenteDigital;
        $strNome = $objComponenteDigital->nome;

        $fp = fopen(DIR_SEI_TEMP.'/'.$strNomeArquivoUpload,'w');
        fwrite($fp,$strConteudoCodificado);
        fclose($fp);

        //Atribui informações do arquivo anexo
        $objAnexoDTO = new AnexoDTO();
        $objAnexoDTO->setNumIdAnexo($strNomeArquivoUpload);
        $objAnexoDTO->setDthInclusao(InfraData::getStrDataHoraAtual());
        $objAnexoDTO->setNumTamanho(filesize(DIR_SEI_TEMP.'/'.$strNomeArquivoUpload));
        $objAnexoDTO->setNumIdUsuario(SessaoSEI::getInstance()->getNumIdUsuario());

        return $objAnexoDTO;
    }

    public function validarIntegridadeDoComponenteDigital(AnexoDTO $objAnexoDTO, $strHashConteudo, $parNumIdentificacaoTramite)
    {
        $strHashInformado = $strHashConteudo;
        $strHashInformado = base64_decode($strHashInformado);

        $objAnexoRN = new AnexoRN();
        $strCaminhoAnexo = DIR_SEI_TEMP.'/'.$objAnexoDTO->getNumIdAnexo();
        $strHashDoArquivo = hash_file("sha256", $strCaminhoAnexo, true);

        if(strcmp($strHashInformado, $strHashDoArquivo) != 0) {
            $strMensagem = "Hash do componente digital não confere com o valor informado pelo remetente.";
            $this->objProcessoEletronicoRN->recusarTramite($parNumIdentificacaoTramite, $strMensagem, ProcessoEletronicoRN::MTV_RCSR_TRAM_CD_CORROMPIDO);

            $strHashInformadoBase64 = base64_encode($strHashInformado);
            $strHashDoArquivoBase64 = base64_encode($strHashDoArquivo);
            $strDetalhes = "Hash do componente digital informado pelo PEN: $strHashInformadoBase64 \n";
            $strDetalhes .= "Hash do componente digital calculado pelo SEI: $strHashDoArquivoBase64 \n";
            throw new InfraException($strMensagem, null, $strDetalhes);
        }
    }

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
          throw new InfraException("Documento não pode ser localizado (".$parObjComponenteDigitalDTO->getDblIdDocumento().")");
        }

        $objProtocoloDTO = new ProtocoloDTO();
        $objProtocoloDTO->retDblIdProtocolo();
        $objProtocoloDTO->retStrProtocoloFormatado();
        $objProtocoloDTO->setDblIdProtocolo($objDocumentoDTO->getDblIdDocumento());

        $objProtocoloRN = new ProtocoloRN();
        $objProtocoloDTO = $objProtocoloRN->consultarRN0186($objProtocoloDTO);

        //Complementa informações do componente digital
        $parObjAnexoDTO->setStrNome($parObjComponenteDigitalDTO->getStrNome());

        $arrStrNome = explode('.',$parObjComponenteDigitalDTO->getStrNome());
        $strProtocoloFormatado = current($arrStrNome);

        $objDocumentoDTO->setObjProtocoloDTO($objProtocoloDTO);
        $objProtocoloDTO->setArrObjAnexoDTO(array($parObjAnexoDTO));
        $objDocumentoDTO = $objDocumentoRN->alterarRN0004($objDocumentoDTO);

        // @join_tec US029 (#3790)
        /*$objObservacaoDTO = new ObservacaoDTO();
        $objObservacaoDTO->setDblIdProtocolo($objProtocoloDTO->getDblIdProtocolo());
        $objObservacaoDTO->setStrDescricao(sprintf('Número SEI do Documento na Origem: %s', $strProtocoloFormatado));
        $objObservacaoDTO->setNumIdUnidade(SessaoSEI::getInstance()->getNumIdUnidadeAtual());

        $objObservacaoBD = new ObservacaoRN();
        $objObservacaoBD->cadastrarRN0222($objObservacaoDTO);*/
    }
}
