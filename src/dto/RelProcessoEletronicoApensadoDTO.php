<?php

class RelProcessoEletronicoApensadoDTO extends InfraDTO
{

  public function getStrNomeTabela()
    {
      return 'md_pen_rel_processo_apensado';
  }

  public function montar()
    {
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'NumeroRegistro', 'numero_registro');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdProcedimentoApensado', 'id_procedimento_apensado');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Protocolo', 'protocolo');

      $this->configurarPK('NumeroRegistro', InfraDTO::$TIPO_PK_INFORMADO);
      $this->configurarPK('IdProcedimentoApensado', InfraDTO::$TIPO_PK_INFORMADO);
      $this->configurarFK('NumeroRegistro', 'md_pen_procedimento_eletronico', 'numero_registro', InfraDTO::$TIPO_FK_OBRIGATORIA);
  }
}
