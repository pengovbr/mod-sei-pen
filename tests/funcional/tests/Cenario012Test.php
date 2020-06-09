<?php

use \utilphp\util;

class Cenario012Test extends CenarioBaseTestCase
{
    public function teste_cancelamento_tramite_com_recibo_pendente()    
    {
        $this->markTestIncomplete(
          '
Teste não pode ser feito automaticamente pois é necessário forçar o não envio do recibo de conclusão do trâmite 
pelo destinatário de tal forma que o trâmite possa ser cancelado no momento correto.
Por enquanto, necessário realizar o teste de forma manual.'
        );
    }    
}
