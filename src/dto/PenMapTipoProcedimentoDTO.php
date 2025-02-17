<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Classe reaponsável por manipulação
 */
class PenMapTipoProcedimentoDTO extends InfraDTO
{

  public function getStrNomeTabela()
    {
      return 'md_pen_map_tipo_processo';
  }

  public function getStrNomeSequenciaNativa()
    {
      return 'md_pen_seq_map_tp_procedimento';
  }

  public function montar()
    {

      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'Id', 'id');

      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdMapOrgao', 'id_map_orgao');
      $this->configurarPK('Id', InfraDTO::$TIPO_PK_NATIVA);

      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdTipoProcessoOrigem', 'id_tipo_processo_origem');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdTipoProcessoDestino', 'id_tipo_processo_destino');

      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'NomeTipoProcesso', 'nome_tipo_processo');

      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DTH, 'Registro', 'dth_criacao');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Ativo', 'sin_ativo');

      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'OrgaoOrigem', 'str_orgao_origem', 'md_pen_orgao_externo');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'OrgaoDestino', 'str_orgao_destino', 'md_pen_orgao_externo');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'NomeTipoProcedimento', 'nome', 'tipo_procedimento');


      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUnidade', 'id_unidade');
      $this->configurarPK('Id', InfraDTO::$TIPO_PK_NATIVA);
      $this->configurarFK('IdUnidade', 'unidade', 'id_unidade');
      $this->configurarFK('IdMapOrgao', 'md_pen_orgao_externo', 'id', InfraDTO::$TIPO_FK_OBRIGATORIA);
      $this->configurarFK('IdTipoProcessoDestino', 'tipo_procedimento', 'id_tipo_procedimento', InfraDTO::$TIPO_FK_OPCIONAL);
  }
}
