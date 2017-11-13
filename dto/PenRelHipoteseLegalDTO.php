<?
require_once dirname(__FILE__).'/../../../SEI.php';
/**
 * DTO de cadastro do Hipotese Legal no Barramento
 *
 * @author Join Tecnologia
 */
class PenRelHipoteseLegalDTO extends InfraDTO {

    public function getStrNomeTabela() {
        return 'md_pen_rel_hipotese_legal';
    }
    
    public function montar() {
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdMap', 'id_mapeamento');
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdHipoteseLegal', 'id_hipotese_legal');
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Tipo', 'tipo');
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Ativo', 'sin_ativo');
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdBarramento', 'id_hipotese_legal_pen'); 
        
        $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'Identificacao', 'identificacao', 'md_pen_hipotese_legal');  
        
        $this->configurarPK('IdMap',InfraDTO::$TIPO_PK_SEQUENCIAL);
        $this->configurarFK('IdBarramento', 'md_pen_hipotese_legal', 'id_hipotese_legal');

        //$this->configurarExclusaoLogica('Ativo', 'N');
    }
}
