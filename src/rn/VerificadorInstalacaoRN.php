<?php

require_once DIR_SEI_WEB . '/SEI.php';


/**
 * Classe respons�vel pela verifica��o da correta��o instala��o e configura��o do m�dulo no sistema
 *
 * A verifica��o cobre os aspectos t�cnicos como tamb�m os neg�ciais realizados pelos administradores do SEI via interface da sistema
 *
 * VERIFICA��ES T�CNICAS DA INSTALA��O
 * [] Verificar o correto posicionamento dos arquivos ap�s instala��o
 * [] Verificar se o m�dulo foi ativado no arquivo de configura��es do sistema
 * [] Verificar se os par�metros obrigat�rios foram devidamente configurados
 * [] Verifica se o endere�o do webservice � valido
 * [] Verifica se o arquivo de certificado digital configurado existe
 * [] Verificar se a base de dados do SEI foi atualizada corretamente
 * [] Verificar se a base de dados do SIP foi atualizada corretamente
 * [] Verificar a conex�o com o Barramento de Servi�os do PEN
 *
 * VERIFICA��ES NEGOCIAIS
 * [] Verificar se os par�metros obrigat�rios do sistema foram definidos em Par�metros de Configura��es
 * [] Verificar se o mapeamento de pelo menos uma unidade foi realizado
 * [] Verificar se o mapeamento de esp�cies documentais foi realizado
 * [] Verificar se a Esp�cie Documental padr�o para envio foi corretamente definida
 * [] Verificar se o Tipo de Documento padr�o para recebimento foi corretamente definido
 * [] Verificar se o mapeamento de hip�teses legais foi realizado
 * [] Verificar se a Hip�tese Legal padr�o foi corretamente definida
 */
class VerificadorInstalacaoRN extends InfraRN
{
    const COMPATIBILIDADE_MODULO_SEI = [
      '5.0.0',
      '5.0.1'
    ];

    public function __construct()
    {
      parent::__construct();
    }

    protected function inicializarObjInfraIBanco()
    {
      return BancoSEI::getInstance();
    }

    /**
     * Verifica se todos os arquivos do m�dulo foram posicionados nos locais corretos
     *
     * @return bool
     */
    public function verificarPosicionamentoScripts()
    {
      $this->verificarExistenciaArquivo(DIR_SEI_WEB . '/../scripts/mod-pen/sei_atualizar_versao_modulo_pen.php');
      $this->verificarExistenciaArquivo(DIR_SEI_WEB . '/../scripts/mod-pen/verifica_instalacao_modulo_pen.php');
      $this->verificarExistenciaArquivo(DIR_SEI_WEB . '/../scripts/mod-pen/MonitoramentoEnvioTarefasPEN.php');
      $this->verificarExistenciaArquivo(DIR_SEI_WEB . '/../scripts/mod-pen/MonitoramentoRecebimentoTarefasPEN.php');
      $this->verificarExistenciaArquivo(DIR_SEI_WEB . '/../scripts/mod-pen/ProcessamentoTarefasPEN.php');
      $this->verificarExistenciaArquivo(DIR_SEI_WEB . '/../config/mod-pen/ConfiguracaoModPEN.php');
      $this->verificarExistenciaArquivo(DIR_SEI_WEB . '/../bin/mod-pen/verificar-reboot-fila.sh');
      $this->verificarExistenciaArquivo(DIR_SEI_WEB . '/../bin/mod-pen/verificar-pendencias-represadas.py');
      return true;
    }


    /**
     * Verifica se o m�dulo foi devidamente ativado nas configura��es do sistema
     *
     * @return bool
     */
    public function verificarAtivacaoModulo()
    {
      global $SEI_MODULOS;

      if(!array_key_exists("PENIntegracao", $SEI_MODULOS)) {
        throw new InfraException("M�dulo do Tramita: Chave de ativa��o do m�dulo mod-sei-pen (PENIntegracao) n�o definido nas configura��es de m�dulos do SEI");
      }

      if(is_null($SEI_MODULOS['PENIntegracao'])) {
        $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();

        if (!$objConfiguracaoSEI->isSetValor('SEI', 'Modulos')) {
          throw new InfraException("M�dulo do Tramita: Chave de configura��o de M�dulos n�o definida nas configura��es do sistema. (ConfiguracaoSEI.php | SEI > Modulos)");
        }

        $arrModulos = $objConfiguracaoSEI->getValor('SEI', 'Modulos');
        $strDiretorioModPEN = basename($arrModulos['PENIntegracao']);
        $strDiretorioModulos = dirname($arrModulos['PENIntegracao']);
        throw new InfraException("M�dulo do Tramita: Diret�rio do m�dulo ($strDiretorioModPEN) n�o pode ser localizado em $strDiretorioModulos");
      }

      return true;
    }


