<?php

class DatabaseUtils
{
    private $connection;

  public function __construct($nomeContexto)
    {
      $dns = getenv($nomeContexto . '_DB_SEI_DSN');
      $user = getenv("SEI_DATABASE_USER");
      $password = getenv("SEI_DATABASE_PASSWORD");
      $this->connection = new PDO($dns, $user, $password);
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
