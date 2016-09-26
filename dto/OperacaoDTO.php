<?

require_once dirname(__FILE__).'/../../../SEI.php';

class OperacaoDTO extends InfraDTO {

  public function getStrNomeTabela() {
     return null;
  }

  public function montar() {
    $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'Codigo');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'Nome');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'Complemento');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_DTH, 'Operacao');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'IdentificacaoPessoaOrigem');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'NomePessoaOrigem');
  }
}
