<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Classe gererica de persistncia com o banco de dados
 * 
 *
 */
class PenParametroBD extends InfraBD {

  public function setValor($strNome, $strValor) {

      $sql = '';
      $sql .= ' SELECT count(*) as existe';
      $sql .= ' FROM md_pen_parametro ';
      $sql .= ' WHERE nome=' . $this->getObjInfraIBanco()->formatarGravacaoStr($strNome);

      $rs = $this->getObjInfraIBanco()->consultarSql($sql);

    if ($rs[0]['existe'] == 0) {

      if (strlen($strNome) > 100) {
        throw new InfraException('Nome do parâmetro possui tamanho superior a 100 caracteres.');
      }

        $sql = '';
        $sql .= ' INSERT INTO infra_parametro (nome,valor)';
        $sql .= ' VALUES ';
        $sql .= ' (' . $this->getObjInfraIBanco()->formatarGravacaoStr($strNome) . ',' . $this->getObjInfraIBanco()->formatarGravacaoStr($strValor) . ')';

    } else {
        $sql = '';
        $sql .= ' UPDATE infra_parametro ';
        $sql .= ' SET valor = ' . $this->getObjInfraIBanco()->formatarGravacaoStr($strValor);
        $sql .= ' WHERE nome = ' . $this->getObjInfraIBanco()->formatarGravacaoStr($strNome);
    }

      $ret = $this->getObjInfraIBanco()->executarSql($sql);

      return $ret;
  }

  public function isSetValor($strNome) {
        
      $sql = '';
      $sql .= ' SELECT valor';
      $sql .= ' FROM md_pen_parametro ';
      $sql .= ' WHERE nome = ' . $this->getObjInfraIBanco()->formatarGravacaoStr($strNome);

      $rs = $this->getObjInfraIBanco()->consultarSql($sql);

    if (count($rs) == 0) {
        return false;
    }

      return true;
  }
}
