<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaTramitarProcesso extends PaginaTeste
{
  public function __construct($test)
    {
      parent::__construct($test);

  }

  public function repositorio($siglaRepositorio)
    {
      $this->repositorioSelect = $this->test->select($this->test->byId('selRepositorioEstruturas'));

    if(isset($siglaRepositorio)){
        $this->repositorioSelect->selectOptionByLabel($siglaRepositorio);
    }

      return $this->test->byId('selRepositorioEstruturas')->value();
  }

  public function unidade($nomeUnidade, $hierarquia = null)
    {
    try{
        $this->test->frame(null);
        $this->test->frame("ifrConteudoVisualizacao");
        $this->test->frame("ifrVisualizacao");
        $this->unidadeInput =$this->test->byId('txtUnidade');
    }
    catch (Exception $e){
        $this->unidadeInput =$this->test->byId('txtUnidade');
    }

      $this->unidadeInput =$this->test->byId('txtUnidade');
      $this->unidadeInput->value($nomeUnidade);
      $this->test->keys(Keys::ENTER);
      $this->test->waitUntil(function($testCase) use($hierarquia) {
          $bolExisteAlerta=null;
          $nomeUnidade = $testCase->byId('txtUnidade')->value();
        if(!empty($hierarquia)){
            $nomeUnidade .= ' - ' . $hierarquia;
        }

        try{
            $bolExisteAlerta=$this->alertTextAndClose();
          if($bolExisteAlerta!=null) { $this->test->keys(Keys::ENTER);
          }
        }catch(Exception $e){
        }
          $testCase->byPartialLinkText($nomeUnidade)->click();
          return true;
      }, PEN_WAIT_TIMEOUT);

      return $this->unidadeInput->value();
  }

  public function urgente($urgente)
    {
      $this->urgenteCheck = $this->test->byId('chkSinUrgente');
    if(isset($urgente) && ((!$urgente && $this->urgenteCheck->selected()) || ($urgente && !$this->urgenteCheck->selected()))) {
        $this->urgenteCheck->click();
    }

      return $this->urgenteCheck->selected();
  }

  public function tramitar()
    {
      $tramitarButton = $this->test->byXPath("//button[@value='Enviar']");
      $tramitarButton->click();
  }

  public function fecharBarraProgresso()
    {
      $btnFechar = $this->test->byXPath("//input[@id='btnFechar']");
      $btnFechar->click();
  }

  public function unidadeInterna($nomeUnidade)
    {
      $this->test->frame(null);
      $this->test->frame("ifrConteudoVisualizacao");
      $this->test->frame("ifrVisualizacao");
      $this->unidadeInput =$this->test->byId('txtUnidade');
      $this->unidadeInput->value($nomeUnidade);
      //$this->test->keys(Keys::ENTER);
      $this->test->waitUntil(function($testCase) use($nomeUnidade) {
          $bolExisteAlerta=null;
          $nomeUnidade = $testCase->byId('txtUnidade')->value();
          sleep(1);
        try{
            $bolExisteAlerta=$this->alertTextAndClose();
          if($bolExisteAlerta!=null) { $this->test->keys(Keys::ENTER);
          }
        }catch(Exception $e){
        }
          $testCase->byPartialLinkText($nomeUnidade)->click();
          return true;
      }, PEN_WAIT_TIMEOUT);

        sleep(1);
        return $this->unidadeInput->value();
  }

  public function manterAbertoNaUnidadeAtual()
    {
      $manterAbertoCheckBox = $this->test->byXPath("//label[@id='lblSinManterAberto']");
      $manterAbertoCheckBox->click();
  }

  public function tramitarInterno()
    {
      $tramitarButton = $this->test->byXPath("//button[@value='Enviar']");
      $tramitarButton->click();
  }   

  public function alertTextAndClose($confirm = true)
    {
      sleep(10);
      $result = $this->test->alertText();
      $result = (!is_array($result) ? $result : null);

    if(isset($confirm) && $confirm) {
          $this->test->acceptAlert();
    } else {
          $this->dismissAlert();
    }
      return $result;
  } 
}
