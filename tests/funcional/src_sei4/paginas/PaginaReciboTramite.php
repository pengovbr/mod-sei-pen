<?php

class PaginaReciboTramite extends PaginaTeste
{
    public function __construct($test)
    {
        parent::__construct($test);
    }

    public function contemTramite($mensagemTramite, $verificaReciboEnvio=false, $verificaReciboConclusao=false)
    {
        $result = false;
        $this->test->frame(null);
        $this->test->frame("ifrVisualizacao");

        //Localiza colunas com dados da tramita��o
        $linhasResumoTramite = $this->test->elements($this->test->using('css selector')->value('div.infraAreaTabela > table tr'));
        if(count($linhasResumoTramite) > 0) {
            foreach ($linhasResumoTramite as $linha) {
                $colunas = $linha->elements($this->test->using('css selector')->value('td'));

                if(count($colunas) == 2){
                    //Verifica se tr�mite informado foi localizado no hist�rico
                    $result = strpos($colunas[0]->text(), $mensagemTramite) !== false;

                    //Verifica se recibo de envio do processo foi localizado
                    if($result && $verificaReciboEnvio) {
                        try{
                            $colunas[1]->element($this->test->using('css selector')->value(utf8_encode('a > img[title=\'Recibo de Confirma��o de Envio\']')));
                        }
                        catch(Exception $e){ $result = false; }
                    }

                    //Verifica se recibo de conclus�o do tr�mite processo foi localizado
                    if($result && $verificaReciboConclusao) {
                        try{
                            $colunas[1]->element($this->test->using('css selector')->value(utf8_encode('a > img[title=\'Recibo de Conclus�o de Tr�mite\']')));
                        }
                        catch(Exception $e){ $result = false; }
                    }

                    if($result) {
                        break;
                    }
                }

            }
        }

        return $result;
    }
}
