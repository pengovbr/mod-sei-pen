<?php

require_once DIR_SEI_WEB.'/SEI.php';

class PenRelTipoDocMapEnviadoDTO extends InfraDTO
{

  public function getStrNomeTabela()
    {
      return "md_pen_rel_doc_map_enviado";
  }

  public function getStrNomeSequenciaNativa()
    {
      return 'md_pen_seq_rel_doc_map_enviado';
  }

  public function montar()
    {

      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdMap', 'id_mapeamento');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'CodigoEspecie', 'codigo_especie');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdSerie', 'id_serie');

      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'NomeSerie', 'nome', 'serie');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'NomeEspecie', 'nome_especie', 'md_pen_especie_documental');

      $this->configurarPK('IdMap', InfraDTO::$TIPO_PK_NATIVA);
      $this->configurarFK('IdSerie', 'serie', 'id_serie');
      $this->configurarFK('CodigoEspecie', 'md_pen_especie_documental', 'id_especie');
  }
}
