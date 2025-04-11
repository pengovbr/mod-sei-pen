<?php

class TramiteDTO extends InfraDTO
{

  public function getStrNomeTabela()
    {
      return 'md_pen_tramite';
  }

  public function montar()
    {
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'NumeroRegistro', 'numero_registro');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdTramite', 'id_tramite');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'TicketEnvioComponentes', 'ticket_envio_componentes');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DTH, 'Registro', 'dth_registro');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdAndamento', 'id_andamento');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUsuario', 'id_usuario');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUnidade', 'id_unidade');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdRepositorioOrigem', 'id_repositorio_origem');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdEstruturaOrigem', 'id_estrutura_origem');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdRepositorioDestino', 'id_repositorio_destino');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdEstruturaDestino', 'id_estrutura_destino');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'StaTipoTramite', 'sta_tipo_tramite');

      $this->adicionarAtributo(InfraDTO::$PREFIXO_ARR, 'ObjComponenteDigitalDTO');

      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'NomeUsuario', 'nome', 'usuario');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'NomeUnidade', 'nome', 'unidade');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'IdProcedimento', 'id_procedimento', 'md_pen_processo_eletronico');
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'StaTipoProtocolo', 'sta_tipo_protocolo', 'md_pen_processo_eletronico');

      $this->configurarPK('NumeroRegistro', InfraDTO::$TIPO_PK_INFORMADO);
      $this->configurarPK('IdTramite', InfraDTO::$TIPO_PK_INFORMADO);

      $this->configurarFK('NumeroRegistro', 'md_pen_tramite', 'numero_registro', InfraDTO::$TIPO_FK_OBRIGATORIA);
      $this->configurarFK('NumeroRegistro', 'md_pen_processo_eletronico', 'numero_registro');
      $this->configurarFK('IdUsuario', 'usuario', 'id_usuario');
      $this->configurarFK('IdUnidade', 'unidade', 'id_unidade');
  }

}
