<?php

use \utilphp\util;

class Cenario020Test extends CenarioBaseTestCase
{

    public function teste_cancelamento_tramite_com_recibo_pendente()    
    {
        $this->markTestIncomplete(
          '
Teste não pode ser feito automaticamente pois é necessário forçar um erro não tratável no sistema de destino, como falta de permissão para armazenamento dos documentos, estouro da capacidade do disco, etc.

Por enquanto, necessário realizar o teste de forma manual.'
        );
    }    
}
