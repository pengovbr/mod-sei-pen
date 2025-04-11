<?php
require_once DIR_SEI_WEB.'/SEI.php';

/**
 *
 */
class ProcessoExpedidoDTO extends InfraDTO
{

  public function __construct()
    {
      // Força o JOIN com todas as tabelas
      parent::__construct(true);
  }

  public function getStrNomeTabela()
    {
      return 'protocolo';
  }

  public function montar()
    {

      // Protocolo
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdProtocolo', 'id_protocolo');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'StaEstado', 'sta_estado');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'ProtocoloFormatado', 'protocolo_formatado');

      // Atividade
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'IdAtividade', 'id_atividade', 'atividade');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'Tarefa', 'id_tarefa', 'atividade');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_DTH, 'Expedido', 'dth_conclusao', 'atividade');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'IdUsuario', 'id_usuario_origem', 'atividade');

      // Usuário
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'NomeUsuario', 'nome', 'usuario');

      // Atributo Andamento
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'Destino', 'valor', 'atributo_andamento');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'AtribNome', 'nome', 'atributo_andamento');
      $this->setStrAtribNome('UNIDADE_DESTINO');

      $this->configurarFK('IdProtocolo', 'atividade', 'id_protocolo');
      $this->configurarFK('IdUsuario', 'usuario', 'id_usuario');
      $this->configurarFK('IdAtividade', 'atributo_andamento', 'id_atividade');

      $this->setOrd('Expedido', InfraDTO::$TIPO_ORDENACAO_DESC);
  }
}
