<?php

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/sei/src/sei/web/SEI.php';

define("DIR_SEI_WEB", __DIR__ . '/sei/src/sei/web/');
define("DIR_TEST", __DIR__ );
define("DIR_PROJECT", __DIR__ . '/..' );
define("DIR_INFRA", __DIR__ . '/../src/infra/infra_php' );

error_reporting(E_ERROR);
restore_error_handler();

//Classes utilitсrias para manipulaчуo dos dados do SEI
require_once __DIR__ . '/src/utils/DatabaseUtils.php';
require_once __DIR__ . '/src/utils/ParameterUtils.php';

//Representaчуo das pсginas sob teste
require_once __DIR__ . '/src/paginas/PaginaTeste.php';
require_once __DIR__ . '/src/paginas/PaginaLogin.php';
require_once __DIR__ . '/src/paginas/PaginaControleProcesso.php';
require_once __DIR__ . '/src/paginas/PaginaIniciarProcesso.php';
require_once __DIR__ . '/src/paginas/PaginaProcesso.php';
require_once __DIR__ . '/src/paginas/PaginaEnviarProcesso.php';
require_once __DIR__ . '/src/paginas/PaginaIncluirDocumento.php';
require_once __DIR__ . '/src/paginas/PaginaDocumento.php';
require_once __DIR__ . '/src/paginas/PaginaAssinaturaDocumento.php';
require_once __DIR__ . '/src/paginas/PaginaTramitarProcesso.php';
require_once __DIR__ . '/src/paginas/PaginaConsultarAndamentos.php';
require_once __DIR__ . '/src/paginas/PaginaProcessosTramitadosExternamente.php';
require_once __DIR__ . '/src/paginas/PaginaReciboTramite.php';
require_once __DIR__ . '/src/paginas/PaginaEditarProcesso.php';
require_once __DIR__ . '/src/paginas/PaginaAnexarProcesso.php';
require_once __DIR__ . '/src/paginas/PaginaCancelarDocumento.php';
require_once __DIR__ . '/src/paginas/PaginaMoverDocumento.php';
require_once __DIR__ . '/src/paginas/PaginaTramitarProcessoEmLote.php';

require_once __DIR__ . '/tests/CenarioBaseTestCase.php';
