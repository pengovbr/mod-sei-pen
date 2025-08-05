<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;

class PaginaAgendamentos extends PaginaTeste
{
    /**
     * Método contrutor
     * 
     * @return void
     */
  public function __construct(RemoteWebDriver $driver, $testcase)
    {
      parent::__construct($driver, $testcase);
  }

  public function navegarAgendamento()
    {
      $this->elById("txtInfraPesquisarMenu")->sendKeys('Agendamentos');
      $this->elByXPath("//a[@link='infra_agendamento_tarefa_listar']")->click();
  }
    
  public function acaoAgendamento($strAgendamento, $acao)
    {
        
      $linhasAgendamentos = $this->elementsByXPath('//table[contains(@class, "infraTable")]/tbody/tr');
      unset($linhasAgendamentos[0]);

    foreach($linhasAgendamentos as $idx => $linha) {
        $colunaComando = $linha->findElement(WebDriverBy::xpath('./td[2]'));

      if ($colunaComando->getText() === $strAgendamento) {
        $this->elByXPath("(//img[@title='$acao'])[$idx]")->click();
        $bolExisteAlerta = $this->alertTextAndClose();
        if ($bolExisteAlerta != null) { 
            $this->driver->getKeyboard()->pressKey(WebDriverKeys::ENTER); 
        }
      }
    }

  }

  public function executarAgendamento($strAgendamento)
    {
      $this->acaoAgendamento($strAgendamento, 'Executar Agendamento');
      sleep(2);
  }

  public function desativarAgendamento($strAgendamento)
    {  
      $this->acaoAgendamento($strAgendamento, 'Desativar Agendamento');
      sleep(2);
  }

  public function reativarAgendamento($strAgendamento)
    {  
      $this->acaoAgendamento($strAgendamento, 'Reativar Agendamento');
      sleep(2);
  }

}
