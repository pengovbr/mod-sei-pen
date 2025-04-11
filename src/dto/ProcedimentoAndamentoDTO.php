<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Persistência de dados no banco de dados
 */
class ProcedimentoAndamentoDTO extends InfraDTO
{

  public function getStrNomeTabela()
    {
      return 'md_pen_procedimento_andamento';
  }

  public function getStrNomeSequenciaNativa()
    {
      return 'md_pen_seq_procedimento_andam';
  }

  public function montar()
    {

      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdAndamento', 'id_andamento');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdProcedimento', 'id_procedimento');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'NumeroRegistro', 'numero_registro');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdTramite', 'id_tramite');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Situacao', 'situacao');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DTH, 'Data', 'data');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Mensagem', 'mensagem');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Hash', 'hash');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'Tarefa', 'id_tarefa');

      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'IdRepositorioOrigem', 'id_repositorio_origem', 'md_pen_tramite');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'IdEstruturaOrigem', 'id_estrutura_origem', 'md_pen_tramite');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'IdRepositorioDestino', 'id_repositorio_destino', 'md_pen_tramite');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'IdEstruturaDestino', 'id_estrutura_destino', 'md_pen_tramite');

      $this->configurarPK('IdAndamento', InfraDTO::$TIPO_PK_NATIVA);
      $this->configurarFK('IdTramite', 'md_pen_tramite', 'id_tramite', InfraDTO::$TIPO_FK_OPCIONAL);
  }

  public static function criarAndamento($strMensagem = 'Não informado', $strSituacao = 'N')
    {
      $objProcedimentoAndamentoDTO = new ProcedimentoAndamentoDTO();
      $objProcedimentoAndamentoDTO->setStrSituacao($strSituacao);
      $objProcedimentoAndamentoDTO->setStrMensagem($strMensagem);
      return $objProcedimentoAndamentoDTO;
  }
}
