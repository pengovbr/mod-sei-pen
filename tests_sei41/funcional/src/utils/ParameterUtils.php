<?php

require_once dirname(__FILE__)."/DatabaseUtils.php";

class ParameterUtils{

    const SEI_HABILITAR_NUMERO_PROCESSO_INFORMADO = "SEI_HABILITAR_NUMERO_PROCESSO_INFORMADO";
    const PARAM_NUMERO_INFORMADO_DESABILITADO = 0;
    const PARAM_NUMERO_INFORMADO_PROTOCOLO = 1;
    const PARAM_NUMERO_INFORMADO_UNIDADES = 2;

    private $databaseUtils;

  public function __construct($nomeContexto)
    {
      $this->databaseUtils = new DatabaseUtils($nomeContexto);
  }

  public function getParameter($parameter){
      $result = null;
      $query = "SELECT valor FROM md_pen_parametro WHERE nome = ?";
      $values = $this->databaseUtils->query($query, array($parameter));

    if(isset($values)){
        $result = $values[0]["valor"];
    }

      return $result;
  }

  public function setParameter($parameter, $value){
      $query = "UPDATE md_pen_parametro SET valor = ? WHERE nome = ?";
      return $this->databaseUtils->execute($query, array($value, $parameter));
  }
}
