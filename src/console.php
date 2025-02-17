<?php

set_time_limit(0);

set_include_path(implode(PATH_SEPARATOR, [realpath(__DIR__.'/../../../infra/infra_php'), get_include_path()]));

require_once DIR_SEI_WEB.'/SEI.php';
require_once DIR_SEI_WEB.'/SEI.php';

if(!array_key_exists('argv', $_SERVER)) {
    die('Este script somente pode ser executado por linha de comando');
}

$numRetorno = 0;

try {

    print PHP_EOL;
    print PenConsoleRN::format('PEN - Commad Line Interface', 'green', true).PHP_EOL;
    print PHP_EOL;

    $objActionRN = new PenConsoleActionRN();

    $objPenConsoleRN = new PenConsoleRN($objActionRN);
    $strRetorno = $objPenConsoleRN->run();

  if(empty($_SERVER['argv'])) {

      print PenConsoleRN::format('Sucesso: ', 'blue', true);
  }
    print $strRetorno.PHP_EOL;
}
catch(\InfraException $e) {

    $numRetorno = 1;

    print PenConsoleRN::format('Erro: ', 'red', true);
    print PenConsoleRN::format($e->getStrDescricao());
}
catch(\Exception $e) {

    print PenConsoleRN::format('Erro: ', 'red', true);
    print PenConsoleRN::format($e->getMessage());

    $numRetorno = 1;
}

print PHP_EOL;
print PHP_EOL;

exit($numRetorno);
