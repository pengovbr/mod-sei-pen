<?php
/**
 * Script para atualização do sistema SEI.
 * 
 * Modificar o "short_open_tag" para "On" no php.ini
 * 
 * PHP 5.3.3 (cli) (built: Jul  9 2015 17:39:00) 
 * Copyright (c) 1997-2010 The PHP Group
 * Zend Engine v2.3.0, Copyright (c) 1998-2010 Zend Technologies
 */

try {

    include_once DIR_SEI_WEB.'/SEI.php';

    $objPenConsoleRN = new PenConsoleRN();
    $arrArgs = $objPenConsoleRN->getTokens();

    $objAtualizarRN = new PenAtualizarSeiRN($arrArgs);
    $objAtualizarRN->atualizarVersao();

    exit(0);
}
catch(InfraException $e){

    print $e->getStrDescricao().PHP_EOL;
}
catch(Exception $e) {

    print InfraException::inspecionar($e);

  try {
      LogSEI::getInstance()->gravar(InfraException::inspecionar($e));
  } catch (Exception $e) {

  }

    exit(1);
}

print PHP_EOL;

/**
    # Apagar modulo PEN no SEI pelo MySQL

    SET FOREIGN_KEY_CHECKS = 0;
    DROP TABLE `sei`.`pen_componente_digital`;
    DROP TABLE `sei`.`pen_envio_recebimento_tramite`;
    DROP TABLE `sei`.`pen_especie_documental`;
    DROP TABLE `sei`.`pen_procedimento_andamento`;
    DROP TABLE `sei`.`pen_procedimento_expedir_andamento`;
    DROP TABLE `sei`.`pen_processo_eletronico`;
    DROP TABLE `sei`.`pen_protocolo`;
    DROP TABLE `sei`.`pen_receber_tramites_recusados`;
    DROP TABLE `sei`.`pen_recibo_tramite`;
    DROP TABLE `sei`.`pen_recibo_tramite_enviado`;
    DROP TABLE `sei`.`pen_recibo_tramite_recebido`;
    DROP TABLE `sei`.`pen_rel_processo_apensado`;
    DROP TABLE `sei`.`pen_rel_serie_especie`;
    DROP TABLE `sei`.`pen_rel_tarefa_operacao`;
    DROP TABLE `sei`.`pen_rel_tipo_documento_mapeamento_recebido`;
    DROP TABLE `sei`.`pen_tramite`;
    DROP TABLE `sei`.`pen_tramite_pendente`;
    ALTER TABLE unidade DROP COLUMN id_unidade_rh;
    SET FOREIGN_KEY_CHECKS = 1;
 */
