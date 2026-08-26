<?php

require_once __DIR__ . '/vendor/autoload.php';

// Fixa sebastian/diff do vendor de testes ANTES de carregar o SEI: o autoloader
// do SEI e prependado e traz uma copia antiga, cujo Differ recebe uma string.
// Com a copia errada, ComparisonFailure::getDiff() lancava Error e o PHPUnit
// DESCARTAVA a falha - assertEquals com string/array passava em silencio.
class_exists(\SebastianBergmann\Diff\Differ::class);
class_exists(\SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder::class);
class_exists(\SebastianBergmann\Diff\Output\DiffOnlyOutputBuilder::class);
class_exists(\SebastianBergmann\Diff\Chunk::class);
class_exists(\SebastianBergmann\Diff\Diff::class);
class_exists(\SebastianBergmann\Diff\Line::class);
class_exists(\SebastianBergmann\Diff\LongestCommonSubsequenceCalculator::class);
class_exists(\SebastianBergmann\Diff\TimeEfficientLongestCommonSubsequenceCalculator::class);
class_exists(\SebastianBergmann\Diff\MemoryEfficientLongestCommonSubsequenceCalculator::class);
 
define("DIR_SEI_VENDOR", __DIR__ . '/vendor');

require_once __DIR__ . '/sei/src/sei/web/SEI.php';

if (!defined("DIR_SEI_WEB")){
    define("DIR_SEI_WEB", __DIR__ . '/sei/src/sei/web/');
}
define("DIR_TEST", __DIR__ );
define("DIR_PROJECT", __DIR__ . '/..' );
define("DIR_INFRA", __DIR__ . '/../src/infra/infra_php' );

error_reporting(E_ALL); // Exibe todos os tipos de erro, incluindo warnings, notices, etc.
ini_set('display_errors', 1); // Garante que os erros serуo exibidos no navegador

//Classes utilitсrias para manipulaчуo dos dados do SEI
require_once __DIR__ . '/src/utils/DatabaseUtils.php';
require_once __DIR__ . '/src/utils/ParameterUtils.php';
require_once __DIR__ . '/src/utils/RandomUtils.php';

//Representaчуo das pсginas sob teste
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
require_once __DIR__ . '/src/paginas/PaginaAnexarProcesso.php';
require_once __DIR__ . '/src/paginas/PaginaAgendamentos.php';
require_once __DIR__ . '/src/paginas/PaginaCancelarDocumento.php';
require_once __DIR__ . '/src/paginas/PaginaMoverDocumento.php';
require_once __DIR__ . '/src/paginas/PaginaCadastroMapEnvioCompDigitais.php';
require_once __DIR__ . '/src/paginas/PaginaEnvioParcialListar.php';
require_once __DIR__ . '/src/paginas/PaginaEnviarEmail.php';

require_once __DIR__ . '/tests/CenarioBaseTestCase.php';
require_once __DIR__ . '/tests/FixtureCenarioBaseTestCase.php';