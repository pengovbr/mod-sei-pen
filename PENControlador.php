<?php

class PENControlador implements ISeiControlador {

    public function processar($strAcao) {

        switch ($strAcao) {
            case 'pen_procedimento_expedir':
                require_once dirname(__FILE__) . '/pen_procedimento_expedir.php';
                return true;
            //TODO: Alterar nome do recurso para pen_procedimento_expedir_unidade_sel
            case 'pen_unidade_sel_expedir_procedimento':
                require_once dirname(__FILE__) . '/pen_unidade_sel_expedir_procedimento.php';
                return true;

            case 'pen_procedimento_processo_anexado':
                require_once dirname(__FILE__) . '/pen_procedimento_processo_anexado.php';
                return true;

            case 'pen_procedimento_cancelar_expedir':
                require_once dirname(__FILE__) . '/pen_procedimento_cancelar_expedir.php';
                return true;
                    
            case 'pen_procedimento_expedido_listar':
                require_once dirname(__FILE__) . '/pen_procedimento_expedido_listar.php';
                return true;     
            
            case 'pen_map_tipo_documento_envio_listar':
            case 'pen_map_tipo_doc_enviado_excluir':
            case 'pen_map_tipo_doc_enviado_desativar':
            case 'pen_map_tipo_doc_enviado_ativar':
                require_once dirname(__FILE__) . '/pen_map_tipo_documento_envio_listar.php';
                return true;
            
            case 'pen_map_tipo_documento_envio_cadastrar':
            case 'pen_map_tipo_documento_enviador_alterar':
                require_once dirname(__FILE__) . '/pen_map_tipo_documento_envio_cadastrar.php';
                return true;

            case 'pen_map_tipo_documento_recebimento_listar':
            case 'pen_map_tipo_documento_recebimento_excluir':
                require_once dirname(__FILE__) . '/pen_map_tipo_documento_recebimento_listar.php';
                return true;

            case 'pen_map_tipo_documento_recebimento_cadastrar':
            case 'pen_map_tipo_documento_recebimento_alterar':
                require_once dirname(__FILE__) . '/pen_map_tipo_documento_recebimento_cadastrar.php';
                return true;
                                
            case 'apensados_selecionar_expedir_procedimento':
                require_once dirname(__FILE__) . '/apensados_selecionar_expedir_procedimento.php';
                return true;
                    
            case 'pen_procedimento_estado':
                require_once dirname(__FILE__) . '/pen_procedimento_estado.php';
                return true;
                
            

        }

        return false;
    }
}