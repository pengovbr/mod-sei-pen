<?php

use Facebook\WebDriver\Remote\RemoteWebDriver;

class PaginaConsultarAndamentos extends PaginaTeste
{
    public function __construct(RemoteWebDriver $driver, $testcase)
    {
        parent::__construct($driver, $testcase);
    }

    /**
     * Verifica se a visualiza��o cont�m a mensagem de tr�mite gen�rica
     */
    public function contemTramite(string $mensagemTramite): bool
    {
        $texto = $this->getConteudoBody();
        $mensagem = mb_convert_encoding($mensagemTramite, 'UTF-8', 'ISO-8859-1');
        return strpos($texto, $mensagem) !== false;
    }

    /**
     * Verifica se processo est� em tramita��o externa para determinada unidade
     */
    public function contemTramiteProcessoEmTramitacao(string $destino): bool
    {
        $texto = $this->getVisualizacaoBody();
        $mensagem = "Processo em tramita��o externa para {$destino}";
        $mensagem = mb_convert_encoding($mensagem, 'UTF-8', 'ISO-8859-1');
        return strpos($texto, $mensagem) !== false;
    }

    /**
     * Verifica se processo foi recebido por determinada unidade
     */
    public function contemTramiteProcessoRecebido(string $destino): bool
    {
        $texto = $this->getVisualizacaoBody();
        $mensagem = "Recebido em {$destino}";
        $mensagem = mb_convert_encoding($mensagem, 'UTF-8', 'ISO-8859-1');
        return strpos($texto, $mensagem) !== false;
    }

    /**
     * Verifica se processo foi rejeitado por unidade e motivo informados
     */
    public function contemTramiteProcessoRejeitado(string $destino, string $motivo): bool
    {
        $texto = $this->getVisualizacaoBody();
        $mensagem = "O processo foi recusado pelo org�o {$destino} pelo seguinte motivo: {$motivo}";
        $mensagem = mb_convert_encoding($mensagem, 'UTF-8', 'ISO-8859-1');
        return strpos($texto, $mensagem) !== false;
    }
}
