<?php

class TramiteBlocoExternoCadastroTest extends CenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;

    /**
     * Teste de cadastro de novo bloco de tramite externo
     *
     * @return void
     */
    public function test_cadastrar_novo_bloco_para_tramite_externo()
    {
        // Configura��o do dados para teste do cen�rio
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        $penMapUnidadesFixture = new PenMapUnidadesFixture(CONTEXTO_ORGAO_A, [
            'id' => self::$remetente['ID_ESTRUTURA'],
            'sigla' => self::$remetente['SIGLA_ESTRUTURA'],
            'nome' => self::$remetente['NOME_UNIDADE']
        ]);
        $penMapUnidadesFixture->gravar();

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
        $this->paginaCadastrarProcessoEmBloco->novoBlocoDeTramite();
        $this->paginaCadastrarProcessoEmBloco->criarNovoBloco();
        $this->paginaCadastrarProcessoEmBloco->btnSalvar();

        sleep(1);
        $mensagemRetornoAlert = $this->paginaCadastroOrgaoExterno->buscarMensagemAlerta();
        $menssagemValidacao = utf8_encode('Bloco de Tr�mite externo criado com sucesso!');

        $this->assertStringContainsString(
            $menssagemValidacao,
            $mensagemRetornoAlert
        );
    }

    /**
     * Teste para editar bloco de tr�mite externo j� criado
     *
     * @return void
     */
    public function test_editar_bloco_de_tramite()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);
        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );
        $this->paginaCadastrarProcessoEmBloco->navegarListagemBlocoDeTramite();
        $this->paginaCadastrarProcessoEmBloco->editarBlocoDeTramite('Bloco editado para teste automatizado');
        $this->paginaCadastrarProcessoEmBloco->btnSalvar();
        
        sleep(1);
        $mensagemRetornoAlert = $this->paginaCadastroOrgaoExterno->buscarMensagemAlerta();
        $menssagemValidacao = utf8_encode('Bloco de tr�mite externo alterado com sucesso!');

        $this->assertStringContainsString(
            $menssagemValidacao,
            $mensagemRetornoAlert
        );
    }
}