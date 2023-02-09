<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Cria uma tabela de rela��o 1 para 1 para unidade com o intuito de adicionar
 * novos campos de configura��o para cada unidade utilizado somente pelo m�dulo
 * PEN
 * 
 * Crio a classe com extendida de UnidadeDTO em fun��o dos m�todos de UnidadeRN,
 * que for�a o hinting para UnidadeDTO, ent�o n�o gera erro usar PenUnidadeDTO
 * com o UnidadeBD e UnidadeRN
 * 
 *
 * @see http://php.net/manual/pt_BR/language.oop5.typehinting.php
 */
class PenUnidadeDTO extends UnidadeDTO {

  public function getStrNomeTabela() {
      return 'md_pen_unidade';
  }
    
  public function montar() {
        
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUnidade', 'id_unidade'); 
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUnidadeRH', 'id_unidade_rh'); 
      $this->configurarPK('IdUnidade', InfraDTO::$TIPO_PK_INFORMADO);
        
      // Infelizmente n�o funciona com parent::getArrAtributos(), pois o arrAtributos
      // esta na InfraDTO e ela confunde em fun��o do extends, ent�o tenho que 
      // criar uma nova inst�ncia
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
