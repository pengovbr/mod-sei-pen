<?

class PENControladorWebServices implements ISeiControladorWebServices{

	public function processar($strServico){

		$strArq = null;

		/*
	    switch ($strServico) {
            case 'cvm_xxxxxx':
                $strArq = 'cvm_xxxxxx.wsdl';
                break;
        }

        if ($strArq!=null){
    	   $strArq = dirname(__FILE__).'/ws/'.$strArq;
        }
    */

    return $strArq;
	}
}
?>