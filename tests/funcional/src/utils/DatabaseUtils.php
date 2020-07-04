<?php

class DatabaseUtils
{
    private $connection;

    function __construct($nomeContexto)
    {
        $dns = constant($nomeContexto . '_DB_SEI_DSN');
        $user = constant($nomeContexto . '_DB_SEI_USER');
        $password = constant($nomeContexto . '_DB_SEI_PASSWORD');
        $this->connection = new PDO($dns, $user, $password);
    }


	public function execute($sql, $params){
		$statement = $this->connection->prepare($sql);
		return $statement->execute($params);
	}


	public function query($sql, $params){
		$statement = $this->connection->prepare($sql);
		$statement->execute($params);
		return $statement->fetchAll();
	}
}
