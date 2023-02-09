<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Data Transfer Object de parâmetros do módulo PEN
 *
 *
 */
class PenExpedirLoteDTO extends InfraDTO
{

  public function getStrNomeTabela()
    {
      return 'md_pen_expedir_lote';
  }

  public function getStrNomeSequenciaNativa() {
      return 'md_pen_seq_expedir_lote';
  }    

  public function montar()
    {
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdLote', 'id_lote');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdRepositorioDestino', 'id_repositorio_destino');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'RepositorioDestino', 'str_repositorio_destino');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdRepositorioOrigem', 'id_repositorio_origem');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUnidadeDestino', 'id_unidade_destino');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'UnidadeDestino', 'str_unidade_destino');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUnidadeOrigem', 'id_unidade_origem');     
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUsuario', 'id_usuario');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUnidade', 'id_unidade');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DTH, 'Registro', 'dth_registro');

      $this->adicionarAtributo(InfraDTO::$PREFIXO_ARR, 'IdProcedimento');

      $this->configurarPK('IdLote', InfraDTO::$TIPO_PK_NATIVA);

      $this->configurarFK('IdLote', 'md_pen_seq_expedir_lote', 'id');
      $this->configurarFK('IdUsuario', 'usuario', 'id_usuario');
      $this->configurarFK('IdUnidade', 'unidade', 'id_unidade');
  }
}
