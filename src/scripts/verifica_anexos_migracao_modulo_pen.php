<?php
/**
 * Verificação prévia dos anexos que a migração da versão 4.1.0 irá mover.
 *
 * A migração IGNORA o anexo cujo arquivo esteja ausente, ilegível ou corrompido:
 * ele permanece como está, em `anexo`, e a migração segue -- o id e o motivo vão
 * para um arquivo em <tmp>. Numa base antiga, porém, descobrir o tamanho do
 * problema no meio da janela de manutenção custa caro.
 *
 * Este script percorre exatamente o mesmo conjunto de anexos que a migração
 * percorreria e relata, antes da atualização, quais dariam problema.
 *
 * SOMENTE LEITURA: não grava no banco nem no repositório de arquivos.
 *
 * Uso:
 *   php -c /etc/php.ini verifica_anexos_migracao_modulo_pen.php
 *
 * Pré-requisito: os índices que a migração 4.1.0 cria em md_pen_componente_digital
 * precisam existir. Sem eles cada lote varre a tabela inteira e, em base grande,
 * a verificação não termina. Se faltarem, o script para e mostra os comandos:
 *   CREATE INDEX i01_md_pen_comp_dig_anexo_imut ON md_pen_componente_digital (id_anexo_imutavel);
 *   CREATE INDEX i02_md_pen_comp_dig_anexo      ON md_pen_componente_digital (id_anexo);
 *   CREATE INDEX i03_md_pen_proc_eletr_proced   ON md_pen_processo_eletronico (id_procedimento);
 *
 * Execute com o MESMO usuário que executará a migração. O root lê qualquer
 * arquivo, então rodando como root os anexos sem permissão de leitura passam
 * despercebidos aqui e só aparecem na lista de ignorados da migração.
 *
 * Variáveis de ambiente opcionais:
 *   PEN_VERIFICA_LOTE=500     linhas por lote
 *   PEN_VERIFICA_HASH=1       confere também o MD5 do conteúdo (lê cada arquivo;
 *                             lento em base grande -- sem isto, confere apenas
 *                             existência, leitura e tamanho)
 *   PEN_VERIFICA_MAX_PROBLEMAS=10000  para após esse número de problemas; 0 = sem limite
 *
 * A lista dos anexos com problema é sempre gravada num CSV em
 * <tmp>/verifica-anexos-AAAAMMDD-HHMMSS-<pid>.csv, cujo caminho é informado no
 * início e no fim da execução. O relatório de tela sai por stdout: redirecione
 * com "> verificacao.log" se quiser guardá-lo.
 *
 * <tmp> é o /tmp. Para gravar em outra pasta, informe TMPDIR na chamada:
 *   TMPDIR=/caminho/desejado php -c /etc/php.ini verifica_anexos_migracao_modulo_pen.php
 *
 * Saída: 0 = nenhum problema
 *        1 = problemas encontrados, base percorrida por inteiro
 *        3 = problemas encontrados, varredura interrompida no limite
 *        2 = erro de execução, inclusive índices ausentes
 */

$dirSeiWeb = !defined("DIR_SEI_WEB") ? getenv("DIR_SEI_WEB") ?: __DIR__ . "/../../web" : DIR_SEI_WEB;
require_once $dirSeiWeb . '/SEI.php';

if (!$argv || !isset($argv[0]) || realpath($argv[0]) !== __FILE__) {
    die("Este script deve ser executado apenas por linha de comando.\n");
}

// O Infra.php, carregado pelo SEI.php, impoe max_execution_time de 180s. Quem
// estende InfraScript recebe o zeramento; esta classe nao estende e precisa
// zerar aqui, senao a varredura de uma base grande morre no meio.
ini_set('max_execution_time', '0');

class VerificadorAnexosMigracaoV4100
{
    const TAMANHO_LOTE_PADRAO = 500;

    private $numLote;
    private $bolConferirHash;
    private $strArquivoSaida;

