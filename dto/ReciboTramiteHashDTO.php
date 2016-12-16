<?

require_once dirname(__FILE__).'/../../../SEI.php';

class ReciboTramiteHashDTO extends InfraDTO {

  public function getStrNomeTabela() {
     return "md_pen_recibo_tramite_hash";
  }

  public function montar() {
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'NumeroRegistro', 'numero_registro');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdTramite', 'id_tramite');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'HashComponenteDigital', 'hash_componente_digital');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'TipoRecibo', 'tipo_recibo');
    
  }
}

