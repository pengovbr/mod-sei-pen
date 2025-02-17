<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Data Transfer Object de parâmetros do módulo PEN
 */
class PenBlocoProcessoDTO extends InfraDTO
{

  public function getStrNomeTabela()
    {
      return 'md_pen_bloco_processo';
  }

  public function getStrNomeSequenciaNativa()
    {
      return 'md_pen_seq_bloco_processo';
  }

  public function montar()
    {
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdBlocoProcesso', 'id_bloco_processo');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdRepositorioDestino', 'id_repositorio_destino');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'RepositorioDestino', 'str_repositorio_destino');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdRepositorioOrigem', 'id_repositorio_origem');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUnidadeDestino', 'id_unidade_destino');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'UnidadeDestino', 'str_unidade_destino');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUnidadeOrigem', 'id_unidade_origem');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUsuario', 'id_usuario');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUnidade', 'id_unidade');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DTH, 'Registro', 'dth_registro');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DTH, 'Atualizado', 'dth_atualizado');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DTH, 'Envio', 'dth_envio');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdBloco', 'id_bloco');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdProtocolo', 'id_protocolo');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'Sequencia', 'sequencia');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdAndamento', 'id_andamento');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdAtividade', 'id_atividade_expedicao');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'Tentativas', 'tentativas');

      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_DBL, 'IdProtocoloProtocolo', 'p1.id_protocolo', 'protocolo p1');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'ProtocoloFormatadoProtocolo', 'p1.protocolo_formatado', 'protocolo p1');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'StaProtocoloProtocolo', 'p1.sta_protocolo', 'protocolo p1');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'StaNivelAcessoGlobalProtocolo', 'p1.sta_nivel_acesso_global', 'protocolo p1');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'IdUnidadeBloco', 'tb1.id_unidade', 'md_pen_bloco tb1');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'IdUsuario', 'tb1.id_usuario', 'md_pen_bloco tb1');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'StaTipoBloco', 'sta_tipo', 'md_pen_bloco tb1');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'StaEstadoBloco', 'tb1.sta_estado', 'md_pen_bloco tb1');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'IdProcedimento', 'pe.id_procedimento', 'md_pen_processo_eletronico pe');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'NumeroRegistro', 'pe.numero_registro', 'md_pen_processo_eletronico pe');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'IdTramite', 'pt.id_tramite', 'md_pen_tramite pt');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'StaEstadoProtocolo', 'p1.sta_estado', 'protocolo p1');

      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'NomeUsuario', 'nome', 'usuario');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'NomeUnidade', 'nome', 'unidade');

      $this->adicionarAtributo(InfraDTO::$PREFIXO_ARR, 'ListaProcedimento');

      $this->adicionarAtributo(InfraDTO::$PREFIXO_OBJ, 'TramiteDTO');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_OBJ, 'AtividadeDTO');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_OBJ, 'ProtocoloDTO');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_OBJ, 'PenBlocoProcedimentoDTO');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'PalavrasPesquisa');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'SinAberto');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM, 'StaIdTarefa');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'SinObteveRecusa');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'IdxRelBlocoProtocolo');

      $this->configurarPK('IdBlocoProcesso', InfraDTO::$TIPO_PK_NATIVA);
      $this->configurarPK('IdProtocolo', InfraDTO::$TIPO_PK_INFORMADO);
      $this->configurarPK('IdBloco', InfraDTO::$TIPO_PK_INFORMADO);

      $this->configurarFK('IdUsuario', 'usuario', 'id_usuario', InfraDTO::$TIPO_FK_OPCIONAL);
      $this->configurarFK('IdUnidade', 'unidade', 'id_unidade', InfraDTO::$TIPO_FK_OPCIONAL);

      $this->configurarFK('IdProtocolo', 'protocolo p1', 'p1.id_protocolo');
      $this->configurarFK('IdBloco', 'md_pen_bloco tb1', 'tb1.id');
      $this->configurarFK('IdProtocolo', 'md_pen_processo_eletronico pe', 'pe.id_procedimento', InfraDTO::$TIPO_FK_OPCIONAL);
      $this->configurarFK('NumeroRegistro', 'md_pen_tramite pt', 'pt.numero_registro', InfraDTO::$TIPO_FK_OPCIONAL);
  }
}