    const QTD_AMOSTRA = 20;
    const MAX_PROBLEMAS_PADRAO = 10000;

    private $numTotal = 0;
    private $numOk = 0;
    private $numProblemas = 0;
    private $arrAmostra = array();
    private $resCsv = null;
    private $numMaxProblemas;
    private $bolInterrompido = false;
    private $arrContagem = array(
        'ausente'    => 0,
        'ilegivel'   => 0,
        'tamanho'    => 0,
        'hash'       => 0,
        'data'       => 0,
    );

    public function __construct()
    {
        $this->numLote = (int) getenv('PEN_VERIFICA_LOTE');
        if ($this->numLote < 1) {
            $this->numLote = self::TAMANHO_LOTE_PADRAO;
        }
        $this->bolConferirHash = (getenv('PEN_VERIFICA_HASH') === '1');
        // O PID entra no nome porque o carimbo tem resolucao de 1 segundo:
        // duas execucoes no mesmo segundo colidiriam, e se a primeira for de
        // outro usuario a segunda nem consegue abrir o arquivo.
        $this->strArquivoSaida = sys_get_temp_dir()
            . '/verifica-anexos-' . date('Ymd-His') . '-' . getmypid() . '.csv';

        // Freio: passado esse numero de problemas, a base ja esta caracterizada
        // como problematica e continuar so gasta tempo. 0 desliga o limite.
        $strMax = getenv('PEN_VERIFICA_MAX_PROBLEMAS');
        $this->numMaxProblemas = ($strMax === false || $strMax === '')
            ? self::MAX_PROBLEMAS_PADRAO
            : (int) $strMax;
        if ($this->numMaxProblemas < 0) {
            $this->numMaxProblemas = 0;
        }
    }

    public function executar()
    {
        $this->imprimir('VERIFICACAO PREVIA DOS ANEXOS DA MIGRACAO 4.1.0');
        $this->imprimir(sprintf(
            'lote=%d  conferencia de hash=%s',
            $this->numLote,
            $this->bolConferirHash ? 'SIM' : 'nao (use PEN_VERIFICA_HASH=1)'
        ));
        $this->imprimir(sprintf(
            'parar apos %s problemas',
            $this->numMaxProblemas > 0 ? $this->numMaxProblemas : 'sem limite (PEN_VERIFICA_MAX_PROBLEMAS=0)'
        ));
        $this->imprimir('');

        if (!$this->verificarIndices()) {
            return 2;
        }

        $this->imprimir(sprintf('lista de problemas em %s', $this->strArquivoSaida));
        $this->imprimir('');

        // O log por consulta do INFRA geraria uma linha por lote lido; numa base
        // grande isso enche o disco sem ajudar. O relatorio abaixo continua ativo.
        $bolDebugAnterior = InfraDebug::getInstance()->isBolDebugInfra();
        InfraDebug::getInstance()->setBolDebugInfra(false);

        $this->abrirCsv();

        $numInicio = microtime(true);
        $numUltimoIdAnexo = 0;
        $numLotesLidos = 0;

        try {
            do {
                $arrAnexos = $this->listarLote($numUltimoIdAnexo);

                if (!empty($arrAnexos)) {
                    $numLotesLidos++;
                    $arrUltimo = end($arrAnexos);
                    $numUltimoIdAnexo = $arrUltimo['id_anexo'];

                    foreach ($arrAnexos as $arrAnexo) {
                        $this->verificarAnexo($arrAnexo);

                        if ($this->numMaxProblemas > 0 && $this->numProblemas >= $this->numMaxProblemas) {
                            $this->bolInterrompido = true;
                            break 2;
                        }
                    }

                    if ($numLotesLidos % 20 === 0) {
                        $this->imprimir(sprintf(
                            '  ... %d anexos verificados, %d problemas, %.1fs',
                            $this->numTotal,
                            $this->numProblemas,
                            microtime(true) - $numInicio
                        ));
                    }
                }
            } while (!empty($arrAnexos));
        } catch (Exception $e) {
            InfraDebug::getInstance()->setBolDebugInfra($bolDebugAnterior);
            throw $e;
        }

        InfraDebug::getInstance()->setBolDebugInfra($bolDebugAnterior);

        $this->relatar(microtime(true) - $numInicio);

        if ($this->resCsv !== null) {
            // Sem problemas o CSV so teria o cabecalho, e um arquivo vazio nao
            // distingue "base integra" de "script nao rodou". Registra o veredito.
            if ($this->numProblemas === 0) {
                fputcsv($this->resCsv, array(
                    '',
                    '',
                    '',
                    '',
                    'ok',
                    '',
                    sprintf('nenhum problema: %d anexos verificados', $this->numTotal),
                ));
            }
            fclose($this->resCsv);
            $this->resCsv = null;
        }

        if ($this->numProblemas === 0) {
            return 0;
        }

        return $this->bolInterrompido ? 3 : 1;
    }

