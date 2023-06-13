<?

require_once DIR_SEI_WEB.'/SEI.php';

class ReciboTramiteHashDTO extends InfraDTO {

  public function getStrNomeTabela() {
      return "md_pen_recibo_tramite_hash";
  }

  public function getStrNomeSequenciaNativa() {
      return 'md_pen_seq_recibo_tramite_hash';
  }

  public function montar() {
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdTramiteHash', 'id_tramite_hash');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'NumeroRegistro', 'numero_registro');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdTramite', 'id_tramite');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'HashComponenteDigital', 'hash_componente_digital');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'TipoRecibo', 'tipo_recibo');

      $this->configurarPK('IdTramiteHash', InfraDTO::$TIPO_PK_NATIVA);
  }

}
