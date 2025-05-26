<?php

require_once __DIR__ . '/vendor/autoload.php';
 
define("DIR_SEI_VENDOR", __DIR__ . '/vendor');

require_once __DIR__ . '/sei/src/sei/web/SEI.php';

if (!defined("DIR_SEI_WEB")){
    define("DIR_SEI_WEB", __DIR__ . '/sei/src/sei/web/');
}
define("DIR_TEST", __DIR__ );
define("DIR_PROJECT", __DIR__ . '/..' );
define("DIR_INFRA", __DIR__ . '/../src/infra/infra_php' );

// mostre avisos, notices, deprecated, strict etc.
error_reporting(E_ALL);

// force a exibiзгo em tela (ъtil em dev; em prod use log)
ini_set('display_errors', '1');

//Classes utilitбrias para manipulaзгo dos dados do SEI
require_once __DIR__ . '/src/utils/DatabaseUtils.php';
require_once __DIR__ . '/src/utils/ParameterUtils.php';

//Representaзгo das pбginas sob teste
require_once __DIR__ . '/src/paginas/PaginaTeste.php';
require_once __DIR__ . '/src/paginas/PaginaLogin.php';
require_once __DIR__ . '/src/paginas/PaginaControleProcesso.php';
require_once __DIR__ . '/src/paginas/PaginaProcesso.php';
require_once __DIR__ . '/src/paginas/PaginaDocumento.php';
require_once __DIR__ . '/src/paginas/PaginaTramitarProcesso.php';
require_once __DIR__ . '/src/paginas/PaginaConsultarAndamentos.php';
require_once __DIR__ . '/src/paginas/PaginaProcessosTramitadosExternamente.php';
require_once __DIR__ . '/src/paginas/PaginaReciboTramite.php';
require_once __DIR__ . '/src/paginas/PaginaEditarProcesso.php';
require_once __DIR__ . '/src/paginas/PaginaCancelarDocumento.php';
require_once __DIR__ . '/src/paginas/PaginaMoverDocumento.php';
require_once __DIR__ . '/src/paginas/PaginaCadastroMapEnvioCompDigitais.php';
require_once __DIR__ . '/src/paginas/PaginaEnvioParcialListar.php';

require_once __DIR__ . '/tests/CenarioBaseTestCase.php';
require_once __DIR__ . '/tests/FixtureCenarioBaseTestCase.php';