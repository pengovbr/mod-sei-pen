<?php

use PHPUnit\Framework\Attributes\{Group};

/**
 * Regressao introduzida pelo commit caef7593 (PR #1229, issues #1225/#1226),
 * que e o HEAD atual da release/4.2.0-beta.
 *
 * O que mudou: para levar 'descricao' e 'nome_arvore' do documento ate o orgao
 * de destino, o envio passou a sobrescrever o campo identificacao.complemento
 * com um JSON:
 *
 *   ExpedirProcedimentoRN::1153
 *     $identificacaoComplementar = json_decode($documento['identificacao']['complemento'] ?? '{}', true) ?: [];
 *     $identificacaoComplementar['descricao']   = ...getStrDescricaoProtocolo();
 *     $identificacaoComplementar['nome_arvore'] = ...getStrNomeArvore();
 *     $documento['identificacao']['complemento'] = json_encode($identificacaoComplementar);
 *
 * O problema: identificacao.complemento e um campo com limite de tamanho
 * validado pelo proprio barramento - o modulo inclusive o trunca em 100
 * caracteres nas demais atribuicoes (reduzirCampoTexto($valor, 100)). Um JSON
 * com a descricao do documento estoura esse limite com facilidade, e o
 * barramento rejeita o envio inteiro:
 *
 *   Erro: 0001 - Os seguintes erros de validacao de campos foram identificados:
 *   - processo.documentos[0].identificacao.complemento deve possuir tamanho
 *     entre os limites estabelecidos. Devido aos erros a operacao nao foi concluida.
 *
 * Efeito: qualquer processo com documento de descricao longa fica IMPOSSIBILITADO
 * de ser tramitado. Nao e caso de borda - a descricao padrao gerada pelos
 * proprios fixtures da suite ja estoura.
 *
 * Efeito colateral adicional: json_decode() de um complemento que nao seja JSON
 * retorna null, entao o valor original (descricao da unidade produtora, usado na
 * numeracao do documento) e descartado silenciosamente.
 *
 * Este teste usa a descricao padrao dos fixtures, sem exagero algum.
 *
 * Execution Groups
 * #[Group('execute_alone_group2')]
 */
class EnvioComplementoLimiteTest extends FixtureCenarioBaseTestCase
{
    public static $remetente;
    public static $destinatario;

    /**
     * #[Group('multiplos_orgaos')]
     */
    public function test_envio_com_descricao_padrao_de_documento_nao_pode_falhar()
    {
        self::$remetente = $this->definirContextoTeste(CONTEXTO_ORGAO_A);
        self::$destinatario = $this->definirContextoTeste(CONTEXTO_ORGAO_B);

        foreach (array(self::$destinatario['URL'], self::$remetente['URL']) as $strUrl) {
            try {
                $this->url($strUrl);
                $this->sairSistema();
            } catch (\Exception $e) {
                // ja estava deslogado
            }
        }

        $this->acessarSistema(
            self::$remetente['URL'],
            self::$remetente['SIGLA_UNIDADE'],
            self::$remetente['LOGIN'],
            self::$remetente['SENHA']
        );

        $arrProcesso = $this->gerarDadosProcessoTeste(self::$remetente);

        // Descricao padrao do proprio framework de teste: 10 palavras de 9
        // caracteres = 99 caracteres. Nada fora do comum.
        $arrDocumento = $this->gerarDadosDocumentoInternoTeste(self::$remetente);

        $objProtocoloDTO = $this->cadastrarProcessoFixture($arrProcesso);
        $this->cadastrarDocumentoInternoFixture($arrDocumento, $objProtocoloDTO->getDblIdProtocolo());

        $this->abrirProcesso($arrProcesso['PROTOCOLO']);

        // Falha aqui enquanto a regressao existir: o callback padrao de
        // tramitarProcessoExternamente exige a mensagem de sucesso na tela e
        // recebe o erro 0001 do barramento.
        $this->tramitarProcessoExternamente(
            $arrProcesso['PROTOCOLO'],
            self::$destinatario['REP_ESTRUTURAS'],
            self::$destinatario['NOME_UNIDADE'],
            self::$destinatario['SIGLA_UNIDADE_HIERARQUIA']
        );

        // Chegando aqui o envio foi aceito. Confirma que o documento chegou.
        $this->acessarSistema(
            self::$destinatario['URL'],
            self::$destinatario['SIGLA_UNIDADE'],
            self::$destinatario['LOGIN'],
            self::$destinatario['SENHA']
        );
        $this->abrirProcesso($arrProcesso['PROTOCOLO']);

        $this->assertTrue(
            $this->paginaProcesso->processoAberto(),
            'O processo deveria ter sido recebido no destino.'
        );
    }
}
