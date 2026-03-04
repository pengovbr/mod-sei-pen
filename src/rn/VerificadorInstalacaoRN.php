<?php

require_once DIR_SEI_WEB . '/SEI.php';


/**
 * Classe responsável pela verificação da corretação instalação e configuração do módulo no sistema
 *
 * A verificação cobre os aspectos técnicos como também os negóciais realizados pelos administradores do SEI via interface da sistema
 *
 * VERIFICAÇÕES TÉCNICAS DA INSTALAÇÃO
 * [] Verificar o correto posicionamento dos arquivos após instalação
 * [] Verificar se o módulo foi ativado no arquivo de configurações do sistema
 * [] Verificar se os parâmetros obrigatórios foram devidamente configurados
 * [] Verifica se o endereço do webservice é valido
 * [] Verifica se o arquivo de certificado digital configurado existe
 * [] Verificar se a base de dados do SEI foi atualizada corretamente
 * [] Verificar se a base de dados do SIP foi atualizada corretamente
 * [] Verificar a conexão com o Barramento de Serviços do PEN
 *
 * VERIFICAÇÕES NEGOCIAIS
 * [] Verificar se os parâmetros obrigatórios do sistema foram definidos em Parâmetros de Configurações
 * [] Verificar se o mapeamento de pelo menos uma unidade foi realizado
 * [] Verificar se o mapeamento de espécies documentais foi realizado
 * [] Verificar se a Espécie Documental padrão para envio foi corretamente definida
 * [] Verificar se o Tipo de Documento padrão para recebimento foi corretamente definido
 * [] Verificar se o mapeamento de hipóteses legais foi realizado
 * [] Verificar se a Hipótese Legal padrão foi corretamente definida
 */
