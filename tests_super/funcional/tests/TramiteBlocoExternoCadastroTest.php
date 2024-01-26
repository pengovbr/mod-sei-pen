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
        // Configuração do dados para teste do cenário
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        $penMapUnidadesFixture = new \PenMapUnidadesFixture(CONTEXTO_ORGAO_A, [
            'id' => self::$remetente['ID_ESTRUTURA'],
            'sigla' => self::$remetente['SIGLA_ESTRUTURA'],
            'nome' => self::$remetente['NOME_UNIDADE']
        ]);
        $penMapUnidadesFixture->cadastrar();

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
        $mensagem = $this->paginaCadastrarProcessoEmBloco->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Bloco de Trâmite externo criado com sucesso!'),
            $mensagem
        );
    }

    /**
     * Teste para editar bloco de trâmite externo já criado
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
        $mensagem = $this->paginaCadastrarProcessoEmBloco->buscarMensagemAlerta();
        $this->assertStringContainsString(
            utf8_encode('Bloco de trâmite externo alterado com sucesso!'),
            $mensagem
        );
    }

    public static function tearDownAfterClass(): void
    {
        // $objBlocoDeTramiteFixture = new \BlocoDeTramiteFixture();
        // $objBlocoDeTramiteFixture->excluir(1);
    }
}