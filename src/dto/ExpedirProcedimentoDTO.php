<?

require_once DIR_SEI_WEB.'/SEI.php';

class ExpedirProcedimentoDTO extends InfraDTO {

  public function getStrNomeTabela() {
     return null;
  }

  public function montar() {
    $this->adicionarAtributo(InfraDTO::$PREFIXO_DBL, 'IdProcedimento');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM, 'IdRepositorioOrigem');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'RepositorioOrigem');    
    $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM, 'IdRepositorioDestino');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'RepositorioDestino');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM, 'IdUnidadeOrigem');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'UnidadeOrigem');    
    $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM, 'IdUnidadeDestino');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'UnidadeDestino');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_BOL, 'SinUrgente');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM, 'IdMotivoUrgencia');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_ARR, 'IdProcessoApensado');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_BOL, 'SinProcessamentoEmBloco');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM, 'IdBloco');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM, 'IdAtividade');
    $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM, 'IdUnidade');
  }
}