class VerificadorInstalacaoRN extends InfraRN
{
    const COMPATIBILIDADE_MODULO_SEI = [
      '5.0.0',
      '5.0.1',
      '5.0.2',
      '5.0.3',
      '5.1.0'
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
     * Verifica se todos os arquivos do módulo foram posicionados nos locais corretos
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
     * Verifica se o módulo foi devidamente ativado nas configurações do sistema
     *
     * @return bool
     */
    public function verificarAtivacaoModulo()
    {
      global $SEI_MODULOS;

      if(!array_key_exists("PENIntegracao", $SEI_MODULOS)) {
        throw new InfraException("Módulo do Tramita: Chave de ativação do módulo mod-sei-pen (PENIntegracao) não definido nas configurações de módulos do SEI");
      }

      if(is_null($SEI_MODULOS['PENIntegracao'])) {
        $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();

        if (!$objConfiguracaoSEI->isSetValor('SEI', 'Modulos')) {
          throw new InfraException("Módulo do Tramita: Chave de configuração de Módulos não definida nas configurações do sistema. (ConfiguracaoSEI.php | SEI > Modulos)");
        }

        $arrModulos = $objConfiguracaoSEI->getValor('SEI', 'Modulos');
        $strDiretorioModPEN = basename($arrModulos['PENIntegracao']);
        $strDiretorioModulos = dirname($arrModulos['PENIntegracao']);
        throw new InfraException("Módulo do Tramita: Diretório do módulo ($strDiretorioModPEN) não pode ser localizado em $strDiretorioModulos");
      }

      return true;
    }


    /**
     * Verifica a correta definição de todos os parâmetros de configuração do módulo
     *
     * @return bool
     */
    public function verificarArquivoConfiguracao()
    {
      // Valida se arquivo de configuração está presente na instalação do sistema
      $strArquivoConfiguracao = DIR_SEI_CONFIG . '/mod-pen/ConfiguracaoModPEN.php';
      if (file_exists($strArquivoConfiguracao) && is_readable($strArquivoConfiguracao)) {
        include_once DIR_SEI_CONFIG . '/mod-pen/ConfiguracaoModPEN.php';
      } else {
        $strMensagem = "Arquivo de configuração do módulo de integração do SEI com o Tramita GOV.BR não pode ser localizado";
        $strDetalhes = "As configurações do módulo mod-sei-pen não foram encontradas em $strArquivoConfiguracao \n";
        $strDetalhes .= "Verifique se a instalação foi feita corretamente seguindo os procedimentos do manual de instalação.";
        throw new InfraException($strMensagem, null, $strDetalhes);
      }

      // Valida se arquivo de configuração está íntegro e se a classe de configuração está presente
      if(!class_exists("ConfiguracaoModPEN")) {
        $strMensagem = "Definição de configurações do módulo de integração do SEI com o Tramita GOV.BR não pode ser localizada";
        $strDetalhes = "Verifique se o arquivo de configuração localizado em $strArquivoConfiguracao encontra-se íntegro.";
        throw new InfraException($strMensagem, null, $strDetalhes);
      }

      // Valida se todos os parâmetros de configuração estão presentes no arquivo de configuração
      $arrStrChavesConfiguracao = ConfiguracaoModPEN::getInstance()->getArrConfiguracoes();
      if(!array_key_exists("PEN", $arrStrChavesConfiguracao)) {
        $strMensagem = "Grupo de parametrização 'Tramita GOV.BR' não pode ser localizado no arquivo de configuração do módulo de integração do SEI com o Tramita GOV.BR";
        $strDetalhes = "Verifique se o arquivo de configuração localizado em $strArquivoConfiguracao encontra-se íntegro.";
        throw new InfraException($strMensagem, null, $strDetalhes);
      }


      // Valida se todas as chaves de configuração obrigatórias foram atribuídas
      $arrStrChavesConfiguracao = $arrStrChavesConfiguracao["PEN"];
      $arrStrParametrosExperados = ["WebService", "LocalizacaoCertificado", "SenhaCertificado"];
      foreach ($arrStrParametrosExperados as $strChaveConfiguracao) {
        if(!array_key_exists($strChaveConfiguracao, $arrStrChavesConfiguracao)) {
          $strMensagem = "Parâmetro 'PEN > $strChaveConfiguracao' não pode ser localizado no arquivo de configuração do módulo de integração do SEI com o Tramita GOV.BR";
          $strDetalhes = "Verifique se o arquivo de configuração localizado em $strArquivoConfiguracao encontra-se íntegro.";
          throw new InfraException($strMensagem, null, $strDetalhes);
        }
      }

      return true;
    }


    /**
     * Verifica a compatibilidade da versão do módulo com a atual versão do SEI em que está sendo feita a instalação
     *
     * @return bool
     */
    public function verificarCompatibilidadeModulo()
    {
      $strVersaoSEI = SEI_VERSAO;
      if(!in_array($strVersaoSEI, self::COMPATIBILIDADE_MODULO_SEI)) {
        $objPENIntegracao = new PENIntegracao();
        $strMensagem = sprintf("Módulo %s (versão %s) não é compatível com a versão %s do SEI.", $objPENIntegracao->getNome(), $objPENIntegracao->getVersao(), $strVersaoSEI);
        throw new ModuloIncompativelException($strMensagem);
      }

      return true;
    }

    /**
     * Método responsável pela validação da compatibilidade do banco de dados do módulo em relação ao versão instalada
     *
     * @param  boolean $bolGerarExcecao Flag para geração de exceção do tipo InfraException caso base de dados incompatível
     * @return boolean                  Indicardor se base de dados é compatível
     */
    public function verificarCompatibilidadeBanco()
    {
      $objInfraParametro = new InfraParametro(BancoSEI::getInstance());
      $strVersaoBancoModulo = $objInfraParametro->getValor(PENIntegracao::PARAMETRO_VERSAO_MODULO, false) ?: $objInfraParametro->getValor(PENIntegracao::PARAMETRO_VERSAO_MODULO_ANTIGO, false);

      $objPENIntegracao = new PENIntegracao();
      $strVersaoModulo = $objPENIntegracao->getVersao();

      if($strVersaoModulo !== $strVersaoBancoModulo) {
        $strMensagem = sprintf(
            "Base de dados do módulo '%s' (versão %s) encontra-se incompatível. A versão da base de dados atualmente instalada é a %s. \n ".
            "Favor entrar em contato com o administrador do sistema.", $objPENIntegracao->getNome(), $strVersaoModulo, $strVersaoBancoModulo
          );

          throw new ModuloIncompativelException($strMensagem);
      }

      return true;
    }

    /**
     * Verifica a validação do Certificado Digital, verificando sua localização e a validação das senhas de criptografia
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
        throw new InfraException("Módulo do Tramita: Certificado digital $strNomeCertificado não pode ser localizado em $strDiretorioCertificado");
      }

      $strLocalizacaoAjustada = 'file://' . $strLocalizacaoCertificadoDigital;
      $strPublicKey = openssl_pkey_get_public($strLocalizacaoAjustada);
      if(empty($strPublicKey)) {
        throw new InfraException("Módulo do Tramita: Chave pública do certificado digital de autenticação no Tramita GOV.BR não pode ser localizada em $strLocalizacaoCertificadoDigital. Erro detalhado: " . openssl_error_string());
      }

      $strPrivateKey = openssl_pkey_get_private($strLocalizacaoAjustada, $strSenhaCertificadoDigital);
      if(empty($strPrivateKey)) {
        throw new InfraException("Módulo do Tramita: Chave privada do certificado digital de autenticação no Tramita GOV.BR não pode ser extraída em $strLocalizacaoCertificadoDigital. Erro detalhado: " . openssl_error_string());
      }

      return true;
    }


    /**
     * Verifica a conexão com o Barramento de Serviços do PEN, utilizando o endereço e certificados informados
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
     * Verifica a conexão com o Barramento de Serviços do PEN, utilizando o endereço e certificados informados
     *
     * @return bool
     */
    public function verificarAcessoPendenciasTramitePEN()
    {
      // Processa uma chamada ao Barramento de Serviços para certificar que o atual certificado está corretamente vinculado à um
      // comitê de protocolo válido
      try{
        $objProcessoEletronicoRN = new ProcessoEletronicoRN();
        $objProcessoEletronicoRN->listarPendencias(false);
          
        return true;
      } catch(Exception $e){
        throw new InfraException("Módulo do Tramita: Falha no acesso aos serviços de integração do Tramita GOV.BR: $e");
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
        // Não processa a verificação da instalação do Gearman caso não esteja configurado
        return false;
      }

      if(!class_exists("GearmanClient")) {
        throw new InfraException(
            "Módulo do Tramita: Não foi possível localizar as bibliotecas do PHP para conexão ao GEARMAN." .
            "Verifique os procedimentos de instalação do mod-sei-pen para maiores detalhes"
          );
      }

      try{
        $objGearmanClient = new GearmanClient();
        $objGearmanClient->addServer($strGearmanServidor, $strGearmanPorta);
        $objGearmanClient->setTimeout(10000);
        $objGearmanClient->ping("health");
      } catch (\Exception $e) {
        $strMensagemErro = "Não foi possível conectar ao servidor Gearman (%s, %s). Erro: %s";
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
        throw new InfraException("Módulo do Tramita: Arquivo do $strNomeArquivo não pode ser localizado em $strDiretorioArquivo");
      }
    }
}
