<?php


/**
 * Classe utilitário utilizada em diversas regras de negócio do mod-sei-pen
 */
class ModPenUtilsRN extends InfraRN
{
  protected function inicializarObjInfraIBanco()
    {
      return BancoSEI::getInstance();
  }

  public static function obterUnidadeRecebimento()
    {
      $objPenParametroRN = new PenParametroRN();
      $numUnidadeRecebimentoProcessos = $objPenParametroRN->getParametro('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO');

    if(empty($numUnidadeRecebimentoProcessos)) {
        $objInfraParametro = new InfraParametro(BancoSEI::getInstance());
        $numUnidadeRecebimentoProcessos = $objInfraParametro->getValor('ID_UNIDADE_TESTE');
    }

      return $numUnidadeRecebimentoProcessos;
  }

  public static function simularLoginUnidadeRecebimento()
    {
      $numUnidadeRecebimentoProcessos = self::obterUnidadeRecebimento();

    if(empty($numUnidadeRecebimentoProcessos)) {
        $strMensagem = "Configuração da unidade para representação de trâmites em órgãos externos não pode ser localizada.\n";
        $strMensagem .= "Necessário atribuição de uma unidade válida nos parâmetros do Módulo Tramita GOV.BR (mod-sei-pen)";
        throw new InfraException($strMensagem);
    }

      SessaoSEI::getInstance(false)->simularLogin('SEI', null, null, $numUnidadeRecebimentoProcessos);
  }
}
