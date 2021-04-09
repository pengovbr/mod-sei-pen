<?php

require_once DIR_SEI_WEB.'/SEI.php';

class ProcessoEletronicoINT extends InfraINT {

    //Situação de cada uma das etapas da envio externo de processos
    const NEE_EXPEDICAO_ETAPA_PROCEDIMENTO = 1;
    const TEE_EXPEDICAO_ETAPA_VALIDACAO = 'Validando informações do processo...';
    const TEE_EXPEDICAO_ETAPA_PROCEDIMENTO = 'Enviando dados do processo %s';
    const TEE_EXPEDICAO_ETAPA_DOCUMENTO = 'Enviando documento %s';
    const TEE_EXPEDICAO_ETAPA_CONCLUSAO = 'Trâmite externo do processo finalizado com sucesso!';

    /**
     * Concate as siglas das hierarquias no nome da unidade
     *
     * @param array(EstruturaDTO) $estruturas
     * @return array
     */
    public static function gerarHierarquiaEstruturas($estruturas = array()){

        if(empty($estruturas)) {
            return $estruturas;
        }

        foreach($estruturas as &$estrutura) {

            if($estrutura->isSetArrHierarquia()) {
                $siglas = $estrutura->getArrHierarquia();
                $nome  = $estrutura->getStrNome();
                $nome .= ' - ';

                $array = array($estrutura->getStrSigla());
                foreach($estrutura->getArrHierarquia() as $sigla) {
                    if(trim($sigla) !== '' && !in_array($sigla, array('PR', 'PE', 'UNIAO'))) {
                        $array[] = $sigla;
                    }
                }

                $nome .= implode(' / ', $array);
                $estrutura->setStrNome($nome);
            }
        }

        return $estruturas;
    }

    public static function autoCompletarEstruturas($idRepositorioEstrutura, $strPalavrasPesquisa) {
        $objConecaoWebServerRN = new ProcessoEletronicoRN();
        $arrObjEstruturas = $objConecaoWebServerRN->listarEstruturas(
            $idRepositorioEstrutura,
            $strPalavrasPesquisa,
            null, null, null, null, null, true, null
        );

        return static::gerarHierarquiaEstruturas($arrObjEstruturas);
    }

    public static function autoCompletarProcessosApensados($dblIdProcedimentoAtual, $numIdUnidadeAtual, $strPalavrasPesquisa) {
        $objExpedirProcedimentoRN = new ExpedirProcedimentoRN();
        return $objExpedirProcedimentoRN->listarProcessosApensados($dblIdProcedimentoAtual, $numIdUnidadeAtual, $strPalavrasPesquisa);
    }


    public static function formatarHierarquia($ObjEstrutura)
    {
        $nome = "";

        if(isset($ObjEstrutura->hierarquia)) {
            
            $arrObjNivel = $ObjEstrutura->hierarquia->nivel;

            $siglasUnidades = array();
            $siglasUnidades[] = $ObjEstrutura->sigla;

            foreach($arrObjNivel as $key => $objNivel){
                $siglasUnidades[] = $objNivel->sigla  ;
            }

            for($i = 1; $i <= 3; $i++){
                if(isset($siglasUnidades[count($siglasUnidades) - 1])){
                    unset($siglasUnidades[count($siglasUnidades) - 1]);
                }
            }

            foreach($siglasUnidades as $key => $nomeUnidade){
                if($key == (count($siglasUnidades) - 1)){
                    $nome .= $nomeUnidade." ";
                }else{
                    $nome .= $nomeUnidade." / ";
                }
            }

            $objNivel=current($arrObjNivel);

        }
        $dados=["nome"=>$nome,"objNivel"=>$objNivel];

        return $dados;

    }

    
}
