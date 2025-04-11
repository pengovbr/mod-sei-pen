<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Cria uma tabela de relação 1 para 1 para unidade com o intuito de adicionar
 * novos campos de configuração para cada unidade utilizado somente pelo módulo
 * PEN
 * 
 * Crio a classe com extendida de UnidadeDTO em função dos métodos de UnidadeRN,
 * que força o hinting para UnidadeDTO, então não gera erro usar PenUnidadeDTO
 * com o UnidadeBD e UnidadeRN
 *
 * @see http://php.net/manual/pt_BR/language.oop5.typehinting.php
 */
class PenUnidadeDTO extends UnidadeDTO
{

  public function getStrNomeTabela(): string
    {
      return 'md_pen_unidade';
  }
    
  public function montar(): void
    {
        
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUnidade', 'id_unidade');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUnidadeRH', 'id_unidade_rh');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'NomeUnidadeRH', 'nome_unidade_rh');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'SiglaUnidadeRH', 'sigla_unidade_rh');
      $this->configurarPK('IdUnidade', InfraDTO::$TIPO_PK_INFORMADO);

      $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM, 'IdUnidadeMap');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'DescricaoMap');
        
      // Infelizmente não funciona com parent::getArrAtributos(), pois o arrAtributos
      // esta na InfraDTO e ela confunde em função do extends, então tenho que 
      // criar uma nova instância
      $objUnidadeDTO = new UnidadeDTO();
      $objUnidadeDTO->retTodos();
        
    foreach($objUnidadeDTO->getArrAtributos() as $arrAtrib) {
            
      if($arrAtrib[InfraDTO::$POS_ATRIBUTO_PREFIXO] != 'IdUnidade') {
            
        $this->adicionarAtributoTabelaRelacionada(
            $arrAtrib[InfraDTO::$POS_ATRIBUTO_PREFIXO], 
            $arrAtrib[InfraDTO::$POS_ATRIBUTO_NOME], 
            $arrAtrib[InfraDTO::$POS_ATRIBUTO_CAMPO_SQL], 
            $objUnidadeDTO->getStrNomeTabela()
        );
      }
    }     

      $this->configurarFK('IdUnidade', 'unidade', 'id_unidade');  
  }
}
