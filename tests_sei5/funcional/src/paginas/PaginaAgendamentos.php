<?php

use PHPUnit\Extensions\Selenium2TestCase\Keys as Keys;

class PaginaAgendamentos extends PaginaTeste
{
    /**
     * Método contrutor
     * 
     * @return void
     */
  public function __construct($test)
    {
      parent::__construct($test);
  }

  public function navegarAgendamento()
    {
      $this->test->byId("txtInfraPesquisarMenu")->value(mb_convert_encoding('Agendamentos', 'UTF-8', 'ISO-8859-1'));
      $this->test->byXPath("//a[@link='infra_agendamento_tarefa_listar']")->click();
  }
    
  public function acaoAgendamento($strAgendamento, $acao)
    {
        
      $linhasAgendamentos = $this->test->elements($this->test->using('xpath')->value('//table[contains(@class, "infraTable")]/tbody/tr'));
      unset($linhasAgendamentos[0]);

    foreach($linhasAgendamentos as $idx => $linha) {
        $colunaComando = $linha->byXPath('./td[2]');

      if ($colunaComando->text() === $strAgendamento) {
        $this->test->byXPath("(//img[@title='$acao'])[$idx]")->click();
        $bolExisteAlerta = $this->alertTextAndClose();
        if ($bolExisteAlerta != null) { $this->test->keys(Keys::ENTER);
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