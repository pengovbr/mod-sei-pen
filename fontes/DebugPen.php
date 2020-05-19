<?

//require_once DIR_SEI_WEB.'/SEI.php';
require_once DIR_SEI_WEB.'/SEI.php';

class DebugPen extends InfraDebug {

    //Rótulo aplicado na mensagem de log para agrupar a sequência mensagens
    private $strDebugTag = null;
    private $numTempoUltimoLog = null;
    private static $instance = null;

    public function __construct($parStrDebugTag=null){
        parent::__construct();
        $this->strDebugTag = $parStrDebugTag;
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new DebugPen();
        }
        return self::$instance;
    }

    public function setStrDebugTag($parStrDebugTag=null)
    {
        $this->strDebugTag = $parStrDebugTag;
    }

    public function gravar($str, $numIdentacao=0, $bolLogTempoProcessamento=true)
    {
        $strDataLog = date("d/m/Y H:i:s");
        $strTag = (!is_null($this->strDebugTag)) ? "[" . $this->strDebugTag . "]": "";
        $strLog = sprintf("[%s] %s %s %s", $strDataLog, $strTag, str_repeat(" ", $numIdentacao * 4), $str);

        //Registro de tempo de processamento desde último log
        if($bolLogTempoProcessamento){
            $numTempoFinal = microtime(true);
            if(is_null($this->numTempoUltimoLog)){
                //Inicializa contador de tempo de processamento
                $this->numTempoUltimoLog = $numTempoFinal;
            } else {
                $numTempoProcessamento = round($numTempoFinal - $this->numTempoUltimoLog, 2);
                $strLog .= " [+{$numTempoProcessamento}s]";
                $this->numTempoUltimoLog = $numTempoFinal;
            }
        }

        parent::gravar($strLog);
    }
}
