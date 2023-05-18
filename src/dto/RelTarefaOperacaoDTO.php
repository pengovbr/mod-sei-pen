<?

require_once DIR_SEI_WEB.'/SEI.php';

class RelTarefaOperacaoDTO extends InfraDTO {

  public function getStrNomeTabela() {
     return "md_pen_rel_tarefa_operacao";
  }

  public function montar() {
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'CodigoOperacao', 'codigo_operacao');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdTarefa', 'id_tarefa');
        
    $this->configurarPK('IdTarefa', InfraDTO::$TIPO_PK_INFORMADO);    
    $this->configurarFK('IdTarefa', 'tarefa', 'id_tarefa');
  }
}
