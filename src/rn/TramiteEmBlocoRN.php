<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Description of TramiteEmBloco
 *
 * Tramitar em bloco
 */
class TramiteEmBlocoRN extends InfraRN {

    public static $TB_ASSINATURA = 'A';
    public static $TB_REUNIAO = 'R';
    public static $TB_INTERNO = 'I';

    public static $TE_ABERTO = 'A';
    public static $TE_DISPONIBILIZADO = 'D';
    public static $TE_RETORNADO = 'R';
    public static $TE_CONCLUIDO = 'C';
    public static $TE_RECEBIDO = 'B';

    public static $TA_TODAS = 'T';
    public static $TA_MINHAS = 'M';

    /**
     * Inicializa o obj do banco da Infra
     * @return obj
     */
    protected function inicializarObjInfraIBanco(){
        return BancoSEI::getInstance();
    }

    public function getNumMaxTamanhoDescricao(){
        return 250;
    }

    private function validarStrStaTipo(TramiteEmBlocoDTO $objTramiteEmBlocoDTO, InfraException $objInfraException){
        if (InfraString::isBolVazia($objTramiteEmBlocoDTO->getStrStaTipo())){
            $objInfraException->adicionarValidacao('Tipo n�o informado.');
        }else{
            if (!in_array($objTramiteEmBlocoDTO->getStrStaTipo(), InfraArray::converterArrInfraDTO($this->listarValoresTipo(),'StaTipo'))){
                $objInfraException->adicionarValidacao('Tipo inv�lido.');
            }
        }
    }

    private function validarNumIdUsuario(TramiteEmBlocoDTO $objTramiteEmBlocoDTO, InfraException $objInfraException) {
        if (InfraString::isBolVazia($objTramiteEmBlocoDTO->getNumIdUsuario())){
            $objInfraException->adicionarValidacao('Usu�rio n�o informado.');
        }
    }

    private function validarStrDescricao(TramiteEmBlocoDTO $objTramiteEmBlocoDTO, InfraException $objInfraException){
        if (InfraString::isBolVazia($objTramiteEmBlocoDTO->getStrDescricao())) {

            $objTramiteEmBlocoDTO->setStrDescricao(null);

        } else {

            $objTramiteEmBlocoDTO->setStrDescricao(trim($objTramiteEmBlocoDTO->getStrDescricao()));
            $objTramiteEmBlocoDTO->setStrDescricao(InfraUtil::filtrarISO88591($objTramiteEmBlocoDTO->getStrDescricao()));

            if (strlen($objTramiteEmBlocoDTO->getStrDescricao()) > $this->getNumMaxTamanhoDescricao()){
                $objInfraException->adicionarValidacao('Descri��o possui tamanho superior a ' .$this->getNumMaxTamanhoDescricao(). ' caracteres.');
            }

        }
    }

    private function validarStrIdxBloco(TramiteEmBlocoDTO $objTramiteEmBlocoDTO, InfraException $objInfraException){
        if (InfraString::isBolVazia($objTramiteEmBlocoDTO->getStrIdxBloco())){

            $objTramiteEmBlocoDTO->setStrIdxBloco(null);

        }else{

            $objTramiteEmBlocoDTO->setStrIdxBloco(trim($objTramiteEmBlocoDTO->getStrIdxBloco()));

            if (strlen($objTramiteEmBlocoDTO->getStrIdxBloco()) > 500){
                $objInfraException->adicionarValidacao('Indexa��o possui tamanho superior a 500 caracteres.');
            }

        }
    }

    private function validarStrStaEstado(TramiteEmBlocoDTO $objTramiteEmBlocoDTO, InfraException $objInfraException){
        if (InfraString::isBolVazia($objTramiteEmBlocoDTO->getStrStaEstado())) {

            $objInfraException->adicionarValidacao('Estado n�o informado.');

        } else {

            if (!in_array($objTramiteEmBlocoDTO->getStrStaEstado(), InfraArray::converterArrInfraDTO($this->listarValoresEstado(), 'StaEstado'))){
                $objInfraException->adicionarValidacao('Estado inv�lido.');
            }

        }
    }

    public function listarValoresTipo(){
        try {

            $arrObjTipoDTO = array();

            $objTipoDTO = new TipoDTO();
            $objTipoDTO->setStrStaTipo(self::$TB_INTERNO);
            $objTipoDTO->setStrDescricao('Interno');
            $arrObjTipoDTO[] = $objTipoDTO;


            return $arrObjTipoDTO;

        }catch(Exception $e){
            throw new InfraException('Erro listando valores de Tipo.', $e);
        }
    }