    /**
     * Verifica a correta defini��o de todos os par�metros de configura��o do m�dulo
     *
     * @return bool
     */
    public function verificarArquivoConfiguracao()
    {
      // Valida se arquivo de configura��o est� presente na instala��o do sistema
      $strArquivoConfiguracao = DIR_SEI_CONFIG . '/mod-pen/ConfiguracaoModPEN.php';
      if (file_exists($strArquivoConfiguracao) && is_readable($strArquivoConfiguracao)) {
        include_once DIR_SEI_CONFIG . '/mod-pen/ConfiguracaoModPEN.php';
      } else {
        $strMensagem = "Arquivo de configura��o do m�dulo de integra��o do SEI com o Tramita GOV.BR n�o pode ser localizado";
        $strDetalhes = "As configura��es do m�dulo mod-sei-pen n�o foram encontradas em $strArquivoConfiguracao \n";
        $strDetalhes .= "Verifique se a instala��o foi feita corretamente seguindo os procedimentos do manual de instala��o.";
        throw new InfraException($strMensagem, null, $strDetalhes);
      }

      // Valida se arquivo de configura��o est� �ntegro e se a classe de configura��o est� presente
      if(!class_exists("ConfiguracaoModPEN")) {
        $strMensagem = "Defini��o de configura��es do m�dulo de integra��o do SEI com o Tramita GOV.BR n�o pode ser localizada";
        $strDetalhes = "Verifique se o arquivo de configura��o localizado em $strArquivoConfiguracao encontra-se �ntegro.";
        throw new InfraException($strMensagem, null, $strDetalhes);
      }

      // Valida se todos os par�metros de configura��o est�o presentes no arquivo de configura��o
      $arrStrChavesConfiguracao = ConfiguracaoModPEN::getInstance()->getArrConfiguracoes();
      if(!array_key_exists("PEN", $arrStrChavesConfiguracao)) {
        $strMensagem = "Grupo de parametriza��o 'Tramita GOV.BR' n�o pode ser localizado no arquivo de configura��o do m�dulo de integra��o do SEI com o Tramita GOV.BR";
        $strDetalhes = "Verifique se o arquivo de configura��o localizado em $strArquivoConfiguracao encontra-se �ntegro.";
        throw new InfraException($strMensagem, null, $strDetalhes);
      }


      // Valida se todas as chaves de configura��o obrigat�rias foram atribu�das
      $arrStrChavesConfiguracao = $arrStrChavesConfiguracao["PEN"];
      $arrStrParametrosExperados = ["WebService", "LocalizacaoCertificado", "SenhaCertificado"];
      foreach ($arrStrParametrosExperados as $strChaveConfiguracao) {
        if(!array_key_exists($strChaveConfiguracao, $arrStrChavesConfiguracao)) {
          $strMensagem = "Par�metro 'PEN > $strChaveConfiguracao' n�o pode ser localizado no arquivo de configura��o do m�dulo de integra��o do SEI com o Tramita GOV.BR";
          $strDetalhes = "Verifique se o arquivo de configura��o localizado em $strArquivoConfiguracao encontra-se �ntegro.";
          throw new InfraException($strMensagem, null, $strDetalhes);
        }
      }

      return true;
    }


