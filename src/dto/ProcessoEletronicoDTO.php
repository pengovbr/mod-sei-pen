<?php

class ProcessoEletronicoDTO extends InfraDTO
{

  public function getStrNomeTabela()
    {
      return 'md_pen_processo_eletronico';
  }

  public function montar()
    {
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'NumeroRegistro', 'numero_registro');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdProcedimento', 'id_procedimento');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdProcedimentoApensacao', 'id_procedimento_apensacao');

      // Campo criado para identificar o protocolo como sendo processo ou documento
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'StaTipoProtocolo', 'sta_tipo_protocolo');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_ARR, 'ObjTramiteDTO');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_ARR, 'ObjRelProcessoEletronicoApensado');

      $this->adicionarAtributoTabelaRelacionada(
          InfraDTO::$PREFIXO_STR,
          'ProtocoloProcedimentoFormatado',
          'protocolo_formatado',
          'protocolo'
      );


      $this->configurarPK('NumeroRegistro', InfraDTO::$TIPO_PK_INFORMADO);
      $this->configurarFK('IdProcedimento', 'protocolo', 'id_protocolo');
      $this->configurarFK('IdProcedimento', 'procedimento', 'id_procedimento', InfraDTO::$TIPO_FK_OBRIGATORIA);        
  }
}
