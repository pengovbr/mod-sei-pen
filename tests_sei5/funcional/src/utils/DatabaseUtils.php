<?php

class DatabaseUtils
{
    private $connection;

  public function __construct($nomeContexto)
    {
      $dns = getenv($nomeContexto . '_DB_SEI_DSN');
      $user = getenv("SEI_DATABASE_USER");
      $password = getenv("SEI_DATABASE_PASSWORD");
      // CASE_UPPER torna o acesso as colunas agnostico de banco: o driver OCI
      // devolve os nomes em MAIUSCULAS e os demais como declarados. Sem isto,
      // $linha['coluna'] vira null no Oracle e o teste falha comparando string
      // vazia, sem defeito algum na aplicacao.
      // MAIUSCULA e a convencao ja adotada pela suite - ver os
      // array_change_key_case(..., CASE_UPPER) do CenarioBaseTestCase.
      $this->connection = new PDO($dns, $user, $password, array(
          PDO::ATTR_CASE => PDO::CASE_UPPER,
      ));
  }


  public function execute($sql, $params){
      $statement = $this->connection->prepare($sql);
      $result = $statement->execute($params);
      return $result;
  }


  public function query($sql, $params){
      $statement = $this->connection->prepare($sql);
      $statement->execute($params);
      return $statement->fetchAll();
  }   

    
  public function getBdType(){
      return $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
  }
}