    /**
     * Mesma consulta da migracao: anexos de documentos internos que pertencem ao
     * modulo, ancorada em id_anexo. Manter as duas em sincronia.
     */
    private function listarLote($numUltimoIdAnexo)
    {
        $objInfraBanco = BancoSEI::getInstance();
        // EXISTS aninhado (pe por id_procedimento, rt pela PK): mantem o custo do
        // lote constante em base grande. Manter em sincronia com a migracao.
        // id_protocolo e o numero do documento ajudam a localizar o anexo na
        // arvore do processo. Os LEFT JOIN trazem o numero do processo. Sao LEFT de
        // proposito: com INNER, anexos fora de documento (base de conhecimento,
        // projeto) sairiam do conjunto -- e a migracao os processa do mesmo jeito.
        $sql = "SELECT a.id_anexo, a.id_protocolo, a.nome, a.dth_inclusao, a.tamanho, a.hash,
                       p.protocolo_formatado AS documento,
                       pp.protocolo_formatado AS processo
                  FROM anexo a
                 INNER JOIN protocolo p  ON p.id_protocolo = a.id_protocolo
                  LEFT JOIN documento d  ON d.id_documento = a.id_protocolo
                  LEFT JOIN protocolo pp ON pp.id_protocolo = d.id_procedimento
                 WHERE p.sta_protocolo = 'G'
                   AND a.id_anexo > " . (int) $numUltimoIdAnexo . "
                   AND a.id_anexo IN (
                         SELECT cd.id_anexo FROM md_pen_componente_digital cd
                          WHERE cd.id_anexo IS NOT NULL
                            AND EXISTS (SELECT 1 FROM md_pen_processo_eletronico pe
                                        WHERE pe.id_procedimento = cd.id_procedimento
                                          AND EXISTS (SELECT 1 FROM md_pen_recibo_tramite rt
                                                       WHERE rt.numero_registro = pe.numero_registro))
                       UNION
                         SELECT cd.id_anexo_imutavel FROM md_pen_componente_digital cd
                          WHERE cd.id_anexo_imutavel IS NOT NULL
                            AND EXISTS (SELECT 1 FROM md_pen_processo_eletronico pe
                                        WHERE pe.id_procedimento = cd.id_procedimento
                                          AND EXISTS (SELECT 1 FROM md_pen_recibo_tramite rt
                                                       WHERE rt.numero_registro = pe.numero_registro)))
                 ORDER BY a.id_anexo";

        // limitarSql aplica o LIMIT e ja executa, devolvendo as linhas.
        return $objInfraBanco->limitarSql($sql, $this->numLote);
    }

