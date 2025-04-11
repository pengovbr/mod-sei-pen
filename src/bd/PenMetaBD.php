<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Mapeamento dos metadados sobre a estrutura do banco de dados
 */
class PenMetaBD extends InfraMetaBD
{

    const NNULLO = 'NOT NULL';
    const SNULLO = 'NULL';

    /**
     *
     * @return string
     */
  public function adicionarValorPadraoParaColuna($strNomeTabela, $strNomeColuna, $strValorPadrao, $bolRetornarQuery = false)
    {

      $objInfraBanco = $this->getObjInfraIBanco();
      $strTableDrive = get_parent_class($objInfraBanco);
      $strQuery = '';

    switch($strTableDrive) {

      case 'InfraMySqli':
          $strQuery = sprintf("ALTER TABLE `%s` ALTER COLUMN `%s` SET DEFAULT '%s'", $strNomeTabela, $strNomeColuna, $strValorPadrao);
          break;

      case 'InfraSqlServer':
          $strQuery =  sprintf("ALTER TABLE [%s] ADD DEFAULT('%s') FOR [%s]", $strNomeTabela, $strValorPadrao, $strNomeColuna);
          break;

      case 'InfraOracle':
          $strQuery =  sprintf("ALTER TABLE %s MODIFY %s DEFAULT '%s'", $strNomeTabela, $strNomeColuna, $strValorPadrao);
          break;

      case 'InfraPostgreSql':
          $strQuery = sprintf("ALTER TABLE %s ALTER COLUMN %s SET DEFAULT '%s'", $strNomeTabela, $strNomeColuna, $strValorPadrao);
          break;
    }

    if($bolRetornarQuery === false) {
        $objInfraBanco->executarSql($strQuery);
    }
    else {
        return  $strQuery;
    }
  }

    /**
     * Verifica se o usuário do drive de conexão possui permissão para criar/ remover
     * estruturas
     *
     * @return PenMetaBD
     */
  public function isDriverPermissao()
    {

      $objInfraBanco = $this->getObjInfraIBanco();

    if(count($this->obterTabelas('sei_teste'))==0) {
        $objInfraBanco->executarSql('CREATE TABLE sei_teste (id '.$this->tipoNumero().' NULL)');
    }

      $objInfraBanco->executarSql('DROP TABLE sei_teste');

      return $this;
  }

    /**
     * Verifica se o banco do SEI é suportador pelo atualizador
     *
     * @throws InfraException
     * @return PenMetaBD
     */
  public function isDriverSuportado()
    {

      $strTableDrive = get_parent_class($this->getObjInfraIBanco());

    switch($strTableDrive) {
     
      case 'InfraMySqli': // Fix para bug de MySQL versão inferior ao 5.5 o default engine
          // é MyISAM e não tem suporte a FOREING KEYS
          $version = $this->getObjInfraIBanco()->consultarSql('SELECT VERSION() as versao');
          $version = $version[0]['versao'];
          $arrVersion = explode('.', $version);
        if($arrVersion[0].$arrVersion[1] < 56) {
            $this->getObjInfraIBanco()->executarSql('@SET STORAGE_ENGINE=InnoDB');
        }
          break;
      case 'InfraSqlServer':
      case 'InfraOracle':
      case 'InfraPostgreSql':
          break;

      default:
          throw new InfraException('Módulo do Tramita: BANCO DE DADOS NAO SUPORTADO: ' . $strTableDrive);

    }

      return $this;
  }

    /**
     * Verifica se a versão sistema é compativel com a versão do módulo PEN
     *
     * @throws InfraException
     * @return PenMetaBD
     */
  public function isVersaoSuportada($strRegexVersaoSistema, $strVerMinRequirida)
    {

      $numVersaoRequerida = intval(preg_replace('/\D+/', '', $strVerMinRequirida));
      $numVersaoSistema = intval(preg_replace('/\D+/', '', $strRegexVersaoSistema));

    if($numVersaoRequerida > $numVersaoSistema) {
        throw new InfraException('Módulo do Tramita: VERSAO DO FRAMEWORK PHP INCOMPATIVEL (VERSAO ATUAL '.$strRegexVersaoSistema.', VERSAO REQUERIDA '.$strVerMinRequirida.')');
    }

      return $this;
  }

  public function adicionarChaveUnica($strNomeTabela = '', $arrNomeChave = [])
    {

      $this->getObjInfraIBanco()
          ->executarSql('ALTER TABLE '.$strNomeTabela.' ADD CONSTRAINT UK_'.$strNomeTabela.' UNIQUE('.implode(', ', $arrNomeChave).')');
  }

