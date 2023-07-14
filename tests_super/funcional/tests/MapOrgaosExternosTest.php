<?php

/**
 * Testes de trâmite de processos em lote
 *
 * Este mesmo documento deve ser recebido e assinalado como cancelado no destinatário e
 * a devolução do mesmo processo não deve ser impactado pela inserção de outros documentos
 */
class MapOrgaosExternosTest extends CenarioBaseTestCase
{
    public static $remetente;

    /**
     * Teste inicial de trâmite de um processo contendo um documento movido
     *
     * @group envio
     *
     * @return void
     */
    public function test_map_orgaos_externos_reativar()
    {

        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);

        $this->acessarSistema(self::$remetente['URL'], self::$remetente['SIGLA_UNIDADE'], self::$remetente['LOGIN'], self::$remetente['SENHA']);
        
        $this->navegarPara("pen_map_orgaos_externos_listar");
        $this->paginaMapOrgaosExternosListar->reativarMapOrgaosExterno();
        sleep(15);

    }
}