    public function listarValoresEstado(){
        try {

            $objArrEstadoBlocoDTO = array();

            $objEstadoBlocoDTO = new EstadoBlocoDTO();
            $objEstadoBlocoDTO->setStrStaEstado(self::$TE_ABERTO);
            $objEstadoBlocoDTO->setStrDescricao('Aberto');
            $objArrEstadoBlocoDTO[] = $objEstadoBlocoDTO;

            $objEstadoBlocoDTO = new EstadoBlocoDTO();
            $objEstadoBlocoDTO->setStrStaEstado(self::$TE_DISPONIBILIZADO);
            $objEstadoBlocoDTO->setStrDescricao('Em Processamento');
            $objArrEstadoBlocoDTO[] = $objEstadoBlocoDTO;

            $objEstadoBlocoDTO = new EstadoBlocoDTO();
            $objEstadoBlocoDTO->setStrStaEstado(self::$TE_CONCLUIDO);
            $objEstadoBlocoDTO->setStrDescricao('Conclu�do');
            $objArrEstadoBlocoDTO[] = $objEstadoBlocoDTO;

            return $objArrEstadoBlocoDTO;

        } catch(Exception $e) {
            throw new InfraException('Erro listando valores de Estado.',$e);
        }
    }

    public function retornarEstadoDescricao($estado){
        try {
            $arg = [
               self::$TE_ABERTO => 'Aberto',
               self::$TE_DISPONIBILIZADO = 'Em Processamento',
               self::$TE_RETORNADO = 'Retornado',
            ];

            return $arg[$estado];

        } catch(Exception $e) {
            throw new InfraException('Estado n�o encontrado.',$e);
        }
    }

    protected function listarConectado(TramiteEmBlocoDTO $objTramiteEmBlocoDTO) {
        try {
            //Valida Permissao
            SessaoSEI::getInstance()->validarAuditarPermissao('md_pen_tramita_em_bloco',__METHOD__,$objTramiteEmBlocoDTO);


            if ($objTramiteEmBlocoDTO->isRetStrTipoDescricao()) {
                $objTramiteEmBlocoDTO->retStrStaTipo();
            }

            $objTramiteEmBlocoBD = new TramiteEmBlocoBD($this->getObjInfraIBanco());
            $ret = $objTramiteEmBlocoBD->listar($objTramiteEmBlocoDTO);

            if ($objTramiteEmBlocoDTO->isRetStrTipoDescricao()) {
                $arrObjTipoDTO = $this->listarValoresTipo();
                foreach ($ret as $dto) {
                    foreach ($arrObjTipoDTO as $objTipoDTO) {
                        if ($dto->getStrStaTipo() == $objTipoDTO->getStrStaTipo()){
                            $dto->setStrTipoDescricao($objTipoDTO->getStrDescricao());
                            break;
                        }
                    }
                }
            }

            //Auditoria

            return $ret;

        } catch(Exception $e) {
            throw new InfraException('Erro listando Tramite em Blocos.',$e);
        }
    }

    protected function montarIndexacaoControlado(TramiteEmBlocoDTO $obTramiteEmBlocoDTO){
        try{

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

            foreach($arrObjTramiteEmBlocoDTO as $dto) {

                $objTramiteEmBlocoDTOIdx->setNumId($dto->getNumId());
                $objTramiteEmBlocoDTOIdx->setStrIdxBloco(InfraString::prepararIndexacao($dto->getNumId().' '.$dto->getStrDescricao()));

                $this->validarStrIdxBloco($objTramiteEmBlocoDTOIdx, $objInfraException);
                $objInfraException->lancarValidacoes();

                $objTramiteEmBlocoBD->alterar($objTramiteEmBlocoDTOIdx);
            }

        } catch(Exception $e) {
            throw new InfraException('Erro montando indexa��o de bloco.',$e);
        }
    }

    protected function cadastrarControlado(TramiteEmBlocoDTO $objTramiteEmBlocoDTO) {
        try {

            //Valida Permissao
//           / SessaoSEI::getInstance()->validarAuditarPermissao('pen_tramite_em_bloco_cadastrar',__METHOD__,$objTramiteEmBlocoDTO);

            //Regras de Negocio
            $objInfraException = new InfraException();


            $this->validarStrStaTipo($objTramiteEmBlocoDTO, $objInfraException);
            $this->validarNumIdUsuario($objTramiteEmBlocoDTO, $objInfraException);
            $this->validarStrDescricao($objTramiteEmBlocoDTO, $objInfraException);
            $this->validarStrIdxBloco($objTramiteEmBlocoDTO, $objInfraException);
            $this->validarStrStaEstado($objTramiteEmBlocoDTO, $objInfraException);


            $objInfraException->lancarValidacoes();

            $objTramiteEmBlocoBD = new TramiteEmBlocoBD($this->getObjInfraIBanco());
            $ret = $objTramiteEmBlocoBD->cadastrar($objTramiteEmBlocoDTO);

            $this->montarIndexacao($ret);

            return $ret;

        } catch (Exception $e) {
            throw new InfraException('Erro cadastrando Bloco.',$e);
        }
    }

