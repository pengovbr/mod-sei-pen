<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

/**
 * Persistência de dados no banco de dados
 * 
 * @autor Join Tecnologia
 */
class ProcedimentoAndamentoDTO extends InfraDTO {

    public function getStrNomeTabela() {
        return 'md_pen_procedimento_andamento';
    }

    public function montar() {
        
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdAndamento', 'id_andamento');
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdProcedimento', 'id_procedimento');
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdTramite', 'id_tramite');
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Situacao', 'situacao');
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DTH, 'Data', 'data');
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Mensagem', 'mensagem');
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Hash', 'hash');
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'Tarefa', 'id_tarefa');
        
        $this->configurarPK('IdAndamento', InfraDTO::$TIPO_PK_SEQUENCIAL);
    }
}