    private function verificarAnexo($arrAnexo)
    {
        $this->numTotal++;

        $numIdAnexo  = $arrAnexo['id_anexo'];
        $arrProtocolo = array(
            isset($arrAnexo['processo']) ? $arrAnexo['processo'] : '',
            isset($arrAnexo['documento']) ? $arrAnexo['documento'] : '',
            isset($arrAnexo['id_protocolo']) ? $arrAnexo['id_protocolo'] : '',
        );

        try {
            $strDataHora = $this->normalizarDataHora($arrAnexo['dth_inclusao']);
        } catch (Exception $e) {
            $this->registrar($numIdAnexo, $arrProtocolo, 'data', '-', $e->getMessage());
            return;
        }

        $strCaminho = ConfiguracaoSEI::getInstance()->getValor('SEI', 'RepositorioArquivos')
            . '/' . substr($strDataHora, 6, 4)
            . '/' . substr($strDataHora, 3, 2)
            . '/' . substr($strDataHora, 0, 2)
            . '/' . $numIdAnexo;

        if (!file_exists($strCaminho)) {
            $this->registrar($numIdAnexo, $arrProtocolo, 'ausente', $strCaminho, 'arquivo nao encontrado');
            return;
        }

        if (!is_readable($strCaminho)) {
            $this->registrar($numIdAnexo, $arrProtocolo, 'ilegivel', $strCaminho, 'sem permissao de leitura');
            return;
        }

        $numTamanhoDisco = filesize($strCaminho);
        $numTamanhoBanco = (int) $arrAnexo['tamanho'];
        if ($numTamanhoBanco > 0 && $numTamanhoDisco !== $numTamanhoBanco) {
            $this->registrar($numIdAnexo, $arrProtocolo, 'tamanho', $strCaminho, sprintf(
                'banco=%d bytes, disco=%d bytes',
                $numTamanhoBanco,
                $numTamanhoDisco
            ));
            return;
        }

        if ($this->bolConferirHash) {
            $strHashBanco = $arrAnexo['hash'];
            if (!empty($strHashBanco) && $strHashBanco !== hash_file('md5', $strCaminho)) {
                $this->registrar($numIdAnexo, $arrProtocolo, 'hash', $strCaminho, 'MD5 do arquivo difere do gravado em anexo.hash');
                return;
            }
        }

        $this->numOk++;
    }

    /**
     * A consulta e crua, entao a data chega na forma nativa de cada driver.
     * Mesma normalizacao usada pela migracao.
     */
    private function normalizarDataHora($mixDataHora)
    {
        if ($mixDataHora instanceof DateTimeInterface) {
            return $mixDataHora->format('d/m/Y H:i:s');
        }

        if (is_resource($mixDataHora)) {
            $mixDataHora = stream_get_contents($mixDataHora);
        }

        if ($mixDataHora === null || $mixDataHora === '') {
            throw new InfraException('dth_inclusao vazia');
        }

        $arrFormatos = array('Y-m-d H:i:s', 'Y-m-d H:i:s.u', 'd/m/Y H:i:s', 'd/m/Y H:i:s.u');
        foreach ($arrFormatos as $strFormato) {
            $objData = DateTime::createFromFormat($strFormato, $mixDataHora);
            if ($objData !== false) {
                return $objData->format('d/m/Y H:i:s');
            }
        }

        throw new InfraException('dth_inclusao em formato nao reconhecido: ' . $mixDataHora);
    }

    /**
     * Grava o problema no CSV na hora e guarda so as primeiras QTD_AMOSTRA
     * linhas em memoria. Acumular a lista inteira estouraria o memory_limit
     * numa base com muitos problemas.
     */
    private function registrar($numIdAnexo, $arrProtocolo, $strTipo, $strCaminho, $strDetalhe)
    {
        $this->arrContagem[$strTipo]++;
        $this->numProblemas++;

        list($strProcesso, $strDocumento, $numIdProtocolo) = $arrProtocolo;

        if (count($this->arrAmostra) < self::QTD_AMOSTRA) {
            $this->arrAmostra[] = array($numIdAnexo, $strProcesso, $strTipo, $strCaminho, $strDetalhe, $strDocumento);
        }

        if ($this->resCsv !== null) {
            fputcsv($this->resCsv, array(
                $numIdAnexo, $strProcesso, $strDocumento, $numIdProtocolo, $strTipo, $strCaminho, $strDetalhe,
            ));
        }
    }

