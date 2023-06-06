<?php

require_once DIR_SEI_WEB . '/SEI.php';

/**
 * Description of TramiteEmBloco
 *
 * Tramitar em bloco
 */
class TramiteEmBlocoRN extends InfraRN
{
    /**
     * Inicializa o obj do banco da Infra
     * @return obj
     */
    protected function inicializarObjInfraIBanco()
    {
        return BancoSEI::getInstance();
    }

    /**
     * Método utilizado para exclusão de dados.
     * @param TramiteEmBlocoDTO $objDTO
     * @return array
     * @throws InfraException
     */
    protected function excluirControlado(TramiteEmBlocoDTO $objDTO)
    {
        try {
            $objBD = new TramiteEmBlocoBD(BancoSEI::getInstance());
            return $objBD->excluir($objDTO);
        } catch (Exception $e) {
            throw new InfraException('Erro excluindo mapeamento de unidades.', $e);
        }
    }
}
