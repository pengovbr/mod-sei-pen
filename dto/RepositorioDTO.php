<?

require_once dirname(__FILE__).'/../../../SEI.php';

class RepositorioDTO extends InfraDTO {

  public function getStrNomeTabela() {
  	 return null;
  }

  public function montar() {
    $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM, 'Id');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'Nome');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_BOL, 'Ativo');
  }
}