    /**
     * Verifica a compatibilidade da vers�o do m�dulo com a atual vers�o do SEI em que est� sendo feita a instala��o
     *
     * @return bool
     */
    public function verificarCompatibilidadeModulo()
    {
      $strVersaoSEI = SEI_VERSAO;
      if(!in_array($strVersaoSEI, self::COMPATIBILIDADE_MODULO_SEI)) {
        $objPENIntegracao = new PENIntegracao();
        $strMensagem = sprintf("M�dulo %s (vers�o %s) n�o � compat�vel com a vers�o %s do SEI.", $objPENIntegracao->getNome(), $objPENIntegracao->getVersao(), $strVersaoSEI);
        throw new ModuloIncompativelException($strMensagem);
      }

      return true;
    }

    /**
     * M�todo respons�vel pela valida��o da compatibilidade do banco de dados do m�dulo em rela��o ao vers�o instalada
     *
     * @param  boolean $bolGerarExcecao Flag para gera��o de exce��o do tipo InfraException caso base de dados incompat�vel
     * @return boolean                  Indicardor se base de dados � compat�vel
     */
    public function verificarCompatibilidadeBanco()
    {
      $objInfraParametro = new InfraParametro(BancoSEI::getInstance());
      $strVersaoBancoModulo = $objInfraParametro->getValor(PENIntegracao::PARAMETRO_VERSAO_MODULO, false) ?: $objInfraParametro->getValor(PENIntegracao::PARAMETRO_VERSAO_MODULO_ANTIGO, false);

      $objPENIntegracao = new PENIntegracao();
      $strVersaoModulo = $objPENIntegracao->getVersao();

      if($strVersaoModulo !== $strVersaoBancoModulo) {
        $strMensagem = sprintf(
            "Base de dados do m�dulo '%s' (vers�o %s) encontra-se incompat�vel. A vers�o da base de dados atualmente instalada � a %s. \n ".
            "Favor entrar em contato com o administrador do sistema.", $objPENIntegracao->getNome(), $strVersaoModulo, $strVersaoBancoModulo
          );

          throw new ModuloIncompativelException($strMensagem);
      }

      return true;
    }

    /**
     * Verifica a valida��o do Certificado Digital, verificando sua localiza��o e a valida��o das senhas de criptografia
     *
     * @return bool
     */
    public function verificarCertificadoDigital()
    {
      $objConfiguracaoModPEN = ConfiguracaoModPEN::getInstance();
      $strLocalizacaoCertificadoDigital = $objConfiguracaoModPEN->getValor("PEN", "LocalizacaoCertificado");
      $strSenhaCertificadoDigital = $objConfiguracaoModPEN->getValor("PEN", "SenhaCertificado");

      if(!file_exists($strLocalizacaoCertificadoDigital)) {
        $strNomeCertificado = basename($parStrLocalizacaoArquivo);
        $strDiretorioCertificado = dirname($parStrLocalizacaoArquivo);
        throw new InfraException("M�dulo do Tramita: Certificado digital $strNomeCertificado n�o pode ser localizado em $strDiretorioCertificado");
      }

      $strLocalizacaoAjustada = 'file://' . $strLocalizacaoCertificadoDigital;
      $strPublicKey = openssl_pkey_get_public($strLocalizacaoAjustada);
      if(empty($strPublicKey)) {
        throw new InfraException("M�dulo do Tramita: Chave p�blica do certificado digital de autentica��o no Tramita GOV.BR n�o pode ser localizada em $strLocalizacaoCertificadoDigital. Erro detalhado: " . openssl_error_string());
      }

      $strPrivateKey = openssl_pkey_get_private($strLocalizacaoAjustada, $strSenhaCertificadoDigital);
      if(empty($strPrivateKey)) {
        throw new InfraException("M�dulo do Tramita: Chave privada do certificado digital de autentica��o no Tramita GOV.BR n�o pode ser extra�da em $strLocalizacaoCertificadoDigital. Erro detalhado: " . openssl_error_string());
      }

      return true;
    }


