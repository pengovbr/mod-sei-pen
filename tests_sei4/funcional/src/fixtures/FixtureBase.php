<?php

abstract class FixtureBase extends \InfraRN
{
  abstract protected function cadastrar($dados);

  protected function cadastrarInternoControlado($parametros){
      $dto = $this->cadastrar($parametros["dados"]);

    if (isset($parametros["callback"])) {
        $parametros["callback"]($dto);
    }

      return $dto;
  }

  public function carregar($dados = null, $callback = null){
      $dados = $dados ?: [];
      return $this->cadastrarInterno([
          'dados' => $dados,
          'callback' => $callback
      ]);
  }

  public function carregarVarios($dados = null, $quantidade = 1){
      $resultado = [];
    for ($i=0; $i < $quantidade; $i++) { 
        $resultado[] = $this->carregar($dados);
    }
                
      return $resultado;
  }

  public function carregarVariados($dados){
      $resultado = [];
    foreach ($dados as $dado) {
        $resultado[] = $this->carregar($dado);
    }  

      return $resultado;
  }

  protected function listarInternoControlado($parametros){
      $dto = $this->listar($parametros["dados"]);

    if (isset($parametros["callback"])) {
        $parametros["callback"]($dto);
    }

      return $dto;
  }

  public function buscar($dados = null, $callback = null){
      $dados = $dados ?: [];
      return $this->listarInterno([
          'dados' => $dados,
          'callback' => $callback
      ]);
  }

  protected function removerInternoControlado($parametros){
      $dto = $this->excluir($parametros["dados"]);

    if (isset($parametros["callback"])) {
        $parametros["callback"]($dto);
    }

      return $dto;
  }

  public function remover($dados = null, $callback = null){
      $dados = $dados ?: [];
      return $this->removerInterno([
          'dados' => $dados,
          'callback' => $callback
      ]);
  }

  public function atualizarInternoControlado($parametros){
      $dto = $this->alterar($parametros["dados"]);

    if (isset($parametros["callback"])) {
        $parametros["callback"]($dto);
    }

      return $dto;
  }

  public function atualizar($dados = null, $callback = null){
      $dados = $dados ?: [];
      return $this->atualizarInterno([
          'dados' => $dados,
          'callback' => $callback
      ]);
  }
}