  public function novoRenomearTabela($strNomeTabelaAtual, $strNomeTabelaNovo)
    {

    if($this->isTabelaExiste($strNomeTabelaAtual)) {

        $objInfraBanco = $this->getObjInfraIBanco();
        $strTableDrive = get_parent_class($objInfraBanco);
        $strQuery = '';

      switch ($strTableDrive) {
        case 'InfraMySqli':
            $strQuery = sprintf("ALTER TABLE `%s` RENAME TO `%s`", $strNomeTabelaAtual, $strNomeTabelaNovo);
            break;

        case 'InfraSqlServer':
            $strQuery = sprintf("sp_rename '%s', '%s'", $strNomeTabelaAtual, $strNomeTabelaNovo);
            break;

        case 'InfraOracle':
            $strQuery = sprintf("RENAME %s TO %s", $strNomeTabelaAtual, $strNomeTabelaNovo);
            break;

        case 'InfraPostgreSql':
            $strQuery = sprintf("ALTER TABLE %s RENAME TO %s", $strNomeTabelaAtual, $strNomeTabelaNovo);
            break;
      }

        $objInfraBanco->executarSql($strQuery);
    }
  }
  
  public function renomearTabela($strNomeTabelaAtual, $strNomeTabelaNovo)
    {

    if($this->isTabelaExiste($strNomeTabelaAtual)) {

        $objInfraBanco = $this->getObjInfraIBanco();
        $strTableDrive = get_parent_class($objInfraBanco);
        $strQuery = '';

      switch ($strTableDrive) {
        case 'InfraMySqli':
            $strQuery = sprintf("ALTER TABLE `%s` RENAME TO `%s`", $strNomeTabelaAtual, $strNomeTabelaNovo);
            break;

        case 'InfraSqlServer':
            $strQuery = sprintf("sp_rename '%s', '%s'", $strNomeTabelaAtual, $strNomeTabelaNovo);
            break;

        case 'InfraOracle':
            $strQuery = sprintf("RENAME TABLE %s TO %s", $strNomeTabelaAtual, $strNomeTabelaNovo);
            break;

        case 'InfraPostgreSql':
            $strQuery = sprintf("ALTER TABLE %s RENAME TO %s", $strNomeTabelaAtual, $strNomeTabelaNovo);
            break;
      }

        $objInfraBanco->executarSql($strQuery);
    }
  }

  public function renomearColuna($strNomeTabela, $strNomeColunaAtual, $strNomeColunaNova, $strTipo)
    {

    if($this->isColunaExiste($strNomeTabela, $strNomeColunaAtual)) {

        $objInfraBanco = $this->getObjInfraIBanco();
        $strTableDrive = get_parent_class($objInfraBanco);
        $strQuery = '';

      switch ($strTableDrive) {

        case 'InfraMySqli':
            $strQuery = sprintf("ALTER TABLE `%s` CHANGE `%s` `%s` %s", $strNomeTabela, $strNomeColunaAtual, $strNomeColunaNova, $strTipo);
            break;

        case 'InfraSqlServer':
            $strQuery = sprintf("SP_RENAME '%s.%s', '%s', 'COLUMN'", $strNomeTabela, $strNomeColunaAtual, $strNomeColunaNova);
            break;

        case 'InfraOracle':
        case 'InfraPostgreSql':
            $strQuery = sprintf("ALTER TABLE %s RENAME COLUMN %s TO %s", $strNomeTabela, $strNomeColunaAtual, $strNomeColunaNova);
            break;
      }

        $objInfraBanco->executarSql($strQuery);
    }
  }

    /**
     * Verifica se uma tabela existe no banco
     *
     * @throws InfraException
     * @return bool
     */
  public function isTabelaExiste($strNomeTabela = '')
    {

      return count($this->obterTabelas($strNomeTabela)) != 0;
  }

  public function isColunaExiste($strNomeTabela = '', $strNomeColuna = '')
    {

      $arrColunas = $this->obterColunasTabela($strNomeTabela);
    foreach ($arrColunas as $objColuna) {
      if($objColuna['column_name'] == $strNomeColuna) {
        return true;
      }
    }

      return false;
  }

  public function isChaveExiste($strNomeTabela = '', $strNomeChave = '')
    {

      $arrConstraints = $this->obterConstraints($strNomeTabela);
    foreach ($arrConstraints as $objConstraint) {
      if($objConstraint['constraint_name'] == $strNomeChave) {
        return true;
      }
    }

      return false;
  }

