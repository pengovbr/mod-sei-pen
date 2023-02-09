<?php


/**
 * Classe utilit�rio utilizada em diversas regras de neg�cio do mod-sei-pen
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

    if(empty($numUnidadeRecebimentoProcessos)){
        $objInfraParametro = new InfraParametro(BancoSEI::getInstance());
        $numUnidadeRecebimentoProcessos = $objInfraParametro->getValor('ID_UNIDADE_TESTE');
    }

      return $numUnidadeRecebimentoProcessos;
  }

  public static function simularLoginUnidadeRecebimento()
    {
      $numUnidadeRecebimentoProcessos = self::obterUnidadeRecebimento();

    if(empty($numUnidadeRecebimentoProcessos)) {
        $strMensagem = "Configura��o da unidade para representa��o de tr�mites em �rg�os externos n�o pode ser localizada.\n";
        $strMensagem .= "Necess�rio atribui��o de uma unidade v�lida nos par�metros do M�dulo de Integra��o PEN (mod-sei-pen)";
        throw new InfraException($strMensagem);
    }

      SessaoSEI::getInstance(false)->simularLogin('SEI', null, null, $numUnidadeRecebimentoProcessos);
  }
}
