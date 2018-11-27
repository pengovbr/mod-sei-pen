<?php

require_once dirname(__FILE__) . '/../../../SEI.php';

class ProcessoExpedidoRN extends InfraRN {

    public function __construct() {
        parent::__construct();
    }

    protected function inicializarObjInfraIBanco() {
        return BancoSEI::getInstance();
    }

    public function listarProcessoExpedido(ProtocoloDTO &$objProtocoloDTO) {

        $bolSqlServer = $this->getObjInfraIBanco() instanceof InfraSqlServer;
        $bolOracle = $this->getObjInfraIBanco() instanceof InfraOracle;
        $numLimit = $objProtocoloDTO->getNumMaxRegistrosRetorno();
        $numOffset = $objProtocoloDTO->getNumPaginaAtual() * $objProtocoloDTO->getNumMaxRegistrosRetorno();
        $strInstrucaoPaginacao = (!$bolSqlServer) ? "LIMIT ".$numOffset.",".$numLimit : "OFFSET $numOffset ROWS FETCH NEXT $numLimit ROWS ONLY";
        $strInstrucaoPaginacao = ($bolOracle) ? "" : $strInstrucaoPaginacao;

        $sql = "SELECT
                        p.id_protocolo,
                        p.protocolo_formatado,
                        a.id_unidade id_unidade,
                        atd.valor unidade_destino,
                        us.id_usuario id_usuario,
                        us.nome nome_usuario,
                        a.dth_abertura
               FROM protocolo p
               INNER JOIN atividade a ON a.id_protocolo = p.id_protocolo
               INNER JOIN atributo_andamento atd ON a.id_atividade = atd.id_atividade AND atd.nome = 'UNIDADE_DESTINO'
               INNER JOIN md_pen_processo_eletronico pe ON pe.id_procedimento = p.id_protocolo
               INNER JOIN md_pen_tramite ptra ON ptra.numero_registro = pe.numero_registro
               INNER JOIN usuario us ON ptra.id_usuario = us.id_usuario
               WHERE
                 p.sta_estado = " . $objProtocoloDTO->getStrStaEstado() . "
               AND
                       a.id_tarefa = ". ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_EXTERNO) ."
               AND
                       ptra.dth_registro = (SELECT MAX(pt.dth_registro) dth_registro FROM md_pen_tramite pt WHERE pt.numero_registro = pe.numero_registro)
               AND
               NOT EXISTS (
               SELECT at2.* FROM atividade at2
                WHERE at2.id_protocolo = p.id_protocolo
                AND at2.id_tarefa = ". ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO) ."
                AND at2.dth_abertura > a.dth_abertura )
               GROUP BY
                        p.id_protocolo, p.protocolo_formatado, a.id_unidade , atd.valor , us.id_usuario, us.nome, a.dth_abertura ORDER BY a.dth_abertura DESC ".$strInstrucaoPaginacao;


        if ($this->getObjInfraIBanco() instanceof InfraOracle){
            $qtd = $numLimit + $numLimit;
            $sql = "select a.* from ($sql) a where rownum >= $numOffset and rownum <= $qtd";
        }

        $sqlCount = "SELECT
                        count(*) total
               FROM protocolo p
               INNER JOIN atividade a ON a.id_protocolo = p.id_protocolo
               INNER JOIN atributo_andamento atd ON a.id_atividade = atd.id_atividade AND atd.nome = 'UNIDADE_DESTINO'
               INNER JOIN md_pen_processo_eletronico pe ON pe.id_procedimento = p.id_protocolo
               INNER JOIN md_pen_tramite ptra ON ptra.numero_registro = pe.numero_registro
               INNER JOIN usuario us ON ptra.id_usuario = us.id_usuario
               WHERE
                 p.sta_estado = " . $objProtocoloDTO->getStrStaEstado() . "
               AND
                       a.id_tarefa = ". ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_EXTERNO) ."
               AND
                       ptra.dth_registro = (SELECT MAX(pt.dth_registro) dth_registro FROM md_pen_tramite pt WHERE pt.numero_registro = pe.numero_registro)
               AND
               NOT EXISTS (
               SELECT at2.* FROM atividade at2
                WHERE at2.id_protocolo = p.id_protocolo
                AND at2.id_tarefa = ". ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO) ."
                AND at2.dth_abertura > a.dth_abertura ) ";

        //die($sql);
        $pag = $this->getObjInfraIBanco()->consultarSql($sql);
        $count = $this->getObjInfraIBanco()->consultarSql($sqlCount);
        $total = $count ? $count[0]['total'] : 0;

        $arrProcessosExpedidos = array();

         $objProtocoloDTO->setNumTotalRegistros($total);
         $objProtocoloDTO->setNumRegistrosPaginaAtual(count($pag));

        foreach ($pag as $res) {
            $data = BancoSEI::getInstance()->formatarLeituraDth($res['dth_abertura']);
            $objProcessoExpedidoDTO = new ProcessoExpedidoDTO();
            $objProcessoExpedidoDTO->setDblIdProtocolo($res['id_protocolo']);
            $objProcessoExpedidoDTO->setStrProtocoloFormatado($res['protocolo_formatado']);
            $objProcessoExpedidoDTO->setStrNomeUsuario($res['nome_usuario']);
            $objProcessoExpedidoDTO->setDthExpedido($data);
            $objProcessoExpedidoDTO->setStrDestino($res['unidade_destino']);

            $arrProcessosExpedidos[] = $objProcessoExpedidoDTO;
        }

        return $arrProcessosExpedidos;
    }
}