    /**
     * Cria a estrutura da tabela no padrão ANSI
     *
     * @throws InfraException
     * @return PenMetaBD
     */
  public function criarTabela($arrSchema = [])
    {

      $strNomeTabela = $arrSchema['tabela'];

    if($this->isTabelaExiste($strNomeTabela)) {
        return $this;
    }

      $objInfraBanco = $this->getObjInfraIBanco();
      $arrColunas = [];
      $arrStrQuery = [];

    foreach($arrSchema['cols'] as $strNomeColuna => $arrColunaConfig) {

        [$strTipoDado, $strValorPadrao] = $arrColunaConfig;

      if($strValorPadrao != self::SNULLO && $strValorPadrao != self::NNULLO) {

          $arrStrQuery[] = $this->adicionarValorPadraoParaColuna($strNomeTabela, $strNomeColuna, $strValorPadrao, true);
          $strValorPadrao = self::NNULLO;
      }

        $arrColunas[] = $strNomeColuna.' '.$strTipoDado.' '.$strValorPadrao;
    }

      $objInfraBanco->executarSql('CREATE TABLE '.$strNomeTabela.' ('.implode(', ', $arrColunas).')');

    if(!empty($arrSchema['pk'])) {
        $strNomePK = array_key_exists('nome', $arrSchema['pk']) ? $arrSchema['pk']['nome'] : 'pk_' . $strNomeTabela;
        $arrColunas = array_key_exists('cols', $arrSchema['pk']) ? $arrSchema['pk']['cols'] : $arrSchema['pk'];
        $this->adicionarChavePrimaria($strNomeTabela, $strNomePK, $arrColunas);
      if(count($arrColunas) > 1) {
        for ($i=0; $i < count($arrColunas); $i++) {
          $strPk = $arrColunas[$i];
          $strNomeIndex = substr("i" . str_pad($i + 1, 2, "0", STR_PAD_LEFT) . '_' . $strNomeTabela, 0, 30);
          $objInfraBanco->executarSql('CREATE INDEX '.$strNomeIndex.' ON '.$strNomeTabela.'('.$strPk.')');
        }
      }
    }

    if(array_key_exists('uk', $arrSchema) && !empty($arrSchema['uk'])) {
        $this->adicionarChaveUnica($strNomeTabela, $arrSchema['uk']);
    }

    if(!empty($arrSchema['fks'])) {

      foreach($arrSchema['fks'] as $strTabelaOrigem => $array) {
          $strNomeFK = array_key_exists('nome', $array) ? $array['nome'] : 'fk_'.$strNomeTabela.'_'.$strTabelaOrigem;
          $arrayColumns = array_key_exists('cols', $array) ? $array['cols'] : $array;
          $arrCamposOrigem = (array)array_shift($arrayColumns);
          $arrCampos = $arrCamposOrigem;

        if(!empty($arrayColumns)) {
          $arrCampos = (array)array_shift($arrayColumns);
        }

          $this->adicionarChaveEstrangeira($strNomeFK, $strNomeTabela, $arrCampos, $strTabelaOrigem, $arrCamposOrigem, false);
      }
    }

    foreach($arrStrQuery as $strQuery) {
        $objInfraBanco->executarSql($strQuery);
    }

      return $this;
  }

    /**
     * Apagar a estrutura da tabela no banco de dados
     *
     * @throws InfraException
     * @return PenMetaBD
     */
  public function removerTabela($strNomeTabela = '')
    {

      $this->getObjInfraIBanco()->executarSql('DROP TABLE '.$strNomeTabela);
      return $this;
  }

  public function adicionarChaveEstrangeira($strNomeFK, $strTabela, $arrCampos, $strTabelaOrigem, $arrCamposOrigem, $bolCriarIndice = false)
    {

    if(!$this->isChaveExiste($strTabela, $strNomeFK)) {
        parent::adicionarChaveEstrangeira($strNomeFK, $strTabela, $arrCampos, $strTabelaOrigem, $arrCamposOrigem, $bolCriarIndice);
    }
      return $this;
  }

  public function adicionarChavePrimaria($strTabela, $strNomePK, $arrCampos)
    {

    if(!$this->isChaveExiste($strTabela, $strNomePK)) {
        parent::adicionarChavePrimaria($strTabela, $strNomePK, $arrCampos);
    }
      return $this;
  }

  public function alterarColuna($strTabela, $strColuna, $strTipo, $strNull = '')
    {
      parent::alterarColuna($strTabela, $strColuna, $strTipo, $strNull);
      return $this;
  }

  public function excluirIndice($strTabela, $strIndex)
    {
    if($this->isChaveExiste($strTabela, $strFk)) {
        parent::excluirIndice($strTabela, $strIndex);
    }
      return $this;
  }

  public function excluirChaveEstrangeira($strTabela, $strFk)
    {
    if($this->isChaveExiste($strTabela, $strFk)) {
        parent::excluirChaveEstrangeira($strTabela, $strFk);
    }
      return $this;
  }
}