    protected function consultarConectado(TramiteEmBlocoDTO $objTramiteEmBlocoDTO){
        try {

            //Valida Permissao
            SessaoSEI::getInstance()->validarAuditarPermissao('pen_tramite_em_bloco_consultar',__METHOD__,$objTramiteEmBlocoDTO);

            if ($objTramiteEmBlocoDTO->isRetStrTipoDescricao()) {
                $objTramiteEmBlocoDTO->retStrStaTipo();
            }

            $objTramiteEmBlocoBD = new TramiteEmBlocoBD($this->getObjInfraIBanco());
            $ret = $objTramiteEmBlocoBD->consultar($objTramiteEmBlocoDTO);

            if ($ret != null){
                if ($objTramiteEmBlocoDTO->isRetStrTipoDescricao()) {
                    $arrObjTipoDTO = $this->listarValoresTipo();
                    foreach ($arrObjTipoDTO as $objTipoDTO) {
                        if ($ret->getStrStaTipo() == $objTipoDTO->getStrStaTipo()){
                            $ret->setStrTipoDescricao($objTipoDTO->getStrDescricao());
                            break;
                        }
                    }
                }
            }
            //Auditoria

            return $ret;
        } catch (Exception $e) {
            throw new InfraException('Erro consultando Bloco.',$e);
        }
    }

    protected function alterarControlado(TramiteEmBlocoDTO $objTramiteEmBlocoDTO){
        try {

            //Valida Permissao
            SessaoSEI::getInstance()->validarAuditarPermissao('pen_tramite_em_bloco_alterar',__METHOD__,$objTramiteEmBlocoDTO);

            //Regras de Negocio
            $objInfraException = new InfraException();

            $dto = new TramiteEmBlocoDTO();
            $dto->retStrStaTipo();
            $dto->setNumId($objTramiteEmBlocoDTO->getNumId());

            $dto = $this->consultar($dto);

            if ($objTramiteEmBlocoDTO->isSetStrStaTipo() && $objTramiteEmBlocoDTO->getStrStaTipo()!=$dto->getStrStaTipo()){
                $objInfraException->lancarValidacao('N�o � poss�vel alterar o tipo do bloco.');
            }

            $objTramiteEmBlocoDTO->setStrStaTipo($dto->getStrStaTipo());

            if ($objTramiteEmBlocoDTO->isSetStrStaTipo()){
                $this->validarStrStaTipo($objTramiteEmBlocoDTO, $objInfraException);
            }
            if ($objTramiteEmBlocoDTO->isSetNumIdUsuario()){
                $this->validarNumIdUsuario($objTramiteEmBlocoDTO, $objInfraException);
            }
            if ($objTramiteEmBlocoDTO->isSetStrDescricao()){
                $this->validarStrDescricao($objTramiteEmBlocoDTO, $objInfraException);
            }
            if ($objTramiteEmBlocoDTO->isSetStrIdxBloco()){
                $this->validarStrIdxBloco($objTramiteEmBlocoDTO, $objInfraException);
            }
            if ($objTramiteEmBlocoDTO->isSetStrStaEstado()){
                $this->validarStrStaEstado($objTramiteEmBlocoDTO, $objInfraException);
            }

            $objInfraException->lancarValidacoes();

            $objTramiteEmBlocoBD = new TramiteEmBlocoBD($this->getObjInfraIBanco());
            $objTramiteEmBlocoBD->alterar($objTramiteEmBlocoDTO);

            $this->montarIndexacao($objTramiteEmBlocoDTO);

            //Auditoria

        } catch (Exception $e){
            throw new InfraException('Erro alterando Bloco.',$e);
        }
    }

    /**
     * M�todo utilizado para exclus�o de dados.
     * @param array $arrayObjDTO
     * @return array
     * @throws InfraException
     */
    protected function excluirControlado(array $arrayObjDTO)
    {
        try {
            //Valida Permissao
            SessaoSEI::getInstance()->validarAuditarPermissao('md_pen_tramita_em_bloco_excluir',__METHOD__,$arrayObjDTO);

            $arrayExcluido = array();
            foreach ($arrayObjDTO as $objDTO) {
                $objBD = new TramiteEmBlocoBD(BancoSEI::getInstance());
                $arrayExcluido[] = $objBD->excluir($objDTO);
            }
            return $arrayExcluido;
        } catch (Exception $e) {
            throw new InfraException('Erro excluindo Bloco.', $e);
        }
    }

    protected function cancelarControlado(array $blocoIds)
    {
        try {
            $objBloco = new TramitaEmBlocoProtocoloDTO();
            foreach ($blocoIds as $blocoId) {
                $objBloco->setNumIdTramitaEmBloco($blocoId);
                $objBloco->retDblIdProtocolo();
                $tramiteEmBlocoProtocoloRn = new TramitaEmBlocoProtocoloRN();
                $protocoloIds = $tramiteEmBlocoProtocoloRn->listar($objBloco);
                $protocoloRn = new ExpedirProcedimentoRN();
                foreach ($protocoloIds as $protocoloId) {
                    $protocoloRn->cancelarTramite($protocoloId->getDblIdProtocolo());
                }
            }
        } catch (Exception $e) {
            throw new InfraException('Erro cancelando Bloco.', $e);
        }
    }
}
