<?php

require_once __DIR__ . '/vendor/autoload.php';

//Classes utilitсrias para manipulaчуo dos dados do SEI
require_once __DIR__ . '/src_sei4/utils/DatabaseUtils.php';
require_once __DIR__ . '/src_sei4/utils/ParameterUtils.php';

//Representaчуo das pсginas sob teste
require_once __DIR__ . '/src_sei4/paginas/PaginaTeste.php';
require_once __DIR__ . '/src_sei4/paginas/PaginaLogin.php';
require_once __DIR__ . '/src_sei4/paginas/PaginaControleProcesso.php';
require_once __DIR__ . '/src_sei4/paginas/PaginaIniciarProcesso.php';
require_once __DIR__ . '/src_sei4/paginas/PaginaProcesso.php';
require_once __DIR__ . '/src_sei4/paginas/PaginaEnviarProcesso.php';
require_once __DIR__ . '/src_sei4/paginas/PaginaIncluirDocumento.php';
require_once __DIR__ . '/src_sei4/paginas/PaginaDocumento.php';
require_once __DIR__ . '/src_sei4/paginas/PaginaAssinaturaDocumento.php';
require_once __DIR__ . '/src_sei4/paginas/PaginaTramitarProcesso.php';
require_once __DIR__ . '/src_sei4/paginas/PaginaConsultarAndamentos.php';
require_once __DIR__ . '/src_sei4/paginas/PaginaProcessosTramitadosExternamente.php';
require_once __DIR__ . '/src_sei4/paginas/PaginaReciboTramite.php';
require_once __DIR__ . '/src_sei4/paginas/PaginaEditarProcesso.php';
require_once __DIR__ . '/src_sei4/paginas/PaginaAnexarProcesso.php';
require_once __DIR__ . '/src_sei4/paginas/PaginaCancelarDocumento.php';
require_once __DIR__ . '/src_sei4/paginas/PaginaMoverDocumento.php';
require_once __DIR__ . '/src_sei4/paginas/PaginaTramitarProcessoEmLote.php';

require_once __DIR__ . '/tests_sei4/CenarioBaseTestCase.php';
