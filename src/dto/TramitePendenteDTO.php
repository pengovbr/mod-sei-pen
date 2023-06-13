<?

require_once DIR_SEI_WEB.'/SEI.php';

class TramitePendenteDTO extends InfraDTO {

  public function getStrNomeTabela() {
      return 'md_pen_tramite_pendente';
  }

  public function getStrNomeSequenciaNativa() {
      return 'md_pen_seq_tramite_pendente';
  }

  public function montar() {
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdTabela', 'id');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdTramite', 'numero_tramite');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdAtividade', 'id_atividade_expedicao');
      $this->configurarPK('IdTabela', InfraDTO::$TIPO_PK_NATIVA);
  }
}
