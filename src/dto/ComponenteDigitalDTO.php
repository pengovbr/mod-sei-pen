<?php

require_once DIR_SEI_WEB.'/SEI.php';

class ComponenteDigitalDTO extends InfraDTO {

  public function getStrNomeTabela() {
     return 'md_pen_componente_digital';
  }

  public function montar() {
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'NumeroRegistro', 'numero_registro');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdProcedimento', 'id_procedimento');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdProcedimentoAnexado', 'id_procedimento_anexado');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdDocumento', 'id_documento');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdTramite', 'id_tramite');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdAnexo', 'id_anexo');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Nome', 'nome');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'HashConteudo', 'hash_conteudo');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Protocolo', 'protocolo');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'ProtocoloProcedimentoAnexado', 'protocolo_procedimento_anexado');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'AlgoritmoHash', 'algoritmo_hash');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'TipoConteudo', 'tipo_conteudo');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'MimeType', 'mime_type');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'DadosComplementares', 'dados_complementares');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'Tamanho', 'tamanho');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'CodigoEspecie', 'codigo_especie');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'NomeEspecieProdutor', 'nome_especie_produtor');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'Ordem', 'ordem');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'OrdemDocumento', 'ordem_documento');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'OrdemDocumentoReferenciado', 'ordem_documento_referenciado');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'OrdemDocumentoAnexado', 'ordem_documento_anexado');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'SinEnviar', 'sin_enviar');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdAnexoImutavel', 'id_anexo_imutavel');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'TarjaLegada', 'tarja_legada');

    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'StaTipoTramite', 'sta_tipo_tramite', 'md_pen_tramite');
    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_NUM, 'TicketEnvioComponentes', 'ticket_envio_componentes', 'md_pen_tramite');
    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'ConteudoAssinaturaDocumento', 'conteudo_assinatura', 'documento_conteudo');
    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'ProtocoloDocumentoFormatado', 'protocolo_formatado', 'protocolo');
    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'StaProtocolo', 'sta_protocolo', 'protocolo');
    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'StaEstadoProtocolo', 'sta_estado', 'protocolo');


    $this->configurarPK('NumeroRegistro', InfraDTO::$TIPO_PK_INFORMADO);
    $this->configurarPK('IdProcedimento', InfraDTO::$TIPO_PK_INFORMADO);
    $this->configurarPK('IdDocumento', InfraDTO::$TIPO_PK_INFORMADO);
    $this->configurarPK('IdTramite', InfraDTO::$TIPO_PK_INFORMADO);

    $this->configurarFK('NumeroRegistro', 'md_pen_tramite', 'numero_registro', InfraDTO::$TIPO_FK_OBRIGATORIA);
    $this->configurarFK('IdTramite', 'md_pen_tramite', 'id_tramite', InfraDTO::$TIPO_FK_OBRIGATORIA);
    $this->configurarFK('IdDocumento', 'documento', 'id_documento', InfraDTO::$TIPO_FK_OBRIGATORIA);
    $this->configurarFK('IdDocumento', 'protocolo', 'id_protocolo', InfraDTO::$TIPO_FK_OBRIGATORIA);
    $this->configurarFK('IdDocumento', 'documento_conteudo', 'id_documento', InfraDTO::$TIPO_FK_OBRIGATORIA);
  }
}