    /**
     * O CSV e desejavel, mas nao essencial: se nao der para grava-lo, a
     * verificacao segue e avisa. O INFRA converte warning em excecao, entao o
     * fopen precisa de try/catch alem do @.
     */
    private function abrirCsv()
    {
        try {
            $this->resCsv = @fopen($this->strArquivoSaida, 'w');
        } catch (Throwable $e) {
            $this->resCsv = false;
        }

        if ($this->resCsv === false) {
            $this->resCsv = null;
            $this->imprimir(sprintf('AVISO: nao foi possivel gravar %s -- seguindo sem CSV.', $this->strArquivoSaida));
            return;
        }

        // O CSV lista caminhos do repositorio de arquivos do orgao. Em /tmp ele
        // nasceria 644, legivel por qualquer usuario local do servidor.
        @chmod($this->strArquivoSaida, 0600);

        fputcsv($this->resCsv, array('id_anexo', 'processo', 'documento', 'id_protocolo', 'tipo', 'caminho', 'detalhe'));
    }

    private function relatar($numSegundos)
    {
        $numProblemas = $this->numProblemas;

        $this->imprimir('');
        $this->imprimir('---------------------------------------------------------------');
        $this->imprimir(sprintf(
            'anexos verificados : %d%s',
            $this->numTotal,
            $this->bolInterrompido ? '  (VARREDURA INCOMPLETA)' : ''
        ));
        $this->imprimir(sprintf('sem problema       : %d', $this->numOk));
        $this->imprimir(sprintf('com problema       : %d', $numProblemas));
        $this->imprimir(sprintf('tempo              : %.1fs', $numSegundos));
        $this->imprimir(sprintf('memoria de pico    : %.1f MB', memory_get_peak_usage(true) / 1048576));

        if ($numProblemas > 0) {
            $this->imprimir('');
            $this->imprimir('por tipo:');
            foreach ($this->arrContagem as $strTipo => $numQtd) {
                if ($numQtd > 0) {
                    $this->imprimir(sprintf('  %-9s %d', $strTipo, $numQtd));
                }
            }

            $numMostrar = count($this->arrAmostra);
            $this->imprimir('');
            $this->imprimir(sprintf('primeiros %d:', $numMostrar));
            foreach ($this->arrAmostra as $arr) {
                $this->imprimir(sprintf(
                    '  id_anexo=%-8s processo=%-22s documento=%-10s %-8s %s  (%s)',
                    $arr[0],
                    $arr[1] !== null && $arr[1] !== '' ? $arr[1] : '-',
                    $arr[5] !== null && $arr[5] !== '' ? $arr[5] : '-',
                    $arr[2],
                    $arr[3],
                    $arr[4]
                ));
            }

            if ($numProblemas > $numMostrar) {
                $this->imprimir(sprintf('  ... e mais %d -- a lista completa esta no CSV.', $numProblemas - $numMostrar));
            }

            if ($this->resCsv !== null) {
                $this->imprimir('');
                $this->imprimir(sprintf('lista completa gravada em %s', $this->strArquivoSaida));
            }


            $this->imprimir('');
            if ($this->bolInterrompido) {
                $this->imprimir(sprintf(
                    'VARREDURA INTERROMPIDA ao atingir %d problemas -- a base NAO foi',
                    $this->numMaxProblemas
                ));
                $this->imprimir('percorrida por inteiro e pode haver mais. Trate os problemas e');
                $this->imprimir('reexecute, ou use PEN_VERIFICA_MAX_PROBLEMAS=0 para varrer tudo.');
                $this->imprimir('');
            }
            $this->imprimir('A MIGRACAO VAI IGNORAR estes anexos: eles permanecem em `anexo`');
            $this->imprimir('e nao vao para a tabela do modulo. Trate-os antes, se possivel.');
        } else {
            $this->imprimir('');
            $this->imprimir('NENHUM PROBLEMA ENCONTRADO.');
            $this->imprimir(sprintf('veredito registrado em %s', $this->strArquivoSaida));
            if (!$this->bolConferirHash) {
                $this->imprimir('Observacao: o conteudo nao foi conferido. Para validar o MD5 de');
                $this->imprimir('cada arquivo, reexecute com PEN_VERIFICA_HASH=1.');
            }
        }
        $this->imprimir('---------------------------------------------------------------');
    }

