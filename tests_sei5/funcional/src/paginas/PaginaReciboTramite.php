<?php

class PaginaReciboTramite extends PaginaTeste
{
  public function __construct($test)
    {
      parent::__construct($test);
  }

  public function contemTramite($mensagemTramite, $verificaReciboEnvio = false, $verificaReciboConclusao = false)
    {
      $result = false;
      $this->test->frame(null);
      $this->test->frame("ifrConteudoVisualizacao");
      $this->test->frame("ifrVisualizacao");

      //Localiza colunas com dados da tramitação
      $linhasResumoTramite = $this->test->elements($this->test->using('css selector')->value('div.infraAreaTabela > table tr'));
    if(count($linhasResumoTramite) > 0) {
      foreach ($linhasResumoTramite as $linha) {
        $colunas = $linha->elements($this->test->using('css selector')->value('td'));

        if(count($colunas) == 2){
            //Verifica se trâmite informado foi localizado no histórico
            $result = strpos($colunas[0]->text(), $mensagemTramite) !== false;

            //Verifica se recibo de envio do processo foi localizado
          if($result && $verificaReciboEnvio) {
            try{
                  $to = $this->test->timeouts()->getLastImplicitWaitValue();
                  $this->test->timeouts()->implicitWait(300);
                  $colunas[1]->element($this->test->using('css selector')->value(mb_convert_encoding('a > img[title=\'Recibo de Confirmação de Envio\']', 'UTF-8', 'ISO-8859-1')));
            }
            catch(Exception $e){ $result = false; }
            finally{ $this->test->timeouts()->implicitWait($to); }
          }

            //Verifica se recibo de conclusão do trâmite processo foi localizado
          if($result && $verificaReciboConclusao) {
            try{
              $to = $this->test->timeouts()->getLastImplicitWaitValue();
              $this->test->timeouts()->implicitWait(300);
              $colunas[1]->element($this->test->using('css selector')->value(mb_convert_encoding('a > img[title=\'Recibo de Conclusão de Trâmite\']', 'UTF-8', 'ISO-8859-1')));
            }
            catch(Exception $e){ $result = false; }
            finally{ $this->test->timeouts()->implicitWait($to); }
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
