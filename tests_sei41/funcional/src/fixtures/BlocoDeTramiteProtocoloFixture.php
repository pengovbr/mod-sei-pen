<?php

class BlocoDeTramiteProtocoloFixture extends \FixtureBase
{
    protected $objBlocoDeTramiteProtocoloDTO;

  public function __construct()
    {
    $this->objBlocoDeTramiteProtocoloDTO = new \PenBlocoProcessoDTO();
  }
 
  protected function inicializarObjInfraIBanco()
    {
      return \BancoSEI::getInstance();
  }

  protected function cadastrar($dados = [])
    {
      $this->objBlocoDeTramiteProtocoloDTO->setDblIdProtocolo($dados['IdProtocolo'] ?: null);
      $this->objBlocoDeTramiteProtocoloDTO->setNumIdBloco($dados['IdBloco'] ?: null);
      $this->objBlocoDeTramiteProtocoloDTO->setNumSequencia($dados['Sequencia'] ?: null);
      $this->objBlocoDeTramiteProtocoloDTO->setNumIdUsuario($dados['IdUsuario'] ?: '100000001');
      $this->objBlocoDeTramiteProtocoloDTO->setNumIdUnidade($dados['IdUnidade'] ?: '110000001');
      $dthRegistro = date('d/m/Y H:i:s');
      $this->objBlocoDeTramiteProtocoloDTO->setDthRegistro($dados['DthRegistro'] ?: $dthRegistro);
      $this->objBlocoDeTramiteProtocoloDTO->setDthAtualizado($dados['DthAtualizado'] ?: $dthRegistro);

      // atualização 3.7.0
      $this->objBlocoDeTramiteProtocoloDTO->setNumIdAndamento($dados['IdAndamento'] ?: null);
      $this->objBlocoDeTramiteProtocoloDTO->setStrUnidadeDestino($dados['UnidadeDestino'] ?: null);
      $this->objBlocoDeTramiteProtocoloDTO->setNumIdUnidadeOrigem($dados['IdUnidadeOrigem'] ?: null);
      $this->objBlocoDeTramiteProtocoloDTO->setNumIdUnidadeDestino($dados['IdUnidadeDestino'] ?: null);
      $this->objBlocoDeTramiteProtocoloDTO->setNumIdAtividade($dados['IdAtividade'] ?: null);

      $this->objBlocoDeTramiteProtocoloDTO->setNumIdRepositorioOrigem($dados['IdRepositorioOrigem'] ?: null);
      $this->objBlocoDeTramiteProtocoloDTO->setNumIdRepositorioDestino($dados['IdRepositorioDestino'] ?: null);
      $this->objBlocoDeTramiteProtocoloDTO->setDthEnvio($dados['Envio'] ?: $dthRegistro);

      $this->objBlocoDeTramiteProtocoloDTO->setStrRepositorioDestino($dados['RepositorioDestino'] ?: null);

      $objBlocoDeTramiteProtocoloBD = new \PenBlocoProcessoBD($this->inicializarObjInfraIBanco());
      $objBlocoDeTramiteProtocoloBD->cadastrar($this->objBlocoDeTramiteProtocoloDTO);

      return $this->objBlocoDeTramiteProtocoloDTO;
  }

  protected function listar($dados = [])
    { 
      $this->objBlocoDeTramiteProtocoloDTO->setDblIdProtocolo($dados['IdProtocolo']);
      $this->objBlocoDeTramiteProtocoloDTO->retTodos();

      $objBlocoDeTramiteProtocoloBD = new \PenBlocoProcessoBD($this->inicializarObjInfraIBanco());
      return $objBlocoDeTramiteProtocoloBD->listar($this->objBlocoDeTramiteProtocoloDTO);
  }

  protected function alterar($dados = [])
  {
    
    $objBlocoDeTramiteProtocoloDTO = $this->listar($dados)[0];

    $objBlocoDeTramiteProtocoloDTO->setNumIdBloco($dados['IdBloco'] ?: $objBlocoDeTramiteProtocoloDTO->getNumIdBloco());
    $objBlocoDeTramiteProtocoloDTO->setNumSequencia($dados['Sequencia'] ?: $objBlocoDeTramiteProtocoloDTO->getNumSequencia());
    $objBlocoDeTramiteProtocoloDTO->setNumIdUsuario($dados['IdUsuario'] ?: $objBlocoDeTramiteProtocoloDTO->getNumIdUsuario());
    $objBlocoDeTramiteProtocoloDTO->setNumIdUnidade($dados['IdUnidade'] ?: $objBlocoDeTramiteProtocoloDTO->getNumIdUnidade());
    $dthRegistro = date('d/m/Y H:i:s');
    $objBlocoDeTramiteProtocoloDTO->setDthRegistro($dados['DthRegistro'] ?: $objBlocoDeTramiteProtocoloDTO->getDthRegistro());
    $objBlocoDeTramiteProtocoloDTO->setDthAtualizado($dados['DthAtualizado'] ?: $dthRegistro);

    $objBlocoDeTramiteProtocoloDTO->setNumIdAndamento($dados['IdAndamento'] ?: $objBlocoDeTramiteProtocoloDTO->getNumIdAndamento());
    $objBlocoDeTramiteProtocoloDTO->setStrUnidadeDestino($dados['UnidadeDestino'] ?: $objBlocoDeTramiteProtocoloDTO->getStrUnidadeDestino());
    $objBlocoDeTramiteProtocoloDTO->setNumIdUnidadeOrigem($dados['IdUnidadeOrigem'] ?: $objBlocoDeTramiteProtocoloDTO->getNumIdUnidadeOrigem());
    $objBlocoDeTramiteProtocoloDTO->setNumIdUnidadeDestino($dados['IdUnidadeDestino'] ?: $objBlocoDeTramiteProtocoloDTO->getNumIdUnidadeDestino());
    $objBlocoDeTramiteProtocoloDTO->setNumIdAtividade($dados['IdAtividade'] ?: $objBlocoDeTramiteProtocoloDTO->getNumIdAtividade());

    $objBlocoDeTramiteProtocoloDTO->setNumIdRepositorioOrigem($dados['IdRepositorioOrigem'] ?: $objBlocoDeTramiteProtocoloDTO->getNumIdRepositorioOrigem());
    $objBlocoDeTramiteProtocoloDTO->setNumIdRepositorioDestino($dados['IdRepositorioDestino'] ?: $objBlocoDeTramiteProtocoloDTO->getNumIdRepositorioDestino());
    $objBlocoDeTramiteProtocoloDTO->setDthEnvio($dados['Envio'] ?: $objBlocoDeTramiteProtocoloDTO->getDthEnvio());

    $objBlocoDeTramiteProtocoloDTO->setStrRepositorioDestino($dados['RepositorioDestino'] ?: $objBlocoDeTramiteProtocoloDTO->getStrRepositorioDestino());
    
    $objBlocoDeTramiteProtocoloBD = new \PenBlocoProcessoBD($this->inicializarObjInfraIBanco());
    return $objBlocoDeTramiteProtocoloBD->alterar($objBlocoDeTramiteProtocoloDTO);
  }

}
