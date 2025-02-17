<?php

require_once DIR_SEI_WEB . '/SEI.php';

class TramiteEmBlocoDTO extends InfraDTO
{

  public function getStrNomeTabela()
    {
      return 'md_pen_bloco';
  }

  public function getStrNomeSequenciaNativa()
    {
      return 'md_pen_seq_bloco';
  }

  public function montar()
    {

      $this->adicionarAtributoTabela(
          InfraDTO::$PREFIXO_NUM,
          'Id',
          'id'
      );

      $this->adicionarAtributoTabela(
          InfraDTO::$PREFIXO_NUM,
          'IdUnidade',
          'id_unidade'
      );

      $this->adicionarAtributoTabela(
          InfraDTO::$PREFIXO_NUM,
          'IdUsuario',
          'id_usuario'
      );

      $this->adicionarAtributoTabela(
          InfraDTO::$PREFIXO_STR,
          'Descricao',
          'descricao'
      );

      $this->adicionarAtributoTabela(
          InfraDTO::$PREFIXO_STR,
          'IdxBloco',
          'idx_bloco'
      );

      $this->adicionarAtributoTabela(
          InfraDTO::$PREFIXO_STR,
          'StaTipo',
          'sta_tipo'
      );

      $this->adicionarAtributoTabela(
          InfraDTO::$PREFIXO_STR,
          'StaEstado',
          'sta_estado'
      );

      $this->adicionarAtributoTabela(
          InfraDTO::$PREFIXO_NUM,
          'Ordem',
          'ordem'
      );

      $this->adicionarAtributoTabelaRelacionada(
          InfraDTO::$PREFIXO_STR,
          'SiglaUnidade',
          'uc.sigla',
          'unidade uc'
      );

      $this->adicionarAtributoTabelaRelacionada(
          InfraDTO::$PREFIXO_STR,
          'DescricaoUnidade',
          'uc.descricao',
          'unidade uc'
      );

      $this->adicionarAtributoTabelaRelacionada(
          InfraDTO::$PREFIXO_STR,
          'SiglaUnidadeRelBlocoUnidade',
          'ud.sigla',
          'unidade ud'
      );

      $this->adicionarAtributoTabelaRelacionada(
          InfraDTO::$PREFIXO_STR,
          'DescricaoUnidadeRelBlocoUnidade',
          'ud.descricao',
          'unidade ud'
      );

      $this->adicionarAtributoTabelaRelacionada(
          InfraDTO::$PREFIXO_STR,
          'SiglaUsuario',
          'sigla',
          'usuario'
      );

      $this->adicionarAtributoTabelaRelacionada(
          InfraDTO::$PREFIXO_STR,
          'NomeUsuario',
          'nome',
          'usuario'
      );

      $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'TipoDescricao');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'PalavrasPesquisa');

      $this->configurarPK('Id', InfraDTO::$TIPO_PK_NATIVA);

      $this->configurarFK('IdUsuario', 'usuario', 'id_usuario');
      $this->configurarFK('IdUnidade', 'unidade uc', 'uc.id_unidade');
  }
}
