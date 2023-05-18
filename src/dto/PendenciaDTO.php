<?

require_once DIR_SEI_WEB.'/SEI.php';

class PendenciaDTO extends InfraDTO {

  public function getStrNomeTabela() {
     return null;
  }

  public function montar() {
    $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM, 'IdentificacaoTramite');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'Status');

  }
}
