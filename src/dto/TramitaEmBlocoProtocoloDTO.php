<?
require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Data Transfer Object de parâmetros do módulo PEN
 */
class TramitaEmBlocoProtocoloDTO extends InfraDTO {

  public function getStrNomeTabela() {
  	 return 'md_pen_tramita_em_bloco_protocolo';
  }

  public function montar() {

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM,
                                   'Id',
                                   'id');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL,
                                   'IdProtocolo',
                                   'id_protocolo');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM,
                                   'IdTramitaEmBloco',
                                   'id_tramita_em_bloco');
                                   
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR,
                                   'Anotacao',
                                   'anotacao');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM,
                                   'Sequencia',
                                   'sequencia');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR,
                                   'IdxRelBlocoProtocolo',
                                   'idx_rel_bloco_protocolo');

    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_DBL,
                                   'IdProtocoloProtocolo',
                                   'p1.id_protocolo',
                                   'protocolo p1');
                                   
    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR,
                                   'ProtocoloFormatadoProtocolo',
                                   'p1.protocolo_formatado',
                                   'protocolo p1');

    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR,
                                   'StaProtocoloProtocolo',
                                   'p1.sta_protocolo',
                                   'protocolo p1');

    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR,
                                  'StaNivelAcessoGlobalProtocolo',
                                  'p1.sta_nivel_acesso_global',
                                  'protocolo p1');

    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM,
                                   'IdUnidadeBloco',
                                   'tb1.id_unidade',
                                   'md_pen_tramita_em_bloco tb1');

    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM,
                                   'IdUsuario',
                                   'tb1.id_usuario',
                                   'md_pen_tramita_em_bloco tb1');

    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR,
                                   'StaTipoBloco',
                                   'sta_tipo',
                                   'bloco');

    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR,
                                   'StaEstadoBloco',
                                   'sta_estado',
                                   'md_pen_tramita_em_bloco tb1');
                              
    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'StaNomeUsuario', 'nome', 'usuario us');

    $this->adicionarAtributo(InfraDTO::$PREFIXO_OBJ,'ProtocoloDTO');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_OBJ,'PenLoteProcedimentoDTO');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_ARR,'ObjAssinaturaDTO');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_STR,'PalavrasPesquisa');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_STR,'SinAberto');


    $this->configurarPK('Id', InfraDTO::$TIPO_PK_NATIVA);
    $this->configurarPK('IdProtocolo',InfraDTO::$TIPO_PK_INFORMADO);
    $this->configurarPK('IdTramitaEmBloco',InfraDTO::$TIPO_PK_INFORMADO);
    
    $this->configurarFK('IdProtocolo', 'protocolo p1', 'p1.id_protocolo');
		$this->configurarFK('IdTramitaEmBloco', 'md_pen_tramita_em_bloco tb1', 'tb1.id');
    $this->configurarFK('IdUsuario', 'usuario us', 'us.id_usuario');
  }
}
?>