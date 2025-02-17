<?php

require_once DIR_SEI_WEB.'/SEI.php';

class EstruturaDTO extends InfraDTO
{

  public function getStrNomeTabela()
    {
      return null;
  }

  public function montar()
    {
      $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM, 'NumeroDeIdentificacaoDaEstrutura');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'Nome');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'Sigla');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_BOL, 'Ativo');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_BOL, 'AptoParaReceberTramites');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'CodigoNoOrgaoEntidade');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_ARR, 'Hierarquia');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM, 'TotalDeRegistros');
  }
}