    /**
     * Verifica a conex�o com o Barramento de Servi�os do PEN, utilizando o endere�o e certificados informados
     *
     * @return bool
     */
    public function verificarConexaoBarramentoPEN()
    {
      $objConfiguracaoModPEN = ConfiguracaoModPEN::getInstance();
      $strEnderecoWebService = $objConfiguracaoModPEN->getValor("PEN", "WebService");
      $strLocalizacaoCertificadoDigital = $objConfiguracaoModPEN->getValor("PEN", "LocalizacaoCertificado");
      $strSenhaCertificadoDigital = $objConfiguracaoModPEN->getValor("PEN", "SenhaCertificado");

      $healthcheck = 'healthcheck';

      $strEnderecoBarramento = $strEnderecoWebService . '/' . $healthcheck;
      $curl = curl_init($strEnderecoBarramento);

      try{
        $bolEmProducao = boolval(ConfiguracaoSEI::getInstance()->getValor('SEI', 'Producao'));
        curl_setopt($curl, CURLOPT_URL, $strEnderecoBarramento);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $bolEmProducao);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $bolEmProducao);
        curl_setopt($curl, CURLOPT_SSLCERT, $strLocalizacaoCertificadoDigital);
        curl_setopt($curl, CURLOPT_SSLCERTPASSWD, $strSenhaCertificadoDigital);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);

        $strOutput = curl_exec($curl);

        if (curl_errno($curl)) {
          $strErrorMsg = curl_error($curl);
        }
        if (isset($strErrorMsg)) {
          throw new Exception("Erro no CURL para $strEnderecoBarramento. Erro detalhado: $strErrorMsg.");
        }

      } finally{
        curl_close($curl);
      }

      return true;
    }


    /**
     * Verifica a conex�o com o Barramento de Servi�os do PEN, utilizando o endere�o e certificados informados
     *
     * @return bool
     */
    public function verificarAcessoPendenciasTramitePEN()
    {
      // Processa uma chamada ao Barramento de Servi�os para certificar que o atual certificado est� corretamente vinculado � um
      // comit� de protocolo v�lido
      try{
        $objProcessoEletronicoRN = new ProcessoEletronicoRN();
        $objProcessoEletronicoRN->listarPendencias(false);
          
        return true;
      } catch(Exception $e){
        throw new InfraException("M�dulo do Tramita: Falha no acesso aos servi�os de integra��o do Tramita GOV.BR: $e");
      }
    }

    /**
     * Verifica se Gearman foi corretamente configurado e se o mesmo se encontra ativo
     *
     * @return bool
     */
    public function verificarConfiguracaoGearman()
    {
      $objConfiguracaoModPEN = ConfiguracaoModPEN::getInstance();
      $arrObjGearman = $objConfiguracaoModPEN->getValor("PEN", "Gearman", false);
      $strGearmanServidor = trim(@$arrObjGearman["Servidor"] ?: null);
      $strGearmanPorta = trim(@$arrObjGearman["Porta"] ?: null);

      if(empty($strGearmanServidor)) {
        // N�o processa a verifica��o da instala��o do Gearman caso n�o esteja configurado
        return false;
      }

      if(!class_exists("GearmanClient")) {
        throw new InfraException(
            "M�dulo do Tramita: N�o foi poss�vel localizar as bibliotecas do PHP para conex�o ao GEARMAN." .
            "Verifique os procedimentos de instala��o do mod-sei-pen para maiores detalhes"
          );
      }

      try{
        $objGearmanClient = new GearmanClient();
        $objGearmanClient->addServer($strGearmanServidor, $strGearmanPorta);
        $objGearmanClient->setTimeout(10000);
        $objGearmanClient->ping("health");
      } catch (\Exception $e) {
        $strMensagemErro = "N�o foi poss�vel conectar ao servidor Gearman (%s, %s). Erro: %s";
        $strMensagem = sprintf($strMensagemErro, $strGearmanServidor, $strGearmanPorta, $objGearmanClient->error());
        throw new InfraException($strMensagem);
      }

      return true;
    }

    private function verificarExistenciaArquivo($parStrLocalizacaoArquivo)
    {
      if(!file_exists($parStrLocalizacaoArquivo)) {
        $strNomeArquivo = basename($parStrLocalizacaoArquivo);
        $strDiretorioArquivo = dirname($parStrLocalizacaoArquivo);
        throw new InfraException("M�dulo do Tramita: Arquivo do $strNomeArquivo n�o pode ser localizado em $strDiretorioArquivo");
      }
    }
}
