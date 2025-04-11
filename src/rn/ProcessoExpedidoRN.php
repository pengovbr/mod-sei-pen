<?php

require_once DIR_SEI_WEB.'/SEI.php';

class ProcessoExpedidoRN extends InfraRN
{

  public function __construct()
    {
      parent::__construct();
  }

  protected function inicializarObjInfraIBanco()
    {
      return BancoSEI::getInstance();
  }

  public function listarProcessoExpedido(ProtocoloDTO &$objProtocoloDTO)
    {
      $numLimit = $objProtocoloDTO->getNumMaxRegistrosRetorno();
      $numOffset = $objProtocoloDTO->getNumPaginaAtual() * $objProtocoloDTO->getNumMaxRegistrosRetorno();
      $numIdUnidade = SessaoSEI::getInstance()->getNumIdUnidadeAtual();


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
                    p.sta_estado = '" . $objProtocoloDTO->getStrStaEstado() . "'
               AND
                    a.id_tarefa = ". ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_EXTERNO) ."
               AND
                    a.id_unidade = $numIdUnidade
               AND
                    ptra.dth_registro = (SELECT MAX(pt.dth_registro) dth_registro FROM md_pen_tramite pt WHERE pt.numero_registro = pe.numero_registro)
               AND
               NOT EXISTS (
               SELECT at2.* FROM atividade at2
                WHERE at2.id_protocolo = p.id_protocolo
                AND at2.id_tarefa = ". ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO) ."
                AND at2.dth_abertura > a.dth_abertura )
               GROUP BY
                p.id_protocolo, p.protocolo_formatado, a.id_unidade , atd.valor , us.id_usuario, us.nome, a.dth_abertura ORDER BY a.dth_abertura DESC ";
      ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_TRAMITE_EXTERNO);
      ProcessoEletronicoRN::obterIdTarefaModulo(ProcessoEletronicoRN::$TI_PROCESSO_ELETRONICO_PROCESSO_RECEBIDO);
      $objProtocoloDTO->getStrStaEstado();

      $objPaginacao = $this->getObjInfraIBanco()->paginarSql($sql, $numOffset, $numLimit);
      $total = $objPaginacao['totalRegistros'];

      $arrProcessosExpedidos = [];
      $objProtocoloDTO->setNumTotalRegistros($total);
      $objProtocoloDTO->setNumRegistrosPaginaAtual($total);

    foreach ($objPaginacao['registrosPagina'] as $res) {
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
