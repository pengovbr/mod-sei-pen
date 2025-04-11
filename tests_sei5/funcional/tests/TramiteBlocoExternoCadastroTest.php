<?php
/**
 * Cadastrar e editrar bloco
 *
 * Execution Groups
 * @group execute_alone_group1
 */
class TramiteBlocoExternoCadastroTest extends FixtureCenarioBaseTestCase
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
            mb_convert_encoding('Bloco de Trâmite externo criado com sucesso!', 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );

        $this->sairSistema();
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
            mb_convert_encoding('Bloco de trâmite externo alterado com sucesso!', 'UTF-8', 'ISO-8859-1'),
            $mensagem
        );

        $this->sairSistema();
    }
}