<?php

class AnexoProcessoFixture extends FixtureBase
{
    protected $objAssinaturaDTO;

    protected function inicializarObjInfraIBanco()
    {
        return \BancoSEI::getInstance();
    }

    protected function cadastrar($dados = [])
    {
        $objAtividadeFixture = new AtividadeFixture();
        $objAtividadeFixture->cadastrar(
            [
                'IdProtocolo' => $dados['IdProtocolo'],
                'IdTarefa' => \TarefaRN::$TI_ANEXADO_PROCESSO,//101 
            ]
        );
        $objAtividadeFixture->cadastrar(
            [
                'IdProtocolo' => $dados['IdDocumento'],
                'IdTarefa' => \TarefaRN::$TI_ANEXADO_AO_PROCESSO,//102
                'IdUsuarioConclusao' => $dados['IdUsuarioConclusao'] ?: 100000001,
                'Conclusao' => InfraData::getStrDataHoraAtual(),

            ]
        );
        $objAtividadeFixture->cadastrar(
            [
                'IdProtocolo' => $dados['IdDocumento'],
                'IdTarefa' => \TarefaRN::$TI_CONCLUSAO_AUTOMATICA_UNIDADE,//41
                'IdUsuarioConclusao' => $dados['IdUsuarioConclusao'] ?: 100000001,
                'Conclusao' => InfraData::getStrDataHoraAtual(),
            ]
        );
        
        $parametros = [
            'IdProtocolo' =>  $dados['IdProtocolo'], // idprotocolo1
            'IdDocumento' => $dados['IdDocumento'], // idprotocolo2 
            'Associacao' => 2,
        ];
        $objRelProtocoloProtocoloFixture = new RelProtocoloProtocoloFixture();
        $objRelProtocoloProtocoloFixtureDTO = $objRelProtocoloProtocoloFixture->carregar($parametros);
        return $objRelProtocoloProtocoloFixtureDTO;
      
    }
}