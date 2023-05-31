<?
/**
* FABRICA FIRST DECISION
*
* 24/05/2023
*
* Versão do Gerador de Código: 3.4.0
*
* Versão no CVS: $Id$
*/

require_once dirname(__FILE__).'/../SEI.php';

class TramiteEmBlocoDTO extends InfraDTO {

  public function getStrNomeTabela() {
  	 return 'md_pen_tramita_em_bloco';
  }

  public function montar() {

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM,
                                   'Id',
                                   'id');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM,
                                   'IdUnidade',
                                   'id_unidade');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM,
                                   'IdUsuario',
                                   'id_usuario');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR,
                                   'Descricao',
                                   'descricao');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR,
                                   'IdxBloco',
                                   'idx_bloco');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR,
                                   'StaTipo',
                                   'sta_tipo');
                                   
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR,
                                   'StaEstado',
                                   'sta_estado');
                                   
    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR,
					                                   'SiglaUnidade',
					                                   'uc.sigla',
					                                   'unidade uc');

    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR,
					                                   'DescricaoUnidade',
					                                   'uc.descricao',
					                                   'unidade uc');

    // $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM,
    //                                           'IdGrupoBlocoRelBlocoUnidade',
    //                                           'id_grupo_bloco',
    //                                           'rel_bloco_unidade');

    // $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM,
	// 				                                   'IdUnidadeRelBlocoUnidade',
	// 				                                   'id_unidade',
	// 				                                   'rel_bloco_unidade');

    // $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR,
    //                                           'SinPrioridadeRelBlocoUnidade',
    //                                           'sin_prioridade',
    //                                           'rel_bloco_unidade');

    // $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR,
    //                                           'SinRevisaoRelBlocoUnidade',
    //                                           'sin_revisao',
    //                                           'rel_bloco_unidade');

    // $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR,
    //                                           'SinComentarioRelBlocoUnidade',
    //                                           'sin_comentario',
    //                                           'rel_bloco_unidade');

    // $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM,
    //                                           'IdUsuarioAtribuicaoRelBlocoUnidade',
    //                                           'id_usuario_atribuicao',
    //                                           'rel_bloco_unidade');

    // $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR,
    //                                           'SinRetornadoRelBlocoUnidade',
    //                                           'sin_retornado',
    //                                           'rel_bloco_unidade');

		$this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR,
					                                   'SiglaUnidadeRelBlocoUnidade',
					                                   'ud.sigla',
					                                   'unidade ud');

		$this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR,
					                                   'DescricaoUnidadeRelBlocoUnidade',
					                                   'ud.descricao',
					                                   'unidade ud');
					                                   
    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR,
                                              'SiglaUsuario',
                                              'sigla',
                                              'usuario');

    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR,
                                              'NomeUsuario',
                                              'nome',
                                              'usuario');

	// 	$this->adicionarAtributo(InfraDTO::$PREFIXO_STR,'TipoDescricao');
	// 	$this->adicionarAtributo(InfraDTO::$PREFIXO_STR,'StaEstadoDescricao');
	// 	//$this->adicionarAtributo(InfraDTO::$PREFIXO_STR,'SinVazio');
    // $this->adicionarAtributo(InfraDTO::$PREFIXO_STR,'PalavrasPesquisa');
    // $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM,'Documentos');
    // $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM,'Assinados');

    // $this->adicionarAtributo(InfraDTO::$PREFIXO_OBJ,'RelBlocoUnidadeDTO');
    // $this->adicionarAtributo(InfraDTO::$PREFIXO_ARR,'ObjRelBlocoUnidadeDTO');
    // $this->adicionarAtributo(InfraDTO::$PREFIXO_ARR,'ObjRelBlocoProtocoloDTO');
    // $this->adicionarAtributo(InfraDTO::$PREFIXO_STR,'StaTipoAtribuicao');

    $this->configurarPK('Id', InfraDTO::$TIPO_PK_NATIVA );
    
    // $this->configurarFK('Id', 'rel_bloco_unidade', 'id');
    $this->configurarFK('IdUsuario', 'usuario', 'id_usuario');
    $this->configurarFK('IdUnidade', 'unidade uc', 'uc.id_unidade');
    // $this->configurarFK('IdUnidadeRelBlocoUnidade', 'unidade ud', 'ud.id_unidade');
  }
}
?>