    /**
     * Os indices de apoio da consulta so existem a partir da 4.1.0. Sem eles a
     * verificacao varre as tabelas inteiras a cada lote e nao termina em base
     * grande, entao o script para e informa os comandos de criacao.
     */
    private function verificarIndices()
    {
        $arrIndices = array();
        $objMetaBD = new InfraMetaBD(BancoSEI::getInstance());
        foreach (array('md_pen_componente_digital', 'md_pen_processo_eletronico') as $strTabela) {
            $arrIndices[$strTabela] = array();
            foreach ((array) $objMetaBD->obterIndices(null, $strTabela) as $arrPorTabela) {
                foreach ((array) $arrPorTabela as $arrColunas) {
                    $arrIndices[$strTabela][] = array_map('strtolower', (array) $arrColunas);
                }
            }
        }

        $arrFaltando = array();
        $arrNecessarios = array(
            'md_pen_componente_digital|id_anexo_imutavel' => 'i01_md_pen_comp_dig_anexo_imut',
            'md_pen_componente_digital|id_anexo'          => 'i02_md_pen_comp_dig_anexo',
            'md_pen_processo_eletronico|id_procedimento'  => 'i03_md_pen_proc_eletr_proced',
        );
        foreach ($arrNecessarios as $strChave => $strIndice) {
            list($strTabela, $strColuna) = explode('|', $strChave);
            if (!in_array(array($strColuna), $arrIndices[$strTabela], true)) {
                $arrFaltando[$strChave] = $strIndice;
            }
        }

        if (empty($arrFaltando)) {
            return true;
        }

        $this->imprimir('INDICES AUSENTES -- verificacao NAO executada.');
        $this->imprimir('Sem eles cada lote varre a tabela inteira e, em base grande, nao termina.');
        $this->imprimir('Peca ao DBA para criar, fora do horario de pico (igual nos quatro bancos):');
        $this->imprimir('');
        foreach ($arrFaltando as $strChave => $strIndice) {
            list($strTabela, $strColuna) = explode('|', $strChave);
            $this->imprimir(sprintf('  CREATE INDEX %s ON %s (%s);', $strIndice, $strTabela, $strColuna));
        }
        $this->imprimir('');
        $this->imprimir('Sao os mesmos indices que a migracao 4.1.0 cria; ela os reaproveita.');
        return false;
    }

    private function imprimir($strMensagem)
    {
        echo $strMensagem . "\n";
    }
}

$numSaida = 2;
try {
    InfraDebug::getInstance()->setBolLigado(false);
    InfraDebug::getInstance()->setBolDebugInfra(false);
    InfraDebug::getInstance()->setBolEcho(false);

    SessaoSEI::getInstance(false);

    // Mesma autenticacao do sei_atualizar_versao_modulo_pen.php: as credenciais
    // do banco vem pelo stdin. Sem abrir a conexao, a primeira consulta falha
    // com "Tentando executar uma consulta em uma conexao fechada".
    InfraScriptVersao::solicitarAutenticacao(BancoSEI::getInstance());
    BancoSEI::getInstance()->abrirConexao();

    $objVerificador = new VerificadorAnexosMigracaoV4100();
    $numSaida = $objVerificador->executar();
} catch (Exception $e) {
    echo "\nERRO NA VERIFICACAO: " . $e->getMessage() . "\n";
    if ($e instanceof InfraException) {
        echo $e->getStrDescricao() . "\n";
    }
    $numSaida = 2;
} catch (Error $e) {
    echo "\nERRO NA VERIFICACAO: " . $e->getMessage() . "\n";
    $numSaida = 2;
}

exit($numSaida);
