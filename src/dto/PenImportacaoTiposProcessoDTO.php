<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Classe reaponsável por manipulação
 */
class PenImportacaoTiposProcessoDTO extends InfraDTO
{

  public function getStrNomeTabela()
    {
      return 'md_pen_map_tipo_processo';
  }

  public function getStrNomeSequenciaNativa()
    {
      return 'md_pen_seq_orgao_externo';
  }

  public function montar()
    {

      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'Id', 'Id');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdMapOrgao', 'id_map_orgao');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdTipoProcessoOrigem', 'id_tipo_processo_origem');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdTipoProcessoDestino', 'id_tipo_processo_destino');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'NomeTipoProcesso', 'nome_tipo_processo');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Ativo', 'sin_ativo');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUnidade', 'id_unidade');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DTH, 'Criacao', 'dth_criacao');

      $this->configurarPK('Id', InfraDTO::$TIPO_PK_NATIVA);

      $this->configurarFK('IdUnidade', 'unidade', 'id_unidade');
  }
}