<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Data Transfer Object de parâmetros do módulo PEN
 *
 *
 */
class PenLoteProcedimentoDTO extends InfraDTO
{

    public function getStrNomeTabela()
    {
        return 'md_pen_rel_expedir_lote';
    }

    public function montar()
    {
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdLote','id_lote');
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdProcedimento','id_procedimento');
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdAndamento', 'id_andamento');
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdAtividade', 'id_atividade_expedicao');

        $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'IdLote','id_lote','md_pen_expedir_lote');
        $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'IdRepositorioDestino','id_repositorio_destino','md_pen_expedir_lote');
        $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'RepositorioDestino','str_repositorio_destino','md_pen_expedir_lote');
        $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'IdRepositorioOrigem','id_repositorio_origem','md_pen_expedir_lote');
        $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'IdUnidadeDestino','id_unidade_destino','md_pen_expedir_lote');
        $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'UnidadeDestino','str_unidade_destino','md_pen_expedir_lote');
        $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'IdUnidadeOrigem','id_unidade_origem','md_pen_expedir_lote');
        $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'IdUsuario','id_usuario','md_pen_expedir_lote');
        $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'IdUnidade','id_unidade','md_pen_expedir_lote');
        $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_DTH, 'Registro','dth_registro','md_pen_expedir_lote');

        $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'ProcedimentoFormatado','protocolo_formatado','protocolo');
        $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'NomeUsuario','nome','usuario');

        $this->configurarPK('IdLote', InfraDTO::$TIPO_PK_INFORMADO);
        $this->configurarPK('IdProcedimento', InfraDTO::$TIPO_PK_INFORMADO);

        $this->configurarFK('IdLote', 'md_pen_expedir_lote', 'id_lote');
        $this->configurarFK('IdProcedimento', 'procedimento', 'id_procedimento');
        $this->configurarFK('IdProcedimento', 'protocolo', 'id_protocolo');
        $this->configurarFK('IdUsuario', 'usuario', 'id_usuario');
        $this->configurarFK('IdUnidade', 'unidade', 'id_unidade');
    }
}
