<?php

$dirSeiWeb = !defined("DIR_SEI_WEB") ? getenv("DIR_SEI_WEB") ?: __DIR__."/../../web" : DIR_SEI_WEB;
require_once $dirSeiWeb . '/SEI.php';

class PenAtualizarSeiRN extends PenAtualizadorRN {

    private $objInfraMetaBD = null;

    public function __construct() {
        parent::__construct();

        $this->objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());
    }

    protected function inicializarObjInfraIBanco(){
        return BancoSEI::getInstance();
    }

    protected function atualizarVersaoConectado() {
        try {

            $this->inicializar('INICIANDO ATUALIZACAO DO MODULO PEN NO SEI ' . SEI_VERSAO);

            //testando se esta usando BDs suportados
            if (!(BancoSEI::getInstance() instanceof InfraMySql) &&
                !(BancoSEI::getInstance() instanceof InfraSqlServer) &&
                !(BancoSEI::getInstance() instanceof InfraOracle)) {

                $this->finalizar('BANCO DE DADOS NAO SUPORTADO: ' . get_parent_class(BancoSEI::getInstance()), true);
            }

            SessaoSEI::getInstance(false)->simularLogin(SessaoSEI::$USUARIO_SEI, SessaoSEI::$UNIDADE_TESTE);

            //testando permissoes de cria��es de tabelas
            $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());

            if (count($objInfraMetaBD->obterTabelas('pen_sei_teste')) == 0) {
                BancoSEI::getInstance()->executarSql('CREATE TABLE pen_sei_teste (id ' . $objInfraMetaBD->tipoNumero() . ' null)');
            }
            BancoSEI::getInstance()->executarSql('DROP TABLE pen_sei_teste');

            $objInfraParametro = new InfraParametro(BancoSEI::getInstance());

            // Aplica��o de scripts de atualiza��o de forma incremental
            // Aus�ncia de [break;] proposital para realizar a atualiza��o incremental de vers�es
            $strVersaoModuloPen = $objInfraParametro->getValor(PENIntegracao::PARAMETRO_VERSAO_MODULO, false) ?: $objInfraParametro->getValor(PENIntegracao::PARAMETRO_VERSAO_MODULO_ANTIGO, false);
            switch ($strVersaoModuloPen) {
                case '':      $this->instalarV100(); // Nenhuma vers�o instalada
                case '1.0.0': $this->instalarV101();
                case '1.0.1': $this->instalarV110();
                case '1.1.0': $this->instalarV111();
                case '1.1.1': //N�o houve atualiza��o no banco de dados
                case '1.1.2': //N�o houve atualiza��o no banco de dados
                case '1.1.3': //N�o houve atualiza��o no banco de dados
                case '1.1.4': //N�o houve atualiza��o no banco de dados
                case '1.1.5': //N�o houve atualiza��o no banco de dados
                case '1.1.6': $this->instalarV117();
                case '1.1.7': $this->instalarV118();
                case '1.1.8': $this->instalarV119();
                case '1.1.9': $this->instalarV1110();
                case '1.1.10': $this->instalarV1111();
                case '1.1.11': $this->instalarV1112();
                case '1.1.12': $this->instalarV1113();
                case '1.1.13': $this->instalarV1114();
                case '1.1.14': $this->instalarV1115();
                case '1.1.15': $this->instalarV1116();
                case '1.1.16': $this->instalarV1117();
                case '1.1.17': $this->instalarV1200();
                case '1.2.0': $this->instalarV1201();
                case '1.2.1': $this->instalarV1202();
                case '1.2.2': $this->instalarV1203();
                case '1.2.3': $this->instalarV1204();
                case '1.2.4': $this->instalarV1205();
                case '1.2.5': $this->instalarV1206();
                case '1.2.6': $this->instalarV1300();
                case '1.3.0': $this->instalarV1400();
                case '1.4.0': $this->instalarV1401();
                case '1.4.1': $this->instalarV1402();
                case '1.4.2': $this->instalarV1403();
                case '1.4.3': $this->instalarV1500();
                case '1.5.0': $this->instalarV1501();
                case '1.5.1': $this->instalarV1502();
                case '1.5.2': $this->instalarV1503();
                case '1.5.3'; // Faixa de poss�veis vers�es da release 1.5.x de retrocompatibilidade
                case '1.5.4'; // Faixa de poss�veis vers�es da release 1.5.x de retrocompatibilidade
                case '1.5.5'; // Faixa de poss�veis vers�es da release 1.5.x de retrocompatibilidade
                case '1.5.6'; // Faixa de poss�veis vers�es da release 1.5.x de retrocompatibilidade
                case '1.5.7': $this->instalarV2000_beta1();
                case '2.0.0-beta1': $this->instalarV2000_beta2();
                case '2.0.0-beta2': $this->instalarV2000_beta3();
                case '2.0.0-beta3': $this->instalarV2000_beta4();
                case '2.0.0-beta4': $this->instalarV2000_beta5();
                case '2.0.0-beta5': $this->instalarV2000();
                case '2.0.0': $this->instalarV2001();
                case '2.0.1': $this->instalarV2100();
                case '2.1.0': $this->instalarV2101();
                case '2.1.1': $this->instalarV2102();
                case '2.1.2': $this->instalarV2103();
                case '2.1.3': $this->instalarV2104();
                case '2.1.4': $this->instalarV2105();
                case '2.1.5': $this->instalarV2106();
                case '2.1.6': $this->instalarV2107();
                case '2.1.7': $this->instalarV3000();
                case '3.0.0': $this->instalarV3001();
                case '3.0.1': $this->instalarV3010();
                case '3.1.0': $this->instalarV3011();
                case '3.1.1': $this->instalarV3012();
                    break;
                default:
                $this->finalizar('VERSAO DO M�DULO J� CONSTA COMO ATUALIZADA');
                break;
            }

            $this->finalizar('FIM');
        } catch (Exception $e) {
            InfraDebug::getInstance()->setBolLigado(false);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);
            throw new InfraException("Erro atualizando VERSAO: $e", $e);
        }
    }

    /**
     * Cria um novo par�metro
     * @return int C�digo do Parametro gerado
     */
    protected function criarParametro($strNome, $strValor, $strDescricao) {
        $objDTO = new PenParametroDTO();
        $objDTO->setStrNome($strNome);
        $objDTO->setStrValor($strValor);
        $objDTO->setStrDescricao($strDescricao);
        $objDTO->retStrNome();

        $objBD = new PenParametroBD(BancoSEI::getInstance());
        $objDTOCadastrado = $objBD->cadastrar($objDTO);

        return $objDTOCadastrado->getStrNome();
    }

    /**
     * Remove par�metro de configura��o do m�dulo da base de dados
     * @return int C�digo do Parametro gerado
     */
    protected function removerParametro($strNome) {
        $objDTO = new PenParametroDTO();
        $objDTO->setStrNome($strNome);
        $objDTO->retStrNome();

        $objBD = new PenParametroBD(BancoSEI::getInstance());
        $objBD->excluir($objDTO);
    }

    /**
     * Remove um par�metro do infra_parametros
     * @return string Nome do par�metro
     */
    protected function deletaParametroInfra($strNome) {
        $objDTO = new InfraParametroDTO();
        $objDTO->setStrNome($strNome);

        $objBD = new InfraParametroBD(BancoSEI::getInstance());
        return $objBD->excluir($objDTO);
    }

    /**
     * Remove todos os �ndices criados para o conjunto de tabelas informado
     */
    protected function removerIndicesTabela($parObjInfraMetaBD, $parFiltroTabelas)
    {
        $arrTabelasExclusao = is_array($parFiltroTabelas) ? $parFiltroTabelas : array($parFiltroTabelas);
        foreach ($arrTabelasExclusao as $strTabelaExclusao) {
            $arrStrIndices = $parObjInfraMetaBD->obterIndices(null, $strTabelaExclusao);
            foreach ($arrStrIndices as $strTabela => $arrStrIndices) {
                if($strTabela == $strTabelaExclusao){
                    foreach ($arrStrIndices as $strNomeIndice => $arrStrColunas) {
                        $parObjInfraMetaBD->excluirIndice($strTabelaExclusao, $strNomeIndice);
                    }
                }
            }
        }
    }


    /**
     * Atualiza o n�mero de vers�o do m�dulo nas tabelas de par�metro do sistema
     *
     * @param string $parStrNumeroVersao
     * @return void
     */
    private function atualizarNumeroVersao($parStrNumeroVersao)
    {
        $objInfraParametroDTO = new InfraParametroDTO();
        $objInfraParametroDTO->setStrNome(array(PENIntegracao::PARAMETRO_VERSAO_MODULO, PENIntegracao::PARAMETRO_VERSAO_MODULO_ANTIGO), InfraDTO::$OPER_IN);
        $objInfraParametroDTO->retTodos();
        $objInfraParametroBD = new InfraParametroBD(BancoSEI::getInstance());
        $objInfraParametroDTO = $objInfraParametroBD->consultar($objInfraParametroDTO);
        $objInfraParametroDTO->setStrValor($parStrNumeroVersao);
        $objInfraParametroBD->alterar($objInfraParametroDTO);
    }


    /**
     * Remove a chave prim�ria da tabela indicada, removendo tamb�m o �ndice vinculado, caso seja necess�rio
     *
     * Necess�rio dependendo da vers�o do banco de dados Oracle utilizado que pode n�o remover um �ndice criado com mesmo
     * nome da chave prim�ria, impedindo que este objeto seja recriado posteriormente na base de dados
     *
     * @param [type] $parStrNomeTabela
     * @param [type] $parStrNomeChavePrimario
     * @return void
     */
    private function excluirChavePrimariaComIndice($parStrNomeTabela, $parStrNomeChavePrimaria, $bolSuprimirErro=false)
    {
        try{
            $this->objInfraMetaBD->excluirChavePrimaria($parStrNomeTabela, $parStrNomeChavePrimaria);

            try{
                $this->objInfraMetaBD->excluirIndice($parStrNomeTabela, $parStrNomeChavePrimaria);
            } catch(\Exception $e) {
                //Caso o �ndice n�o seja localizado, nada dever� ser feito pois a exist�ncia depende de vers�o do banco de dados
            }
        } catch(Exception $e) {
            // Mensagem de erro deve ser suprimida caso seja indicado pelo usu�rio
            if(!$bolSuprimirErro){
                throw $e;
            }
        }
    }


    private function excluirChaveEstrangeira($parStrTabela, $parStrNomeChaveEstrangeira, $bolSuprimirErro=false)
    {
        try{
            $this->objInfraMetaBD->excluirChaveEstrangeira($parStrTabela, $parStrNomeChaveEstrangeira);
        } catch(\Exception $e){
            // Mensagem de erro deve ser suprimida caso seja indicado pelo usu�rio
            if(!$bolSuprimirErro){
                throw $e;
            }
        }
    }

    private function listarComponenteDigitaisDesatualizados()
    {
        $objInfraBanco = BancoSEI::getInstance();
        $sql = "select a.numero_registro, a.protocolo, a.id_procedimento, a.id_documento, a.id_tramite, a.ordem, a.ordem_documento
                from md_pen_componente_digital a
                where not exists(
                    select b.numero_registro, b.id_procedimento, b.id_documento, b.id_tramite, count(b.id_anexo)
                    from md_pen_componente_digital b
                    where a.numero_registro = b.numero_registro and
                          a.id_procedimento = b.id_procedimento and
                          a.id_documento = b.id_documento and
                          a.id_tramite = b.id_tramite
                    group by b.numero_registro, b.id_procedimento, b.id_documento, b.id_tramite
                    having count(b.id_anexo) > 1
                ) and a.ordem <> 1 and a.ordem_documento = 1";

        return $objInfraBanco->consultarSql($sql);
    }


    /* Cont�m atualiza��es da versao 1.0.0 do modulo */
    protected function instalarV100() {

        $objInfraBanco = BancoSEI::getInstance();
        $objMetaBD = $this->objMeta;

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_processo_eletronico',
            'cols' => array(
                'numero_registro' => array($objMetaBD->tipoTextoFixo(16), PenMetaBD::NNULLO),
                'id_procedimento' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO)
            ),
            'pk' => array('cols'=>array('numero_registro')),
            'uk' => array('numero_registro', 'id_procedimento'),
            'fks' => array(
                'procedimento' => array('nome' => 'fk_md_pen_proc_eletr_procedim',
                    'cols' => array('id_procedimento', 'id_procedimento')),
            )
        ));


        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_tramite',
            'cols' => array(
                'numero_registro' => array($objMetaBD->tipoTextoFixo(16), PenMetaBD::NNULLO),
                'id_tramite' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'ticket_envio_componentes' => array($objMetaBD->tipoTextoGrande(), PenMetaBD::SNULLO),
                'dth_registro' => array($objMetaBD->tipoDataHora(), PenMetaBD::SNULLO),
                'id_andamento' => array($objMetaBD->tipoNumero(), PenMetaBD::SNULLO),
                'id_usuario' => array($objMetaBD->tipoNumero(), PenMetaBD::SNULLO),
                'id_unidade' => array($objMetaBD->tipoNumero(), PenMetaBD::SNULLO)
            ),
            'pk' => array('cols'=>array('id_tramite')),
            'uk' => array('numero_registro', 'id_tramite'),
            'fks' => array(
                'md_pen_processo_eletronico' => array('nome'=>'fk_md_pen_tramite_proc_eletr',
                  'cols' => array('numero_registro', 'numero_registro')),
                'usuario' => array('id_usuario', 'id_usuario'),
                'unidade' => array('id_unidade', 'id_unidade'),
            )
        ));


        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_especie_documental',
            'cols' => array(
                'id_especie' => array($objMetaBD->tipoNumero(16), PenMetaBD::NNULLO),
                'nome_especie' => array($objMetaBD->tipoTextoVariavel(255), PenMetaBD::NNULLO),
                // Campo n�o mais necess�rio ap�s a vers�o 2.0.0 do m�dulo
                'descricao' => array($objMetaBD->tipoTextoVariavel(255), PenMetaBD::SNULLO)
            ),
            'pk' => array('cols'=>array('id_especie')),
        ));


        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_tramite_pendente',
            'cols' => array(
                'id' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'numero_tramite' => array($objMetaBD->tipoTextoVariavel(255)),
                'id_atividade_expedicao' => array($objMetaBD->tipoNumero(), PenMetaBD::SNULLO)
            ),
            'pk' => array('cols'=>array('id')),
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_tramite_recibo_envio',
            'cols' => array(
                'numero_registro' => array($objMetaBD->tipoTextoFixo(16), PenMetaBD::NNULLO),
                'id_tramite' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'dth_recebimento' => array($objMetaBD->tipoDataHora(), PenMetaBD::NNULLO),
                'hash_assinatura' => array($objMetaBD->tipoTextoVariavel(345), PenMetaBD::NNULLO)
            ),
            'pk' => array('cols'=>array('numero_registro', 'id_tramite')),
        ));


        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_procedimento_andamento',
            'cols' => array(
                'id_andamento' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'id_procedimento' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'id_tramite' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'situacao' => array($objMetaBD->tipoTextoFixo(1), 'N'),
                'data' => array($objMetaBD->tipoDataHora(), PenMetaBD::NNULLO),
                'mensagem' => array($objMetaBD->tipoTextoVariavel(255), PenMetaBD::NNULLO),
                'hash' => array($objMetaBD->tipoTextoFixo(32), PenMetaBD::NNULLO),
                'id_tarefa' => array($objMetaBD->tipoTextoVariavel(255), PenMetaBD::NNULLO)
            ),
            'pk' => array('nome' => 'pk_md_pen_procedim_andamen', 'cols'=>array('id_andamento')),
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_protocolo',
            'cols' => array(
                'id_protocolo' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'sin_obteve_recusa' => array($objMetaBD->tipoTextoFixo(1), 'N')
            ),
            'pk' => array('cols'=>array('id_protocolo')),
            'fks' => array(
                'protocolo' => array('id_protocolo', 'id_protocolo')
            )
        ));


        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_recibo_tramite',
            'cols' => array(
                'numero_registro' => array($objMetaBD->tipoTextoFixo(16), PenMetaBD::NNULLO),
                'id_tramite' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'dth_recebimento' => array($objMetaBD->tipoDataHora(), PenMetaBD::NNULLO),
                'hash_assinatura' => array($objMetaBD->tipoTextoVariavel(345), PenMetaBD::NNULLO),
                'cadeia_certificado' => array($objMetaBD->tipoTextoVariavel(255), PenMetaBD::NNULLO)
            ),
            'pk' => array('cols'=>array('numero_registro', 'id_tramite')),
            'fks' => array(
                'md_pen_tramite' => array('nome' => 'fk_md_pen_rec_tramite_tramite',
                  'cols' => array(array('numero_registro', 'id_tramite'), array('numero_registro', 'id_tramite')))
            )
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_recibo_tramite_enviado',
            'cols' => array(
                'numero_registro' => array($objMetaBD->tipoTextoFixo(16), PenMetaBD::NNULLO),
                'id_tramite' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'dth_recebimento' => array($objMetaBD->tipoDataHora(), PenMetaBD::NNULLO),
                'hash_assinatura' => array($objMetaBD->tipoTextoVariavel(345), PenMetaBD::NNULLO),
                'cadeia_certificado ' => array($objMetaBD->tipoTextoVariavel(255), PenMetaBD::NNULLO)
            ),
            'pk' => array('nome' => 'pk_md_pen_recibo_tram_envia', 'cols'=>array('numero_registro', 'id_tramite')),
            'fks' => array(
                'md_pen_tramite' => array('nome' => 'fk_md_pen_rec_tram_env_tram',
                  'cols' => array(array('numero_registro', 'id_tramite'), array('numero_registro', 'id_tramite')))
            )
        ));


        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_recibo_tramite_recebido',
            'cols' => array(
                'numero_registro' => array($objMetaBD->tipoTextoFixo(16), PenMetaBD::NNULLO),
                'id_tramite' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'dth_recebimento' => array($objMetaBD->tipoDataHora(), PenMetaBD::NNULLO),
                'hash_assinatura' => array($objMetaBD->tipoTextoVariavel(345), PenMetaBD::NNULLO)
            ),
            'pk' => array('nome' => 'pk_md_pen_recibo_tramite_receb', 'cols'=>array('numero_registro', 'id_tramite', 'hash_assinatura')),
            'fks' => array(
                'md_pen_tramite' => array('nome' => 'fk_md_pen_recibo_receb_tram',
                  'cols' => array(array('numero_registro', 'id_tramite'), array('numero_registro', 'id_tramite')))
            )
        ));


        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_rel_processo_apensado',
            'cols' => array(
                'numero_registro' => array($objMetaBD->tipoTextoFixo(16), PenMetaBD::NNULLO),
                'id_procedimento_apensado' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'protocolo' => array($objMetaBD->tipoTextoVariavel(50), PenMetaBD::NNULLO)
            ),
            'pk' => array('nome' => 'pk_md_pen_rel_processo_apensad', 'cols'=>array('numero_registro', 'id_procedimento_apensado')),
            'fks' => array(
                'md_pen_processo_eletronico' => array('nome' => 'fk_md_pen_proc_eletr_apensado',
                  'cols' => array('numero_registro', 'numero_registro'))
            )
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_rel_serie_especie',
            'cols' => array(
                'codigo_especie' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_serie' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'sin_padrao' => array($objMetaBD->tipoTextoFixo(1), 'N')
            ),
            'pk' => array('cols'=>array('id_serie')),
            'uk' => array('codigo_especie', 'id_serie'),
            'fks' => array(
                'serie' =>  array('nome' => ' fk_md_pen_rel_serie_especie', 'cols' => array('id_serie', 'id_serie'))
            )
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_rel_tarefa_operacao',
            'cols' => array(
                'id_tarefa' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'codigo_operacao' => array($objMetaBD->tipoTextoFixo(2), PenMetaBD::NNULLO)
            ),
            'pk' => array('cols'=>array('id_tarefa', 'codigo_operacao')),
            'fks' => array(
                'tarefa' =>  array('nome' => 'fk_md_pen_rel_operacao_tarefa', 'cols' => array('id_tarefa', 'id_tarefa'))
            )
        ));


        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_rel_tipo_doc_map_rec',
            'cols' => array(
                'codigo_especie' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_serie' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'sin_padrao' => array($objMetaBD->tipoTextoFixo(2), PenMetaBD::NNULLO)
            ),
            'pk' => array('cols'=>array('codigo_especie', 'id_serie')),
            'fks' => array(
                'serie' => array('nome' =>'fk_md_pen_rel_tipo_doc_serie', 'cols' => array('id_serie', 'id_serie'))
            )
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_componente_digital',
            'cols' => array(
                'numero_registro' => array($objMetaBD->tipoTextoFixo(16), PenMetaBD::NNULLO),
                'id_procedimento' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'id_documento' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'id_tramite' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'id_anexo' => array($objMetaBD->tipoNumero(), PenMetaBD::SNULLO),
                'protocolo' => array($objMetaBD->tipoTextoVariavel(50), PenMetaBD::NNULLO),
                'nome' => array($objMetaBD->tipoTextoVariavel(100), PenMetaBD::NNULLO),
                'hash_conteudo' => array($objMetaBD->tipoTextoVariavel(255), PenMetaBD::NNULLO),
                'algoritmo_hash' => array($objMetaBD->tipoTextoVariavel(20), PenMetaBD::NNULLO),
                'tipo_conteudo' => array($objMetaBD->tipoTextoFixo(3), PenMetaBD::NNULLO),
                'mime_type' => array($objMetaBD->tipoTextoVariavel(100), PenMetaBD::NNULLO),
                'dados_complementares' => array($objMetaBD->tipoTextoVariavel(1000), PenMetaBD::SNULLO),
                'tamanho' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'ordem' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'sin_enviar' => array($objMetaBD->tipoTextoFixo(1), 'N')
            ),
            'pk' => array('cols'=>array('numero_registro', 'id_procedimento', 'id_documento', 'id_tramite')),
            'fks' => array(
                'anexo' => array('nome' => 'fk_md_pen_comp_dig_anexo', 'cols' => array('id_anexo', 'id_anexo')),
                'documento' => array('nome' => 'fk_md_pen_comp_dig_documento', 'cols' => array('id_documento', 'id_documento')),
                'procedimento' => array('nome' => 'fk_md_pen_comp_dig_procediment', 'cols' => array('id_procedimento', 'id_procedimento')),
                'md_pen_processo_eletronico' => array('nome' => 'fk_md_pen_comp_dig_proc_eletr', 'cols' => array('numero_registro', 'numero_registro')),
                'md_pen_tramite' => array('nome' => 'fk_md_pen_comp_dig_tramite', 'cols' => array(array('numero_registro', 'id_tramite'), array('numero_registro', 'id_tramite')))
            )
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_unidade',
            'cols' => array(
                'id_unidade' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_unidade_rh' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO)
            ),
            'pk' => array('cols'=>array('id_unidade')),
            'fks' => array(
                'unidade' => array('id_unidade', 'id_unidade')
            )
        ));


        //----------------------------------------------------------------------
        // Novas sequ�ncias
        //----------------------------------------------------------------------
        $objInfraSequencia = new InfraSequencia($objInfraBanco);

        if (!$objInfraSequencia->verificarSequencia('md_pen_procedimento_andamento')) {
            $objInfraSequencia->criarSequencia('md_pen_procedimento_andamento', '1', '1', '9999999999');
        }

        if (!$objInfraSequencia->verificarSequencia('md_pen_tramite_pendente')) {
            $objInfraSequencia->criarSequencia('md_pen_tramite_pendente', '1', '1', '9999999999');
        }

        //----------------------------------------------------------------------
        // Par�metros
        //----------------------------------------------------------------------

        $objInfraParametro = new InfraParametro($objInfraBanco);

        $objInfraParametro->setValor('PEN_ID_REPOSITORIO_ORIGEM', '');
        $objInfraParametro->setValor('PEN_ENDERECO_WEBSERVICE', '');
        $objInfraParametro->setValor('PEN_SENHA_CERTIFICADO_DIGITAL', '1234');
        $objInfraParametro->setValor('PEN_TIPO_PROCESSO_EXTERNO', '');
        $objInfraParametro->setValor('PEN_ENVIA_EMAIL_NOTIFICACAO_RECEBIMENTO', 'N');
        $objInfraParametro->setValor('PEN_ENDERECO_WEBSERVICE_PENDENCIAS', '');
        $objInfraParametro->setValor('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO', '');
        $objInfraParametro->setValor('PEN_LOCALIZACAO_CERTIFICADO_DIGITAL', '');

        //----------------------------------------------------------------------
        // Especie de Documento
        //----------------------------------------------------------------------

        $objBD = new EspecieDocumentalBD(BancoSEI::getInstance());
        $objDTO = new EspecieDocumentalDTO();

        $fnCadastrar = function($dblIdEspecie, $strNomeEspecie, $strDescricao) use($objDTO, $objBD) {

            $objDTO->unSetTodos();
            $objDTO->setStrNomeEspecie($strNomeEspecie);

            if ($objBD->contar($objDTO) == 0) {
                $objDTO->setDblIdEspecie($dblIdEspecie);
                $objBD->cadastrar($objDTO);
            }
        };

        $fnCadastrar(1, 'Abaixo-assinado', 'Podendo ser complementado: de Reivindica��o');
        $fnCadastrar(2, 'Ac�rd�o', 'Expressa decis�o proferida pelo Conselho Diretor, n�o abrangida pelos demais instrumentos deliberativos anteriores.');
        $fnCadastrar(3, 'Acordo', 'Podendo ser complementado: de N�vel de Servi�o; Coletivo de Trabalho');
        $fnCadastrar(4, 'Alvar�', 'Podendo ser complementado: de Funcionamento; Judicial');
        $fnCadastrar(5, 'Anais', 'Podendo ser complementado: de Eventos; de Engenharia');
        $fnCadastrar(6, 'Anteprojeto', 'Podendo ser complementado: de Lei');
        $fnCadastrar(7, 'Ap�lice', 'Podendo ser complementado: de Seguro');
        $fnCadastrar(8, 'Apostila', 'Podendo ser complementado: de Curso');
        $fnCadastrar(9, 'Ata', 'Como Documento Externo pode ser complementado: de Reuni�o; de Realiza��o de Preg�o');
        $fnCadastrar(10, 'Atestado', 'Podendo ser complementado: M�dico; de Comparecimento; de Capacidade T�cnica');
        $fnCadastrar(11, 'Ato', 'Expressa decis�o sobre outorga, expedi��o, modifica��o, transfer�ncia, prorroga��o, adapta��o e extin��o de concess�es, permiss�es e autoriza��es para explora��o de servi�os, uso de recursos escassos e explora��o de sat�lite, e Chamamento P�blico.');
        $fnCadastrar(12, 'Auto', 'Podendo ser complementado: de Vistoria; de Infra��o');
        $fnCadastrar(13, 'Aviso', 'Podendo ser complementado: de Recebimento; de Sinistro; de F�rias');
        $fnCadastrar(14, 'Balancete', 'Podendo ser complementado: Financeiro');
        $fnCadastrar(15, 'Balan�o', 'Podendo ser complementado: Patrimonial - BP; Financeiro');
        $fnCadastrar(16, 'Bilhete', 'Podendo ser complementado: de Pagamento; de Loteria');
        $fnCadastrar(17, 'Boletim', 'Podendo ser complementado: de Ocorr�ncia; Informativo');
        $fnCadastrar(18, 'Carta', 'Podendo ser complementado: Convite');
        $fnCadastrar(19, 'Cartaz', 'Podendo ser complementado: de Evento');
        $fnCadastrar(20, 'C�dula', 'Podendo ser complementado: de Identidade; de Cr�dito Banc�rio; de Cr�dito Comercial; de Cr�dito Imobili�rio');
        $fnCadastrar(21, 'Certid�o', 'Como Documento Externo pode ser complementado: de Tempo de Servi�o; de Nascimento; de Casamento; de �bito; Negativa de Fal�ncia ou Concordata; Negativa de D�bitos Trabalhistas; Negativa de D�bitos Tribut�rios');
        $fnCadastrar(22, 'Certificado', 'Podendo ser complementado: de Conclus�o de Curso; de Calibra��o de Equipamento; de Marca');
        $fnCadastrar(23, 'Cheque', 'Podendo ser complementado: Cau��o');
        $fnCadastrar(24, 'Comprovante', 'Podendo ser complementado: de Despesa; de Rendimento; de Resid�ncia; de Matr�cula; de Uni�o Est�vel');
        $fnCadastrar(25, 'Comunicado', 'Expediente interno entre uma unidade administrativa e um servidor ou entre um servidor e uma unidade administrativa de um mesmo �rg�o p�blico.');
        $fnCadastrar(26, 'Consulta', 'Podendo ser complementado: P�blica; Interna');
        $fnCadastrar(27, 'Contracheque', 'Esp�cie pr�pria');
        $fnCadastrar(28, 'Contrato', 'Como Documento Externo pode ser complementado: Social');
        $fnCadastrar(29, 'Conv�nio', 'Esp�cie pr�pria');
        $fnCadastrar(30, 'Convite', 'Podendo ser complementado: de Reuni�o; para Evento; de Casamento');
        $fnCadastrar(31, 'Conven��o', 'Podendo ser complementado: Coletiva de Trabalho; Internacional');
        $fnCadastrar(32, 'Crach�', 'Podendo ser complementado: de Identifica��o; de Evento');
        $fnCadastrar(33, 'Cronograma', 'Podendo ser complementado: de Projeto; de Estudos');
        $fnCadastrar(34, 'Curr�culo', 'Podendo ser complementado: de Candidato');
        $fnCadastrar(35, 'Deb�nture', 'Esp�cie pr�pria');
        $fnCadastrar(36, 'Decis�o', 'Podendo ser complementado: Administrativa; Judicial');
        $fnCadastrar(37, 'Declara��o', 'Como Documento Externo pode ser complementado: de Imposto de Renda; de Conformidade; de Responsabilidade T�cnica; de Acumula��o de Aposentadoria; de Acumula��o de Cargos; de Informa��es Econ�mico-Fiscais da Pessoa Jur�dica $fnCadastrar(DIPJ);');
        $fnCadastrar(38, 'Decreto', 'Esp�cie pr�pria');
        $fnCadastrar(39, 'Delibera��o', 'Podendo ser complementado: de Recursos; do Conselho');
        $fnCadastrar(40, 'Demonstrativo', 'Podendo ser complementado: Financeiro; de Pagamento; de Arrecada��o');
        $fnCadastrar(41, 'Depoimento', 'Podendo ser complementado: das Testemunhas');
        $fnCadastrar(42, 'Despacho', 'Esp�cie pr�pria');
        $fnCadastrar(43, 'Di�rio', 'Podendo ser complementado: de Justi�a; Oficial');
        $fnCadastrar(44, 'Diploma', 'Podendo ser complementado: de Conclus�o de Curso');
        $fnCadastrar(45, 'Diretriz', 'Podendo ser complementado: Or�ament�ria');
        $fnCadastrar(46, 'Disserta��o', 'Podendo ser complementado: de Mestrado');
        $fnCadastrar(47, 'Dossi�', 'Podendo ser complementado: de Processo; T�cnico');
        $fnCadastrar(48, 'Edital', 'Podendo ser complementado: de Convoca��o; de Intima��o; de Lan�amento');
        $fnCadastrar(49, 'E-mail', 'Indicado nos Par�metros para corresponder ao envio de Correspond�ncia Eletr�nica do SEI');
        $fnCadastrar(50, 'Embargos', 'Podendo ser complementado: de Declara��o; de Execu��o ou Infringentes');
        $fnCadastrar(51, 'Emenda', 'Podendo ser complementado: Constitucional; de Comiss�o; de Bancada; de Relatoria');
        $fnCadastrar(52, 'Escala', 'Podendo ser complementado: de F�rias');
        $fnCadastrar(53, 'Escritura', 'Podendo ser complementado: P�blica; de Im�vel');
        $fnCadastrar(54, 'Estatuto', 'Podendo ser complementado: Social');
        $fnCadastrar(55, 'Exposi��o de Motivos', 'Esp�cie pr�pria');
        $fnCadastrar(56, 'Extrato', 'Podendo ser complementado: de Sistemas; Banc�rio');
        $fnCadastrar(57, 'Fatura', 'Esp�cie pr�pria');
        $fnCadastrar(58, 'Ficha', 'Podendo ser complementado: de Cadastro; de Inscri��o');
        $fnCadastrar(59, 'Fluxograma', 'Podendo ser complementado: de Processo; de Documentos; de Blocos');
        $fnCadastrar(60, 'Folha', 'Podendo ser complementado: de Frequ�ncia de Estagi�rio; de Frequ�ncia de Servidor');
        $fnCadastrar(61, 'Folheto/Folder', 'Podendo ser complementado: de Evento');
        $fnCadastrar(62, 'Formul�rio', 'Podendo ser complementado: de Contato; de Revis�o');
        $fnCadastrar(63, 'Grade Curricular', 'Podendo ser complementado: do Curso');
        $fnCadastrar(64, 'Guia', 'Podendo ser complementado: de Recolhimento da Uni�o');
        $fnCadastrar(65, 'Hist�rico', 'Podendo ser complementado: Escolar');
        $fnCadastrar(66, 'Indica��o', 'Esp�cie pr�pria utilizada pelo Poder Legislativo');
        $fnCadastrar(67, 'Informe', 'Como Documento Externo pode ser complementado: de Rendimentos');
        $fnCadastrar(68, 'Instru��o', 'Podendo ser complementado: Normativa');
        $fnCadastrar(69, 'Invent�rio', 'Podendo ser complementado: de Estoque; Extrajudicial; Judicial; em Cart�rio');
        $fnCadastrar(70, 'Laudo', 'Podendo ser complementado: M�dico; Conclusivo');
        $fnCadastrar(71, 'Lei', 'Podendo ser complementado: Complementar');
        $fnCadastrar(72, 'Lista/Listagem', 'Podendo ser complementado: de Presen�a');
        $fnCadastrar(73, 'Livro', 'Podendo ser complementado: Caixa');
        $fnCadastrar(74, 'Mandado', 'Podendo ser complementado: de Busca e Apreens�o; de Cita��o; de Intima��o');
        $fnCadastrar(75, 'Manifesto', 'Esp�cie pr�pria');
        $fnCadastrar(76, 'Manual', 'Podendo ser complementado: do Usu�rio; do Sistema; do Equipamento');
        $fnCadastrar(77, 'Mapa', 'Podendo ser complementado: de Ruas; de Risco');
        $fnCadastrar(78, 'Medida Provis�ria', 'Esp�cie pr�pria');
        $fnCadastrar(79, 'Memorando', 'Como Documento Externo pode ser complementado: de Entendimento');
        $fnCadastrar(80, 'Memorando-circular', 'Mesma defini��o do Memorando com apenas uma diferen�a: � encaminhado simultaneamente a mais de um cargo.');
        $fnCadastrar(81, 'Memorial', 'Podendo ser complementado: Descritivo; de Incorpora��o');
        $fnCadastrar(82, 'Mensagem', 'Podendo ser complementado: de Anivers�rio; de Boas Vindas');
        $fnCadastrar(83, 'Minuta', 'Podendo ser complementado: de Portaria; de Resolu��o');
        $fnCadastrar(84, 'Mo��o', 'Podendo ser complementado: de Apoio; de Pesar; de Rep�dio');
        $fnCadastrar(85, 'Norma', 'Podendo ser complementado: T�cnica; de Conduta');
        $fnCadastrar(86, 'Nota', 'Podendo ser complementado: T�cnica; de Empenho');
        $fnCadastrar(87, 'Notifica��o', 'Podendo ser complementado: de Lan�amento');
        $fnCadastrar(88, 'Of�cio', 'Modalidades de comunica��o oficial. � expedido para e pelas autoridades. Tem como finalidade o tratamento de assuntos oficiais pelos �rg�os da Administra��o P�blica entre si e tamb�m com particulares.');
        $fnCadastrar(89, 'Of�cio-Circular', 'Esp�cie pr�pria');
        $fnCadastrar(90, 'Or�amento', 'Podendo ser complementado: de Obra; de Servi�o');
        $fnCadastrar(91, 'Ordem', 'Podendo ser complementado: de Servi�o; de Compra; do Dia');
        $fnCadastrar(92, 'Organograma', 'Podendo ser complementado: da Empresa');
        $fnCadastrar(93, 'Orienta��o', 'Podendo ser complementado: Normativa; Jurisprudencial');
        $fnCadastrar(94, 'Panfleto', 'Podendo ser complementado: de Promo��o; de Evento');
        $fnCadastrar(95, 'Parecer', 'Tipo de Documento pr�prio da AGU e outros �rg�os p�blicos.');
        $fnCadastrar(96, 'Passaporte', 'Esp�cie pr�pria');
        $fnCadastrar(97, 'Pauta', 'Podendo ser complementado: de Julgamentos; de Audi�ncias; das Se��es');
        $fnCadastrar(98, 'Peti��o', 'Podendo ser complementado: Inicial; Incidental');
        $fnCadastrar(99, 'Planilha', 'Podendo ser complementado: de Custos e Forma��o de Pre�os');
        $fnCadastrar(100, 'Plano', 'Podendo ser complementado: de Servi�o; de Contas Cont�bil');
        $fnCadastrar(101, 'Planta', 'Podendo ser complementado: Baixa; de Localiza��o; de Situa��o');
        $fnCadastrar(102, 'Portaria', 'Expressa decis�o relativa a assuntos de interesse interno da Ag�ncia.');
        $fnCadastrar(103, 'Precat�rio', 'Podendo ser complementado: Alimentar; Federal; Estadual; Municipal');
        $fnCadastrar(104, 'Processo', 'Processo');
        $fnCadastrar(105, 'Procura��o', 'Esp�cie pr�pria');
        $fnCadastrar(106, 'Programa', 'Podendo ser complementado: de Governo; de Melhoria');
        $fnCadastrar(107, 'Projeto', 'Podendo ser complementado: T�cnico; Comercial');
        $fnCadastrar(108, 'Prontu�rio', 'Podendo ser complementado: M�dico; Odontol�gico');
        $fnCadastrar(109, 'Pronunciamento', 'Esp�cie pr�pria');
        $fnCadastrar(110, 'Proposta', 'Podendo ser complementado: Comercial; de Or�amento; T�cnica');
        $fnCadastrar(111, 'Prospecto', 'Podendo ser complementado: de Fundos');
        $fnCadastrar(112, 'Protocolo', 'Podendo ser complementado: de Entendimentos; de Entrega');
        $fnCadastrar(113, 'Prova', 'Podendo ser complementado: de Conceito; de Profici�ncia');
        $fnCadastrar(114, 'Question�rio', 'Podendo ser complementado: de Avalia��o; de Pesquisa; Socioecon�mico');
        $fnCadastrar(115, 'Receita', 'Esp�cie pr�pria');
        $fnCadastrar(116, 'Recibo', 'Podendo ser complementado: de Pagamento; de Entrega');
        $fnCadastrar(117, 'Recurso', 'Podendo ser complementado: Administrativo; Judicial');
        $fnCadastrar(118, 'Regimento', 'Podendo ser complementado: Interno');
        $fnCadastrar(119, 'Registro', 'Podendo ser complementado: de Detalhes de Chamadas - CDR; de Acesso; Comercial');
        $fnCadastrar(120, 'Regulamento', 'Podendo ser complementado: Geral; Disciplinar; de Administra��o');
        $fnCadastrar(121, 'Rela��o', 'Podendo ser complementado: de Bens Revers�veis - RBR');
        $fnCadastrar(122, 'Relat�rio', 'Podendo ser complementado: de Conformidade; de Medi��es; de Presta��o de Contas; de Viagem a Servi�o; Fotogr�fico; T�cnico');
        $fnCadastrar(123, 'Release', 'Podendo ser complementado: de Resultados; de Produtos; de Servi�os');
        $fnCadastrar(124, 'Representa��o', 'Podendo ser complementado: Comercial; Processual; Fiscal');
        $fnCadastrar(125, 'Requerimento', 'Podendo ser complementado: Administrativo; de Adapta��o; de Altera��o T�cnica; de Altera��o T�cnica; de Autocadastramento de Esta��o; de Licenciamento de Esta��o; de Servi�o de Telecomunica��es');
        $fnCadastrar(126, 'Requisi��o', 'Podendo ser complementado: de Auditoria; de Exclus�o; de Segunda Via');
        $fnCadastrar(127, 'Resolu��o', 'Expressa decis�o quanto ao provimento normativo que regula a implementa��o da pol�tica de telecomunica��es brasileira, a presta��o dos servi�os de telecomunica��es, a administra��o dos recursos � presta��o e o funcionamento da Ag�ncia.');
        $fnCadastrar(128, 'Resumo', 'Podendo ser complementado: T�cnico');
        $fnCadastrar(129, 'Roteiro', 'Podendo ser complementado: de Instala��o; de Inspe��o');
        $fnCadastrar(130, 'Senten�a', 'Podendo ser complementado: de M�rito; Terminativa; Declarat�ria; Constitutiva; Condenat�ria; Mandamental; Executiva');
        $fnCadastrar(131, 'Sinopse', 'Podendo ser complementado: do Livro; do Estudo T�cnico');
        $fnCadastrar(132, 'Solicita��o', 'Podendo ser complementado: de Pagamento');
        $fnCadastrar(133, 'S�mula', 'Expressa decis�o quanto � interpreta��o da legisla��o de telecomunica��es e fixa entendimento sobre mat�rias de compet�ncia da Ag�ncia, com efeito vinculativo.');
        $fnCadastrar(134, 'Tabela', 'Podendo ser complementado: de Visto; de Passaporte; de Certid�o');
        $fnCadastrar(135, 'Telegrama', 'Esp�cie pr�pria');
        $fnCadastrar(136, 'Termo', 'Podendo ser complementado: de Op��o por Aux�lio Financeiro; de Op��o para Contribui��o ao CPSS; de Concilia��o; de Devolu��o; de Doa��o; de Recebimento; de Rescis�o; de Compromisso de Est�gio; de Representa��o; de Responsabilidade de Instala��o - TRI');
        $fnCadastrar(137, 'Tese', 'Podendo ser complementado: de Doutorado');
        $fnCadastrar(138, 'Testamento', 'Podendo ser complementado: Particular; Vital; Cerrado; Conjuntivo');
        $fnCadastrar(139, 'T�tulo', 'Podendo ser complementado: de Eleitor; P�blico; de Capitaliza��o');
        $fnCadastrar(140, 'Voto', 'Esp�cie pr�pria');
        $fnCadastrar(141, 'Carteira', 'Podendo ser complementado: Nacional de Habilita��o');
        $fnCadastrar(142, 'Cart�o', 'Podendo ser complementado: de Identifica��o');
        $fnCadastrar(143, 'CPF/CIC', 'Esp�cie pr�pria');
        $fnCadastrar(144, 'CNPJ', 'Esp�cie pr�pria');
        $fnCadastrar(145, 'Calend�rio', 'Podendo ser complementado: de Reuni�es');
        $fnCadastrar(146, 'CNH', 'CNH');
        $fnCadastrar(147, 'RG', 'RG');
        $fnCadastrar(148, 'Agenda', 'Podendo ser complementado: de Reuni�o');
        $fnCadastrar(149, 'An�lise', 'Como Documento Externo pode ser complementado: Cont�bil');
        $fnCadastrar(150, 'Anota��o', 'Podendo ser complementado: de Responsabilidade T�cnica - ART');
        $fnCadastrar(151, '�udio', 'Podendo ser complementado: de Reuni�o');
        $fnCadastrar(152, 'Boleto', 'Podendo ser complementado: de Pagamento; de Cobran�a; de Cobran�a Registrada; de Cobran�a sem Registro');
        $fnCadastrar(153, 'Conta', 'Podendo ser complementado: Telef�nica; de �gua; de Luz');
        $fnCadastrar(154, 'Contrarraz�es', 'Podendo ser complementado: em Recurso; em Apela��o; em Embargos Infringentes');
        $fnCadastrar(155, 'Correspond�ncia', 'Esp�cie pr�pria');
        $fnCadastrar(156, 'Cota', 'Tipo de Documento pr�prio da AGU.');
        $fnCadastrar(157, 'Credencial', 'Podendo ser complementado: de Seguran�a; de Agente de Fiscaliza��o');
        $fnCadastrar(158, 'Croqui', 'Podendo ser complementado: de Acesso, Urbano');
        $fnCadastrar(159, 'Defesa', 'Podendo ser complementado: Administrativa; Judicial');
        $fnCadastrar(160, 'Demonstra��o', 'Podendo ser complementado: de Resultado do Exerc�cio - DRE; de Fluxo de Caixa; Financeira; Cont�bil');
        $fnCadastrar(161, 'Den�ncia', 'Esp�cie pr�pria');
        $fnCadastrar(162, 'Esclarecimento', 'Esp�cie pr�pria utilizada em Licita��o $fnCadastrar(ComprasNet);');
        $fnCadastrar(163, 'Escritura��o', 'Podendo ser complementado: Cont�bil Digital - ECD; Fiscal Digital - EFD; Fiscal Digital - EFD-Contribui��es');
        $fnCadastrar(164, 'Estrat�gia', 'Podendo ser complementado: da Contrata��o');
        $fnCadastrar(165, 'Impugna��o', 'Esp�cie pr�pria utilizada em Licita��o $fnCadastrar(ComprasNet);');
        $fnCadastrar(166, 'Informa��o', 'Tipo de Documento pr�prio da AGU.');
        $fnCadastrar(167, 'Inten��o', 'Podendo ser complementado: de Recurso; de Compra; de Venda');
        $fnCadastrar(168, 'Licen�a', 'Podendo ser complementado: de Esta��o');
        $fnCadastrar(169, 'Mat�ria', 'Podendo ser complementado: para Aprecia��o');
        $fnCadastrar(170, 'Material', 'Podendo ser complementado: Publicit�rio; de Evento; de Promo��o');
        $fnCadastrar(171, 'Mem�ria', 'Podendo ser complementado: de C�lculo');
        $fnCadastrar(172, 'Movimenta��o', 'Podendo ser complementado: de Bens M�veis');
        $fnCadastrar(173, 'Pedido', 'Podendo ser complementado: de Reconsidera��o; de Esclarecimento');
        $fnCadastrar(174, 'Reclama��o', 'Esp�cie pr�pria');
        $fnCadastrar(175, 'Referendo', 'Esp�cie pr�pria');
        $fnCadastrar(176, 'Resultado', 'Podendo ser complementado: de Exame M�dico; de Contesta��o');
        $fnCadastrar(177, 'V�deo', 'Podendo ser complementado: de Reuni�o');


        //----------------------------------------------------------------------
        // Tarefas
        //----------------------------------------------------------------------
        $objDTO = new TarefaDTO();

        $fnCadastrar = function($strNome='', $strHistoricoResumido='N', $strHistoricoCompleto='N', $strFecharAndamentosAbertos='N', $strLancarAndamentoFechado='N', $strPermiteProcessoFechado='N', $strIdTarefaModulo='') use($objDTO, $objBD) {

            $objDTO->unSetTodos();
            $objDTO->setStrIdTarefaModulo($strIdTarefaModulo);

            if ($objBD->contar($objDTO) == 0) {

                $objUltimaTarefaDTO = new TarefaDTO();
                $objUltimaTarefaDTO->retNumIdTarefa();
                $objUltimaTarefaDTO->setNumMaxRegistrosRetorno(1);
                $objUltimaTarefaDTO->setOrd('IdTarefa', InfraDTO::$TIPO_ORDENACAO_DESC);
                $objUltimaTarefaDTO = $objBD->consultar($objUltimaTarefaDTO);

                $objDTO->setNumIdTarefa($objUltimaTarefaDTO->getNumIdTarefa() + 1);
                $objDTO->setStrNome($strNome);
                $objDTO->setStrSinHistoricoResumido($strHistoricoResumido);
                $objDTO->setStrSinHistoricoCompleto($strHistoricoCompleto);
                $objDTO->setStrSinFecharAndamentosAbertos($strFecharAndamentosAbertos);
                $objDTO->setStrSinLancarAndamentoFechado($strLancarAndamentoFechado);
                $objDTO->setStrSinPermiteProcessoFechado($strPermiteProcessoFechado);
                $objDTO->setStrIdTarefaModulo($strIdTarefaModulo);
                $objBD->cadastrar($objDTO);
            }
        };

        //TODO: Corrigir mensagem com portugu�s errado
        $fnCadastrar('Processo tr�mitado externamente para a entidade @UNIDADE_DESTINO@ - @REPOSITORIO_DESTINO@ (@PROCESSO@, @UNIDADE@, @USUARIO@)', 'S', 'S', 'N', 'S', 'N', 'PEN_PROCESSO_EXPEDIDO');
        $fnCadastrar('Processo recebido da entidade @ENTIDADE_ORIGEM@ - @REPOSITORIO_ORIGEM@ (@PROCESSO@, @ENTIDADE_ORIGEM@, @UNIDADE_DESTINO@, @USUARIO@)', 'S', 'S', 'N', 'S', 'N', 'PEN_PROCESSO_RECEBIDO');
        $fnCadastrar('O processo foi recusado pelo org�o @UNIDADE_DESTINO@ pelo seguinte motivo: @MOTIVO@', 'S', 'S', 'N', 'N', 'S', 'PEN_PROCESSO_RECUSADO');
        $fnCadastrar('Tr�mite externo do processo cancelado em @DATA_HORA@ pelo Usu�rio @USUARIO@', 'S', 'S', 'N', 'S', 'N', 'PEN_PROCESSO_CANCELADO');
        $fnCadastrar('Operacao externa de @OPERACAO@ registrada em @DATA_HORA@ (@PESSOA_IDENTIFICACAO@ - @PESSOA_NOME@)\n @COMPLEMENTO@', 'S', 'S', 'S', 'S', 'N', 'PEN_OPERACAO_EXTERNA');

        //----------------------------------------------------------------------
        // Opera��es por Tarefas
        //----------------------------------------------------------------------
        $objDTO = new RelTarefaOperacaoDTO();

        $fnCadastrar = function($strCodigoOperacao, $numIdTarefa) use($objDTO, $objBD) {

            $objDTO->unSetTodos();
            $objDTO->setStrCodigoOperacao($strCodigoOperacao);
            $objDTO->setNumIdTarefa($numIdTarefa);

            if ($objBD->contar($objDTO) == 0) {
                $objBD->cadastrar($objDTO);
            }
        };

        //$fnCadastrar("01", 0);// Registro (Padr�o);
        $fnCadastrar("02", 32); //  Envio de documento avulso/processo ($TI_PROCESSO_REMETIDO_UNIDADE = 32;);
        $fnCadastrar("03", 51); //  Cancelamento/exclusao ou envio de documento ($TI_CANCELAMENTO_DOCUMENTO = 51;);
        $fnCadastrar("04", 13); //  Recebimento de documento ($TI_RECEBIMENTO_DOCUMENTO = 13;);
        $fnCadastrar("05", 1); // Autuacao ($TI_GERACAO_PROCEDIMENTO = 1;);
        $fnCadastrar("06", 101); // Juntada por anexacao ($TI_ANEXADO_PROCESSO = 101;);
        //$fnCadastrar("07", 0);// Juntada por apensacao;
        //$fnCadastrar("08", 0);// Desapensacao;
        $fnCadastrar("09", 24); //  Arquivamento ($TI_ARQUIVAMENTO = 24;);
        //$fnCadastrar("10", 0);// Arquivamento no Arquivo Nacional;
        //$fnCadastrar("11", 0);// Eliminacao;
        //$fnCadastrar("12", 0);// Sinistro;
        //$fnCadastrar("13", 0);// Reconstituicao de processo;
        $fnCadastrar("14", 26); // Desarquivamento ($TI_DESARQUIVAMENTO = 26;);
        //$fnCadastrar("15", 0);// Desmembramento;
        //$fnCadastrar("16", 0);// Desentranhamento;
        //$fnCadastrar("17", 0);// Encerramento/abertura de volume no processo;
        //$fnCadastrar("18", 0);// Registro de extravio;

        $objDTO = new InfraAgendamentoTarefaDTO();

        $fnCadastrar = function($strComando, $strDesc) use($objDTO, $objBD, $objRN) {

            $objDTO->unSetTodos();
            $objDTO->setStrComando($strComando);

            if ($objBD->contar($objDTO) == 0) {

                $objDTO->setStrDescricao($strDesc);
                $objDTO->setStrStaPeriodicidadeExecucao('D');
                $objDTO->setStrPeriodicidadeComplemento('0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23');
                $objDTO->setStrSinAtivo('S');
                $objDTO->setStrSinSucesso('S');

                $objBD->cadastrar($objDTO);
            }
        };

        $fnCadastrar('PENAgendamentoRN::seiVerificarServicosBarramento', 'Verifica��o dos servi�os de fila de processamento est�o em execu��o');

        /* ---------- antigo m�todo (instalarV002R003S000US024) ---------- */

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_tramite_processado',
            'cols' => array(
                'id_tramite' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'dth_ultimo_processamento' => array($objMetaBD->tipoDataHora(), PenMetaBD::NNULLO),
                'numero_tentativas' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'sin_recebimento_concluido' => array($objMetaBD->tipoTextoFixo(1), PenMetaBD::NNULLO)
            ),
            'pk' => array('cols'=>array('id_tramite')),
        ));

        $objInfraParametro = new InfraParametro($objInfraBanco);
        $objInfraParametro->setValor('PEN_NUMERO_TENTATIVAS_TRAMITE_RECEBIMENTO', '3');


        /* ---------- antigo m�todo (instalarV002R003S000IW001) ---------- */

        $objDTO = new TarefaDTO();
        $objBD = new TarefaBD($objInfraBanco);

        $fnAlterar = function($strIdTarefaModulo, $strNome) use($objDTO, $objBD) {

            $objDTO->unSetTodos();
            $objDTO->setStrIdTarefaModulo($strIdTarefaModulo);
            $objDTO->setNumMaxRegistrosRetorno(1);
            $objDTO->retStrNome();
            $objDTO->retNumIdTarefa();

            $objDTO = $objBD->consultar($objDTO);

            if (empty($objDTO)) {

                $objDTO->setStrNome($strNome);
                $objBD->cadastrar($objDTO);
            } else {

                $objDTO->setStrNome($strNome);
                $objBD->alterar($objDTO);
            }
        };

        $fnAlterar('PEN_PROCESSO_RECEBIDO', 'Processo recebido da entidade @ENTIDADE_ORIGEM@ - @REPOSITORIO_ORIGEM@');

        /* ---------- antigo m�todo (instalarV002R003S001US035) ---------- */
        $objMetaBanco = $this->inicializarObjMetaBanco();

        if (!$objMetaBanco->isColunaExiste('md_pen_tramite_processado', 'tipo_tramite_processo')) {
            $objMetaBanco->adicionarColuna('md_pen_tramite_processado', 'tipo_tramite_processo', 'CHAR(2)', PenMetaBD::NNULLO);
            $objMetaBanco->adicionarValorPadraoParaColuna('md_pen_tramite_processado', 'tipo_tramite_processo', 'RP');
        }

        if ($objMetaBanco->isChaveExiste('md_pen_tramite_processado', 'pk_md_pen_tramite_processado')) {
            $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());
            $this->excluirChavePrimariaComIndice("md_pen_tramite_processado", "pk_md_pen_tramite_processado");
            $objInfraMetaBD->adicionarChavePrimaria("md_pen_tramite_processado", "pk_md_pen_tramite_processado", array('id_tramite', 'tipo_tramite_processo'));
        }

        /* ---------- antigo m�todo (instalarV003R003S003IW001) ---------- */

        //----------------------------------------------------------------------
        // Novas sequ�ncias
        //----------------------------------------------------------------------
        $objInfraSequencia = new InfraSequencia($objInfraBanco);

        if (!$objInfraSequencia->verificarSequencia('md_pen_rel_doc_map_enviado')) {
            $objInfraSequencia->criarSequencia('md_pen_rel_doc_map_enviado', '1', '1', '9999999999');
        }

        if (!$objInfraSequencia->verificarSequencia('md_pen_rel_doc_map_recebido')) {
            $objInfraSequencia->criarSequencia('md_pen_rel_doc_map_recebido', '1', '1', '9999999999');
        }

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_rel_doc_map_enviado',
            'cols' => array(
                'id_mapeamento' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'codigo_especie' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_serie' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'sin_padrao' => array($objMetaBD->tipoTextoFixo(1), 'S')
            ),
            'pk' => array('cols'=>array('id_mapeamento')),
            'fks' => array(
                'serie' => array('nome' => 'fk_md_pen_rel_doc_map_env_seri', 'cols' => array('id_serie', 'id_serie')),
                'md_pen_especie_documental' => array('nome' => 'fk_md_pen_rel_doc_map_env_espe', 'cols' => array('id_especie', 'codigo_especie')),
            )
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_rel_doc_map_recebido',
            'cols' => array(
                'id_mapeamento' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'codigo_especie' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_serie' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'sin_padrao' => array($objMetaBD->tipoTextoFixo(1), 'S')
            ),
            'pk' => array('cols'=>array('id_mapeamento')),
            'fks' => array(
                'serie' => array('nome' => 'fk_md_pen_rel_doc_map_rec_seri', 'cols' => array('id_serie', 'id_serie')),
                'md_pen_especie_documental' => array('nome' => 'fk_md_pen_rel_doc_map_rec_espe', 'cols' => array('id_especie', 'codigo_especie')),
            )
        ));

        $objBD = new PenRelTipoDocMapRecebidoBD($objInfraBanco);
        if ($objMetaBD->isTabelaExiste('md_pen_rel_tipo_documento_mapeamento_recebido')) {
            $objDTO = new PenRelTipoDocMapRecebidoDTO();
            $fnCadastrar = function($numCodigoEspecie, $numIdSerie) use($objDTO, $objBD) {
                $objDTO->unSetTodos();
                $objDTO->setNumCodigoEspecie($numCodigoEspecie);
                $objDTO->setNumIdSerie($numIdSerie);

                if ($objBD->contar($objDTO) == 0) {
                    $objDTO->setStrPadrao('S');
                    $objBD->cadastrar($objDTO);
                }
            };

            $arrDados = $objInfraBanco->consultarSql('SELECT DISTINCT codigo_especie, id_serie FROM md_pen_rel_tipo_documento_mapeamento_recebido');
            if (!empty($arrDados)) {
                foreach ($arrDados as $arrDocMapRecebido) {
                    $fnCadastrar($arrDocMapRecebido['codigo_especie'], $arrDocMapRecebido['id_serie']);
                }
            }

            $objMetaBD->removerTabela('md_pen_rel_tipo_documento_mapeamento_recebido');
        }

        if ($objMetaBD->isTabelaExiste('md_pen_rel_serie_especie')) {
            $objDTO = new PenRelTipoDocMapEnviadoDTO();
            $fnCadastrar = function($numCodigoEspecie, $numIdSerie) use($objDTO, $objBD) {

                $objDTO->unSetTodos();
                $objDTO->setNumCodigoEspecie($numCodigoEspecie);
                $objDTO->setNumIdSerie($numIdSerie);

                if ($objBD->contar($objDTO) == 0) {
                    $objDTO->setStrPadrao('S');
                    $objBD->cadastrar($objDTO);
                }
            };

            $arrDados = $objInfraBanco->consultarSql('SELECT DISTINCT codigo_especie, id_serie FROM md_pen_rel_serie_especie');
            if (!empty($arrDados)) {
                foreach ($arrDados as $arrDocMapEnviado) {
                    $fnCadastrar($arrDocMapEnviado['codigo_especie'], $arrDocMapEnviado['id_serie']);
                }
            }

            $objMetaBD->removerTabela('md_pen_rel_serie_especie');
        }

        /* ---------- antigo m�todo (instalarV004R003S003IW002) ---------- */
        
        $strTipo = $this->inicializarObjMetaBanco()->tipoTextoGrande();
        $objMetaBanco->adicionarColuna('md_pen_recibo_tramite', 'cadeia_certificado_temp', $strTipo, PenMetaBD::SNULLO);
        BancoSEI::getInstance()->executarSql("update md_pen_recibo_tramite set cadeia_certificado_temp = cadeia_certificado");
        $objMetaBanco->excluirColuna('md_pen_recibo_tramite', 'cadeia_certificado');
        try {
            $objMetaBanco->renomearColuna('md_pen_recibo_tramite', 'cadeia_certificado_temp', 'cadeia_certificado', $strTipo);
        }catch(Exception $e){
            if (strpos($e->__toString(),'Caution: Changing any part of an object name could break scripts and stored procedures.')===false){
                throw $e;
            }
        }
        $objMetaBanco->adicionarColuna('md_pen_recibo_tramite_enviado', 'cadeia_certificado_temp', $strTipo, PenMetaBD::SNULLO);
        BancoSEI::getInstance()->executarSql("update md_pen_recibo_tramite_enviado set cadeia_certificado_temp = cadeia_certificado");
        $objMetaBanco->excluirColuna('md_pen_recibo_tramite_enviado', 'cadeia_certificado');
        try { 
            $objMetaBanco->renomearColuna('md_pen_recibo_tramite_enviado', 'cadeia_certificado_temp', 'cadeia_certificado', $strTipo);
        }catch(Exception $e){
            if (strpos($e->__toString(),'Caution: Changing any part of an object name could break scripts and stored procedures.')===false){
              throw $e;
            }
        }

        /* ---------- antigo m�todo (instalarV005R003S005IW018) ---------- */
        $objBD = new TarefaBD(BancoSEI::getInstance());
        $objDTO = new TarefaDTO();

        $fnCadastrar = function($strNome='', $strHistoricoResumido='N', $strHistoricoCompleto='N', $strFecharAndamentosAbertos='N', $strLancarAndamentoFechado='N', $strPermiteProcessoFechado='N', $strIdTarefaModulo='') use($objDTO, $objBD) {

            $objDTO->unSetTodos();
            $objDTO->setStrIdTarefaModulo($strIdTarefaModulo);

            if ($objBD->contar($objDTO) == 0) {

                $objUltimaTarefaDTO = new TarefaDTO();
                $objUltimaTarefaDTO->retNumIdTarefa();
                $objUltimaTarefaDTO->setNumMaxRegistrosRetorno(1);
                $objUltimaTarefaDTO->setOrd('IdTarefa', InfraDTO::$TIPO_ORDENACAO_DESC);
                $objUltimaTarefaDTO = $objBD->consultar($objUltimaTarefaDTO);

                $objDTO->setNumIdTarefa($objUltimaTarefaDTO->getNumIdTarefa() + 1);
                $objDTO->setStrNome($strNome);
                $objDTO->setStrSinHistoricoResumido($strHistoricoResumido);
                $objDTO->setStrSinHistoricoCompleto($strHistoricoCompleto);
                $objDTO->setStrSinFecharAndamentosAbertos($strFecharAndamentosAbertos);
                $objDTO->setStrSinLancarAndamentoFechado($strLancarAndamentoFechado);
                $objDTO->setStrSinPermiteProcessoFechado($strPermiteProcessoFechado);
                $objDTO->setStrIdTarefaModulo($strIdTarefaModulo);
                $objBD->cadastrar($objDTO);
            }
        };

        $fnCadastrar('O tr�mite externo do processo foi abortado manualmente devido a falha no tr�mite', 'S', 'S', 'N', 'N', 'S', 'PEN_EXPEDICAO_PROCESSO_ABORTADA');

        /* ---------- antigo m�todo (instalarV005R003S005IW023) ---------- */
        $objBD = new TarefaBD(BancoSEI::getInstance());

        $objDTO = new TarefaDTO();
        $objDTO->retNumIdTarefa();
        $objDTO->retStrNome();

        $fnAtualizar = function($strIdTarefaModulo, $strNome) use($objDTO, $objBD) {

            $objDTO->unSetTodos();
            $objDTO->setStrIdTarefaModulo($strIdTarefaModulo);

            $objTarefaDTO = $objBD->consultar($objDTO);

            if (!empty($objTarefaDTO)) {

                $objTarefaDTO->setStrNome($strNome);

                $objBD->alterar($objTarefaDTO);
            }
        };
        // Tramita��o externa do processo @processo@ conclu�da com sucesso. Recebido na @UnidadeDestino@ - @hierarquia_superior@ -@reposit�rio_de_estruturas@
        $fnAtualizar('PEN_PROCESSO_EXPEDIDO', 'Processo em tramita��o externa para @UNIDADE_DESTINO@ - @UNIDADE_DESTINO_HIRARQUIA@ - @REPOSITORIO_DESTINO@');
        $fnAtualizar('PEN_PROCESSO_RECEBIDO', 'Processo recebido da unidade externa @ENTIDADE_ORIGEM@ - @ENTIDADE_ORIGEM_HIRARQUIA@ - @REPOSITORIO_ORIGEM@');
        $fnAtualizar('PEN_OPERACAO_EXTERNA', 'Tramita��o externa do processo @PROTOCOLO_FORMATADO@ conclu�da com sucesso. Recebido em @UNIDADE_DESTINO@ - @UNIDADE_DESTINO_HIRARQUIA@ - @REPOSITORIO_DESTINO@');

        /* ---------- antigo m�todo (instalarV006R004S004WI001) ---------- */
        $objInfraParametro = new InfraParametro(BancoSEI::getInstance());
        $objInfraParametro->setValor('PEN_TAMANHO_MAXIMO_DOCUMENTO_EXPEDIDO', 50);

        /* ---------- antigo m�todo (instalarV007R004S005WI002) ---------- */

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_recibo_tramite_hash',
            'cols' => array(
                'id_tramite_hash' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'numero_registro' => array($objMetaBD->tipoTextoFixo(16), PenMetaBD::NNULLO),
                'id_tramite' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'tipo_recibo' => array($objMetaBD->tipoTextoFixo(1), PenMetaBD::NNULLO),
                'hash_componente_digital ' => array($objMetaBD->tipoTextoVariavel(255), PenMetaBD::NNULLO)
            ),
            'pk' => array('cols'=>array('id_tramite_hash')),
            'fks' => array(
                'md_pen_tramite' => array('nome' => 'fk_md_pen_rec_tram_hash_tram', 'cols' => array(array('numero_registro', 'id_tramite'), array('numero_registro', 'id_tramite')))
            )
        ));

        $objMetaBD->adicionarColuna('md_pen_recibo_tramite_recebido', 'cadeia_certificado', $this->inicializarObjMetaBanco()->tipoTextoGrande(), PenMetaBD::SNULLO);

        $objInfraSequencia = new InfraSequencia(BancoSEI::getInstance());
        if (!$objInfraSequencia->verificarSequencia('md_pen_recibo_tramite_hash')) {
            $objInfraSequencia->criarSequencia('md_pen_recibo_tramite_hash', '1', '1', '9999999999');
        }

        $objInfraParametroDTO = new InfraParametroDTO();
        $objInfraParametroDTO->setStrNome(PENIntegracao::PARAMETRO_VERSAO_MODULO_ANTIGO);
        $objInfraParametroDTO->setStrValor('1.0.0');
        $objInfraParametroBD = new InfraParametroBD(BancoSEI::getInstance());
        $objInfraParametroBD->cadastrar($objInfraParametroDTO);

        $this->logar(' EXECUTADA A INSTALACAO DA VERSAO 0.0.1 DO MODULO PEN NO SEI COM SUCESSO');
    }

    /* Cont�m atualiza��es da versao 1.0.1 do modulo */
    protected function instalarV101()
    {
        $objBD = new TarefaBD(BancoSEI::getInstance());

        $objTarefaDTO = new TarefaDTO();
        $objTarefaDTO->setStrIdTarefaModulo('PEN_PROCESSO_RECEBIDO');
        $objTarefaDTO->retNumIdTarefa();

        $objTarefaDTO = $objBD->consultar($objTarefaDTO);

        $objTarefaDTO->setStrSinLancarAndamentoFechado('N');
        $objTarefaDTO->setStrSinPermiteProcessoFechado('S');

        $objBD->alterar($objTarefaDTO);

        /* ---------- antigo m�todo (instalarV006R004S001US039) ---------- */
        $objMetaBD = $this->inicializarObjMetaBanco();
        $objInfraBanco = BancoSEI::getInstance();

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_hipotese_legal',
            'cols' => array(
                'id_hipotese_legal'=> array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'nome' => array($objMetaBD->tipoTextoVariavel(255), PenMetaBD::NNULLO),
                'sin_ativo' => array($objMetaBD->tipoTextoFixo(1), 'S'),
            ),
            'pk' => array('cols'=>array('id_hipotese_legal')),
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_rel_hipotese_legal',
            'cols' => array(
                'id_mapeamento' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'id_hipotese_legal'=> array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_hipotese_legal_pen'=> array($objMetaBD->tipoNumero(), PenMetaBD::SNULLO),
                'tipo' => array($objMetaBD->tipoTextoFixo(1), 'E'),
                'sin_ativo' => array($objMetaBD->tipoTextoFixo(1), 'S'),
            ),
            'pk' => array('cols'=>array('id_mapeamento')),
            'fks' => array(
                'hipotese_legal' => array('nome' => 'fk_md_pen_rel_hipotese_legal', 'cols' => array('id_hipotese_legal', 'id_hipotese_legal')),
                'md_pen_hipotese_legal' => array('nome' => 'fk_md_pen_rel_hipotese_pen', 'cols' => array('id_hipotese_legal', 'id_hipotese_legal_pen'))
            )
        ));

        $objInfraSequencia = new InfraSequencia($objInfraBanco);

        if(!$objInfraSequencia->verificarSequencia('md_pen_hipotese_legal')){
            $objInfraSequencia->criarSequencia('md_pen_hipotese_legal', '1', '1', '9999999999');
        }

        if(!$objInfraSequencia->verificarSequencia('md_pen_rel_hipotese_legal')){
            $objInfraSequencia->criarSequencia('md_pen_rel_hipotese_legal', '1', '1', '9999999999');
        }

        $objHipoteseLegalDTO = new HipoteseLegalDTO();
        $objHipoteseLegalDTO->setDistinct(true);
        $objHipoteseLegalDTO->setStrStaNivelAcesso(1);
        $objHipoteseLegalDTO->setOrdStrNome(InfraDTO::$TIPO_ORDENACAO_ASC);
        $objHipoteseLegalDTO->retNumIdHipoteseLegal();
        $objHipoteseLegalDTO->retStrNome();

        $objMetaBD = $this->inicializarObjMetaBanco();

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_parametro',
            'cols' => array(
                'nome'=> array($objMetaBD->tipoTextoVariavel(100), PenMetaBD::NNULLO),
                'valor' => array($objMetaBD->tipoTextoGrande(), PenMetaBD::SNULLO)
            ),
            'pk' => array('cols'=>array('nome')),
        ));

        //Agendamento
        $objDTO = new InfraAgendamentoTarefaDTO();

        $fnCadastrar = function($strComando, $strDesc) use($objDTO, $objBD, $objRN) {

            $objDTO->unSetTodos();
            $objDTO->setStrComando($strComando);

            if ($objBD->contar($objDTO) == 0) {

                $objDTO->setStrDescricao($strDesc);
                $objDTO->setStrStaPeriodicidadeExecucao('D');
                $objDTO->setStrPeriodicidadeComplemento('0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23');
                $objDTO->setStrSinAtivo('S');
                $objDTO->setStrSinSucesso('S');

                $objBD->cadastrar($objDTO);
            }
        };

        $fnCadastrar('PENAgendamentoRN::atualizarHipotesesLegais', 'Verifica��o se h� novas hip�teses legais do barramento.');

        $this->atualizarNumeroVersao("1.0.1");
    }

    /* Cont�m atualiza��es da versao 1.1.0 do modulo */
    protected function instalarV110() {
        $objMetaBD = $this->objMeta;

        //Adiciona a coluna de indentifica��o nas hip�teses que vem do barramento
        $objMetaBD->adicionarColuna('md_pen_hipotese_legal', 'identificacao', $this->inicializarObjMetaBanco()->tipoNumero(), PenMetaBD::SNULLO);

        //Adiciona a coluna de descricao nos par�metros
        $objMetaBD->adicionarColuna('md_pen_parametro', 'descricao', $this->inicializarObjMetaBanco()->tipoTextoVariavel(255), PenMetaBD::SNULLO);

        //Cria os par�metros do m�dulo PEN barramento (md_pen_parametro [ nome, valor ])
        $this->criarParametro('PEN_ENDERECO_WEBSERVICE', 'https://pen-api.trafficmanager.net/interoperabilidade/soap/v3/', 'Endere�o do Web Service');
        $this->criarParametro('PEN_ENDERECO_WEBSERVICE_PENDENCIAS', 'https://pen-pendencias.trafficmanager.net/', 'Endere�o do Web Service de Pend�ncias');
        $this->criarParametro('PEN_ENVIA_EMAIL_NOTIFICACAO_RECEBIMENTO', 'N', 'Envia E-mail de Notifica��o de Recebimento');
        $this->criarParametro('PEN_ID_REPOSITORIO_ORIGEM', '1', 'ID do Reposit�rio de Origem');
        $this->criarParametro('PEN_LOCALIZACAO_CERTIFICADO_DIGITAL', '/opt/sei/config/mod-pen/certificado.pem', 'Localiza��o do Certificado Digital');
        $this->criarParametro('PEN_NUMERO_TENTATIVAS_TRAMITE_RECEBIMENTO', '3', 'N�mero M�ximo de Tentativas de Recebimento');
        $this->criarParametro('PEN_SENHA_CERTIFICADO_DIGITAL', '1234', 'Senha do Certificado Digital');
        $this->criarParametro('PEN_TAMANHO_MAXIMO_DOCUMENTO_EXPEDIDO', '50', 'Tamanho M�ximo de Documento Expedido');
        $this->criarParametro('PEN_TIPO_PROCESSO_EXTERNO', '', 'Tipo de Processo Externo');
        $this->criarParametro('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO', '', 'Unidade Geradora de Processo e Documento Recebido');

        //Deleta os par�metros do infra_parametros
        $this->deletaParametroInfra('PEN_ENDERECO_WEBSERVICE');
        $this->deletaParametroInfra('PEN_ENDERECO_WEBSERVICE_PENDENCIAS');
        $this->deletaParametroInfra('PEN_ENVIA_EMAIL_NOTIFICACAO_RECEBIMENTO');
        $this->deletaParametroInfra('PEN_ID_REPOSITORIO_ORIGEM');
        $this->deletaParametroInfra('PEN_LOCALIZACAO_CERTIFICADO_DIGITAL');
        $this->deletaParametroInfra('PEN_NUMERO_TENTATIVAS_TRAMITE_RECEBIMENTO');
        $this->deletaParametroInfra('PEN_SENHA_CERTIFICADO_DIGITAL');
        $this->deletaParametroInfra('PEN_TAMANHO_MAXIMO_DOCUMENTO_EXPEDIDO');
        $this->deletaParametroInfra('PEN_TIPO_PROCESSO_EXTERNO');
        $this->deletaParametroInfra('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO');

        //Alterar nomeclatura do recurso
        $objDTO = new PenParametroDTO();
        $objDTO->setStrNome('HIPOTESE_LEGAL_PADRAO');
        $objDTO->retStrNome();
        $objBD = new PenParametroBD(BancoSEI::getInstance());
        $objDTO = $objBD->consultar($objDTO);
        if ($objDTO) {
            $objDTO->setStrDescricao('Hip�tese Legal Padr�o');
            $objBD->alterar($objDTO);
        } else {
            $objDTO = new PenParametroDTO();
            $objDTO->setStrNome('HIPOTESE_LEGAL_PADRAO');
            $objDTO->setStrValor(1);
            $objDTO->setStrDescricao('Hip�tese Legal Padr�o');
            $objBD->cadastrar($objDTO);
        }

        $this->atualizarNumeroVersao("1.1.0");
    }

    /* Cont�m atualiza��es da versao 1.1.1 do m�dulo */
    protected function instalarV111() {

        //Ajuste em nome da vari�vel de vers�o do m�dulo VERSAO_MODULO_PEN
        BancoSEI::getInstance()->executarSql("update infra_parametro set nome = '" . PENIntegracao::PARAMETRO_VERSAO_MODULO . "' where nome = '" . PENIntegracao::PARAMETRO_VERSAO_MODULO_ANTIGO . "'");

        $this->atualizarNumeroVersao("1.1.1");
    }

    /* Cont�m atualiza��es da versao 1.1.7 do m�dulo */
    protected function instalarV117() {

        /* Cadastramento de novas esp�cies documentais */
        $objEspecieDocumentalBD = new EspecieDocumentalBD(BancoSEI::getInstance());
        $objEspecieDocumentalDTO = new EspecieDocumentalDTO();

        $fnCadastrar = function($dblIdEspecie, $strNomeEspecie, $strDescricao) use($objEspecieDocumentalDTO, $objEspecieDocumentalBD) {
            $objEspecieDocumentalDTO->unSetTodos();
            $objEspecieDocumentalDTO->setDblIdEspecie($dblIdEspecie);
            if ($objEspecieDocumentalBD->contar($objEspecieDocumentalDTO) == 0) {
                $objEspecieDocumentalDTO->setStrNomeEspecie($strNomeEspecie);
                // Descri��o da esp�cie documental n�o mais necess�ria a partir da vers�o 2.0.0
                //$objEspecieDocumentalDTO->setStrDescricao($strDescricao);
                $objEspecieDocumentalBD->cadastrar($objEspecieDocumentalDTO);
            }
        };

        $fnCadastrar(178, 'Alega��es', 'Muito comum no Judici�rio, tendo previs�o no CPC. Podendo ser complementado "Finais", o que representaria o documento "Alega��es Finais".');
        $fnCadastrar(179, 'Anexo', 'Documento ou processo juntado em car�ter definitivo a outro processo, para dar continuidade a uma a��o administrativa.');
        $fnCadastrar(180, 'Documento', 'Informa��o registrada, qualquer que seja o suporte ou formato, que n�o est� reunida e ordenada em processo.');
        $fnCadastrar(181, 'Apartado', 'Apartado por si s�, autos apartados ou partado sigiloso.');
        $fnCadastrar(182, 'Apresenta��o', 'Documentos que s�o apresenta��es propriamente ditas.');
        $fnCadastrar(183, 'Diagn�stico', 'Diagn�stico m�dico, auditoria, etc.');
        $fnCadastrar(184, 'Exame', 'Exame laboratorial, m�dico, etc.');
        $fnCadastrar(185, 'P�gina', 'P�gina do Di�rio Oficial da Uni�o.');
        $fnCadastrar(186, 'Estudo', 'Podendo ser complementado com "T�cnico Preliminar da Contrata��o"; "T�cnico".');
        $fnCadastrar(999, 'Outra', 'Outras esp�cies documentais n�o identificadas.');

        $this->atualizarNumeroVersao("1.1.7");
    }

    /* Cont�m atualiza��es da versao 1.1.8 do m�dulo */
    protected function instalarV118()
    {
        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());

        //Corre��o de chave prim�ria para considerar campo de tipo de recibo
        $this->excluirChavePrimariaComIndice('md_pen_tramite_processado','pk_md_pen_tramite_processado');
        $objInfraMetaBD->adicionarChavePrimaria('md_pen_tramite_processado','pk_md_pen_tramite_processado',array('id_tramite','tipo_tramite_processo'));

        //Atribui��o de dados da unidade de origem e destino no tr�mite
        $objInfraMetaBD->adicionarColuna('md_pen_tramite','id_repositorio_origem', $objInfraMetaBD->tipoNumero(16), 'null');
        $objInfraMetaBD->adicionarColuna('md_pen_tramite','id_estrutura_origem', $objInfraMetaBD->tipoNumero(16), 'null');
        $objInfraMetaBD->adicionarColuna('md_pen_tramite','id_repositorio_destino', $objInfraMetaBD->tipoNumero(16), 'null');
        $objInfraMetaBD->adicionarColuna('md_pen_tramite','id_estrutura_destino', $objInfraMetaBD->tipoNumero(16), 'null');

        $this->atualizarNumeroVersao("1.1.8");
    }

    /* Cont�m atualiza��es da versao 1.1.9 do m�dulo */
    protected function instalarV119()
    {
        $this->atualizarNumeroVersao("1.1.9");
    }


    /* Cont�m atualiza��es da versao 1.1.10 do m�dulo */
    protected function instalarV1110()
    {
        $this->atualizarNumeroVersao("1.1.10");
    }

    /* Cont�m atualiza��es da versao 1.1.11 do m�dulo */
    protected function instalarV1111() {
        BancoSEI::getInstance()->executarSql("DELETE FROM participante WHERE EXISTS (SELECT md_pen_processo_eletronico.id_procedimento FROM md_pen_processo_eletronico WHERE md_pen_processo_eletronico.id_procedimento = participante.id_protocolo AND participante.sta_participacao='R')");

        $this->atualizarNumeroVersao("1.1.11");
    }


    /* Cont�m atualiza��es da versao 1.1.12 do m�dulo */
    protected function instalarV1112() {
        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());

        //[#22] Corre��o de erro de consist�ncia no recebimento de processos com concorr�ncia
        $objInfraMetaBD->adicionarColuna('md_pen_tramite','sta_tipo_tramite', $objInfraMetaBD->tipoTextoFixo(1), 'null');
        $objInfraMetaBD->alterarColuna('md_pen_procedimento_andamento','id_procedimento',$objInfraMetaBD->tipoNumeroGrande(),'null');
        $objInfraMetaBD->adicionarColuna('md_pen_procedimento_andamento','numero_registro', $objInfraMetaBD->tipoTextoFixo(16), 'null');

        $objTramiteDTO = new TramiteDTO();
        $objTramiteDTO->retNumIdTramite();
        $objTramiteDTO->retStrNumeroRegistro();

        $objTramiteRN = new TramiteBD(BancoSEI::getInstance());
        $arrObjTramiteDTO = $objTramiteRN->listar($objTramiteDTO);

        foreach ($arrObjTramiteDTO as $objTramiteDTO) {
            $objProcedimentoAndamentoDTO = new ProcedimentoAndamentoDTO();
            $objProcedimentoAndamentoDTO->retDblIdAndamento();
            $objProcedimentoAndamentoDTO->retStrNumeroRegistro();
            $objProcedimentoAndamentoDTO->retDblIdTramite();
            $objProcedimentoAndamentoDTO->setDblIdTramite($objTramiteDTO->getNumIdTramite());

            $objProcedimentoAndamentoBD = new ProcedimentoAndamentoBD(BancoSEI::getInstance());
            $arrObjProcedimentoAndamentoDTO = $objProcedimentoAndamentoBD->listar($objProcedimentoAndamentoDTO);
            foreach ($arrObjProcedimentoAndamentoDTO as $objProcedimentoAndamentoDTO) {

                $objProcedimentoAndamentoDTO->setStrNumeroRegistro($objTramiteDTO->getStrNumeroRegistro());
                $objProcedimentoAndamentoBD->alterar($objProcedimentoAndamentoDTO);
            }
        }

        $this->atualizarNumeroVersao("1.1.12");
    }

    /* Cont�m atualiza��es da versao 1.1.13 do m�dulo */
    protected function instalarV1113() {

        //Fix-31 - Erro ao Configurar Campo 'numero_registro' como Not Null no Scritp de atualiza<E7><E3>o
        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());
        $objInfraMetaBD->alterarColuna('md_pen_procedimento_andamento','numero_registro', $objInfraMetaBD->tipoTextoFixo(16), 'null');

        $this->atualizarNumeroVersao("1.1.13");
    }

    /* Cont�m atualiza��es da versao 1.1.14 do m�dulo */
    protected function instalarV1114()
    {
        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());
        $objInfraSequencia = new InfraSequencia(BancoSEI::getInstance());

        SessaoSEI::getInstance(false)->simularLogin(SessaoSEI::$USUARIO_SEI, SessaoSEI::$UNIDADE_TESTE);
        SessaoInfra::setObjInfraSessao(SessaoSEI::getInstance());
        BancoInfra::setObjInfraIBanco(BancoSEI::getInstance());

        //[Fix-35] Corre��o de erro de integridade ao retornar mais de um elemento na consulta de mapeamento
        $objInfraMetaBD->criarIndice('md_pen_rel_doc_map_enviado', 'ak1_rel_doc_map_enviado', array('id_serie'), true);
        $objInfraMetaBD->criarIndice('md_pen_rel_doc_map_recebido', 'ak1_rel_doc_map_recebido', array('codigo_especie'), true);

        //30 - Corre��o de erros de chave duplicada devido a concorr�ncia de transa��es
        $objInfraSequenciaRN = new InfraSequenciaRN();
        $objInfraSequenciaDTO = new InfraSequenciaDTO();

        //Sequ�ncia: md_pen_seq_procedimento_andam
        $rs = BancoSEI::getInstance()->consultarSql('select max(id_andamento) as total from md_pen_procedimento_andamento');
        $numMaxId = $rs[0]['total'];
        if ($numMaxId==null){
            $numMaxId = 0;
        }
        BancoSEI::getInstance()->criarSequencialNativa('md_pen_seq_procedimento_andam', $numMaxId + 1);
        $objInfraSequenciaDTO->setStrNome('md_pen_procedimento_andamento');
        $objInfraSequenciaDTO->retStrNome();
        $arrObjInfraSequenciaDTO = $objInfraSequenciaRN->listar($objInfraSequenciaDTO);
        $objInfraSequenciaRN->excluir($arrObjInfraSequenciaDTO);


        //Sequ�ncia: md_pen_seq_hipotese_legal
        $rs = BancoSEI::getInstance()->consultarSql('select max(id_hipotese_legal) as total from md_pen_hipotese_legal');
        $numMaxId = $rs[0]['total'];
        if ($numMaxId==null){
            $numMaxId = 0;
        }
        BancoSEI::getInstance()->criarSequencialNativa('md_pen_seq_hipotese_legal', $numMaxId + 1);
        $objInfraSequenciaDTO->setStrNome('md_pen_hipotese_legal');
        $objInfraSequenciaDTO->retStrNome();
        $arrObjInfraSequenciaDTO = $objInfraSequenciaRN->listar($objInfraSequenciaDTO);
        $objInfraSequenciaRN->excluir($arrObjInfraSequenciaDTO);


        //Sequ�ncia: md_pen_seq_rel_hipotese_legal
        $rs = BancoSEI::getInstance()->consultarSql('select max(id_mapeamento) as total from md_pen_rel_hipotese_legal');
        $numMaxId = $rs[0]['total'];
        if ($numMaxId==null){
            $numMaxId = 0;
        }
        BancoSEI::getInstance()->criarSequencialNativa('md_pen_seq_rel_hipotese_legal', $numMaxId + 1);
        $objInfraSequenciaDTO->setStrNome('md_pen_rel_hipotese_legal');
        $objInfraSequenciaDTO->retStrNome();
        $arrObjInfraSequenciaDTO = $objInfraSequenciaRN->listar($objInfraSequenciaDTO);
        $objInfraSequenciaRN->excluir($arrObjInfraSequenciaDTO);


        //Sequ�ncia: md_pen_seq_recibo_tramite_hash
        $rs = BancoSEI::getInstance()->consultarSql('select max(id_tramite_hash) as total from md_pen_recibo_tramite_hash');
        $numMaxId = $rs[0]['total'];
        if ($numMaxId==null){
            $numMaxId = 0;
        }
        BancoSEI::getInstance()->criarSequencialNativa('md_pen_seq_recibo_tramite_hash', $numMaxId + 1);
        $objInfraSequenciaDTO->setStrNome('md_pen_recibo_tramite_hash');
        $objInfraSequenciaDTO->retStrNome();
        $arrObjInfraSequenciaDTO = $objInfraSequenciaRN->listar($objInfraSequenciaDTO);
        $objInfraSequenciaRN->excluir($arrObjInfraSequenciaDTO);

        //Sequ�ncia: md_pen_seq_rel_doc_map_enviado
        $rs = BancoSEI::getInstance()->consultarSql('select max(id_mapeamento) as total from md_pen_rel_doc_map_enviado');
        $numMaxId = $rs[0]['total'];
        if ($numMaxId==null){
            $numMaxId = 0;
        }
        BancoSEI::getInstance()->criarSequencialNativa('md_pen_seq_rel_doc_map_enviado', $numMaxId + 1);
        $objInfraSequenciaDTO->setStrNome('md_pen_rel_doc_map_enviado');
        $objInfraSequenciaDTO->retStrNome();
        $arrObjInfraSequenciaDTO = $objInfraSequenciaRN->listar($objInfraSequenciaDTO);
        $objInfraSequenciaRN->excluir($arrObjInfraSequenciaDTO);

        //Sequ�ncia: md_pen_seq_rel_doc_map_recebid
        $rs = BancoSEI::getInstance()->consultarSql('select max(id_mapeamento) as total from md_pen_rel_doc_map_recebido');
        $numMaxId = $rs[0]['total'];
        if ($numMaxId==null){
            $numMaxId = 0;
        }
        BancoSEI::getInstance()->criarSequencialNativa('md_pen_seq_rel_doc_map_recebid', $numMaxId + 1);
        $objInfraSequenciaDTO->setStrNome('md_pen_rel_doc_map_recebido');
        $objInfraSequenciaDTO->retStrNome();
        $arrObjInfraSequenciaDTO = $objInfraSequenciaRN->listar($objInfraSequenciaDTO);
        $objInfraSequenciaRN->excluir($arrObjInfraSequenciaDTO);


        //Sequ�ncia: md_pen_seq_tramite_pendente
        $rs = BancoSEI::getInstance()->consultarSql('select max(id) as total from md_pen_tramite_pendente');
        $numMaxId = $rs[0]['total'];
        if ($numMaxId==null){
            $numMaxId = 0;
        }
        BancoSEI::getInstance()->criarSequencialNativa('md_pen_seq_tramite_pendente', $numMaxId + 1);
        $objInfraSequenciaDTO->setStrNome('md_pen_tramite_pendente');
        $objInfraSequenciaDTO->retStrNome();
        $arrObjInfraSequenciaDTO = $objInfraSequenciaRN->listar($objInfraSequenciaDTO);
        $objInfraSequenciaRN->excluir($arrObjInfraSequenciaDTO);

        //Fix 28 - Erro Data too long for column 'nome' at row 1
        $objInfraMetaBD->alterarColuna('md_pen_componente_digital','nome', $objInfraMetaBD->tipoTextoVariavel(255), 'not null');

        $this->atualizarNumeroVersao("1.1.14");
    }


    /* Cont�m atualiza��es da versao 1.1.15 do m�dulo */
    protected function instalarV1115() {

        //Fix-31 - Erro ao Configurar Campo 'numero_registro' como Not Null no Scritp de atualiza<E7><E3>o
        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());
        $objInfraMetaBD->alterarColuna('md_pen_procedimento_andamento','numero_registro', $objInfraMetaBD->tipoTextoFixo(16), 'null');

        $this->atualizarNumeroVersao("1.1.15");
    }

    /* Cont�m atualiza��es da versao 1.1.16 do m�dulo */
    protected function instalarV1116() {

        //Fix-31 - Erro ao Configurar Campo 'numero_registro' como Not Null no Scritp de atualiza<E7><E3>o
        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());
        $objInfraMetaBD->alterarColuna('md_pen_procedimento_andamento','numero_registro', $objInfraMetaBD->tipoTextoFixo(16), 'null');

        $this->atualizarNumeroVersao("1.1.16");
    }

    /* Cont�m atualiza��es da versao 1.1.17 do m�dulo */
    protected function instalarV1117() {

        // Defini��o de fun��o an�nima respons�vel por realizar as seguintes tarefas:
        //  (1) Identificar a tarefa com ID conflitante do SEI
        //  (2) Criar nova tarefa identica mas com ID correto dentro das faixas definidas pelo SEI (maior que 1000)
        //  (3) Atualizar o id_tarefa de todas as atividades relacionadas
        //  (4) Remover a tarefa anterior com ID inv�lido
        //  (5) Atualizar o campo id_tarefa_modulo com o valor correspondente
        $fnCadastrar = function($numIdTarefa, $strIdTarefaModulo) {

            // Identificar a tarefa com ID conflitante do SEI
            $objTarefaRN = new TarefaRN();
            $objTarefaBD = new TarefaBD(BancoSEI::getInstance());
            $objTarefaDTOAntigo = new TarefaDTO();
            $objTarefaDTOAntigo->retTodos();
            $objTarefaDTOAntigo->setStrIdTarefaModulo($strIdTarefaModulo);
            $objTarefaDTOAntigo = $objTarefaBD->consultar($objTarefaDTOAntigo);

            if(isset($objTarefaDTOAntigo)) {
                try {
                    // Criar nova tarefa identica mas com ID correto dentro das faixas definidas pelo SEI (maior que 1000)
                    InfraDebug::getInstance()->gravar("Duplicando tarefa customizadas $strIdTarefaModulo utilizando o controle de sequ�ncia 1000");
                    $objTarefaDTO = new TarefaDTO();
                    $objTarefaDTO->setNumIdTarefa($numIdTarefa);
                    $objTarefaDTO->setStrNome($objTarefaDTOAntigo->getStrNome());
                    $objTarefaDTO->setStrSinHistoricoResumido($objTarefaDTOAntigo->getStrSinHistoricoResumido());
                    $objTarefaDTO->setStrSinHistoricoCompleto($objTarefaDTOAntigo->getStrSinHistoricoCompleto());
                    $objTarefaDTO->setStrSinFecharAndamentosAbertos($objTarefaDTOAntigo->getStrSinFecharAndamentosAbertos());
                    $objTarefaDTO->setStrSinLancarAndamentoFechado($objTarefaDTOAntigo->getStrSinLancarAndamentoFechado());
                    $objTarefaDTO->setStrSinPermiteProcessoFechado($objTarefaDTOAntigo->getStrSinPermiteProcessoFechado());
                    $objTarefaDTO->setStrIdTarefaModulo(null);
                    $objTarefaBD->cadastrar($objTarefaDTO);

                    // Atualizar o id_tarefa de todas as atividades relacionadas
                    InfraDebug::getInstance()->gravar("Atualizando atividades com chave da nova tarefa $strIdTarefaModulo");
                    $numIdTarefaAnterior = $objTarefaDTOAntigo->getNumIdTarefa();
                    BancoSEI::getInstance()->executarSql("UPDATE atividade SET id_tarefa = $numIdTarefa where id_tarefa = $numIdTarefaAnterior");

                    // Remover a tarefa anterior com ID inv�lido
                    InfraDebug::getInstance()->gravar("Apagando a tarefa anterior $strIdTarefaModulo");
                    $objTarefaBD->excluir($objTarefaDTOAntigo);

                    // Atualizar o campo id_tarefa_modulo com o valor correspondente
                    $objTarefaDTOUpdate = new TarefaDTO();
                    $objTarefaDTOUpdate->setNumIdTarefa($numIdTarefa);
                    $objTarefaDTOUpdate->setStrIdTarefaModulo($strIdTarefaModulo);
                    $objTarefaBD->alterar($objTarefaDTOUpdate);

                } catch (Exception $e) {
                    throw new InfraException($e);
                }
            }
        };

        $rsTabelaTarefa = BancoSEI::getInstance()->consultarSql('select max(id_tarefa) as ultimo from tarefa');
        $numMaxId = $rsTabelaTarefa[0]['ultimo'];
        if (!isset($numMaxId) || $numMaxId < 1000){
            $numMaxId = 1000;
        }

        $fnCadastrar(++$numMaxId, 'PEN_PROCESSO_EXPEDIDO');
        $fnCadastrar(++$numMaxId, 'PEN_PROCESSO_RECEBIDO');
        $fnCadastrar(++$numMaxId, 'PEN_PROCESSO_RECUSADO');
        $fnCadastrar(++$numMaxId, 'PEN_PROCESSO_CANCELADO');
        $fnCadastrar(++$numMaxId, 'PEN_OPERACAO_EXTERNA');
        $fnCadastrar(++$numMaxId, 'PEN_EXPEDICAO_PROCESSO_ABORTADA');

        InfraDebug::getInstance()->gravar('Atualizando sequ�ncia das tabelas do sistema');

        //Na vers�o 3.1.0 do SEI, houve uma mudan�a na rotina de atualiza��o das sequences do banco de dados,
        //deixando de se utilizar a classe VersaoRN para utilizar a nova classe ScriptRN.
        //Devido a esta mudan�a, � necess�rio avaliar qual a atual vers�o do SEI executar a rotina correta

        //Normaliza o formato de n�mero de vers�o considerando dois caracteres para cada item (3.1.0 -> 030100)
        $numVersaoAtualSEI = explode('.', SEI_VERSAO);
        $numVersaoAtualSEI = array_map(function($item){ return str_pad($item, 2, '0', STR_PAD_LEFT); }, $numVersaoAtualSEI);
        $numVersaoAtualSEI = intval(join($numVersaoAtualSEI));

        //Normaliza o formato de n�mero de vers�o considerando dois caracteres para cada item (3.1.0 -> 030100)
        $numVersaoMudancaAtualizarSequencias = explode('.', '3.1.0');
        $numVersaoMudancaAtualizarSequencias = array_map(function($item){ return str_pad($item, 2, '0', STR_PAD_LEFT); }, $numVersaoMudancaAtualizarSequencias);
        $numVersaoMudancaAtualizarSequencias = intval(join($numVersaoMudancaAtualizarSequencias));

        if($numVersaoMudancaAtualizarSequencias <= $numVersaoAtualSEI){
            //Procedimento de atualiza��o de sequ�ncias compat�vel com SEI 3.1.X
            $objScriptRN = new ScriptRN();
            $objScriptRN->atualizarSequencias();
        } else {
            //Procedimento de atualiza��o de sequ�ncias compat�vel com SEI 3.0.X
            $objVersaoRN = new VersaoRN();
            $objVersaoRN->atualizarSequencias();
        }

        $this->atualizarNumeroVersao("1.1.17");
    }


    /* Cont�m atualiza��es da versao 1.2.0 do m�dulo */
    protected function instalarV1200()
    {
        $this->atualizarNumeroVersao("1.2.0");
    }

    /* Cont�m atualiza��es da versao 1.2.1 do m�dulo */
    protected function instalarV1201()
    {
        //Fix-47 - Corrigir erro com mapeamento de esp�cies documentais da origem
        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());
        $objInfraMetaBD->adicionarColuna('md_pen_componente_digital', 'codigo_especie', $objInfraMetaBD->tipoNumero(), 'null');
        $objInfraMetaBD->adicionarColuna('md_pen_componente_digital', 'nome_especie_produtor', $objInfraMetaBD->tipoTextoVariavel(255), 'null');

        $this->atualizarNumeroVersao("1.2.1");
    }

    /* Cont�m atualiza��es da versao 1.2.2 do m�dulo */
    protected function instalarV1202()
    {
        $this->atualizarNumeroVersao("1.2.2");
    }

    /* Cont�m atualiza��es da versao 1.2.3 do m�dulo */
    protected function instalarV1203()
    {
        $this->atualizarNumeroVersao("1.2.3");
    }

    /* Cont�m atualiza��es da versao 1.2.4 do m�dulo */
    protected function instalarV1204()
    {
        $this->atualizarNumeroVersao("1.2.4");
    }

    /* Cont�m atualiza��es da versao 1.2.5 do m�dulo */
    protected function instalarV1205()
    {
        $this->atualizarNumeroVersao("1.2.5");
    }

    /* Cont�m atualiza��es da versao 1.2.6 do m�dulo */
    protected function instalarV1206()
    {
        $this->atualizarNumeroVersao("1.2.6");
    }

    /* Cont�m atualiza��es da versao 1.3.0 do m�dulo */
    protected function instalarV1300()
    {
        //Alterar nomeclatura do recurso
        $objPenParametroDTO = new PenParametroDTO();
        $objPenParametroDTO->setStrNome('PEN_TAMANHO_MAXIMO_DOCUMENTO_EXPEDIDO');
        $objPenParametroDTO->retStrNome();
        $objPenParametroBD = new PenParametroBD(BancoSEI::getInstance());
        $objPenParametroDTO = $objPenParametroBD->consultar($objPenParametroDTO);
        if ($objPenParametroDTO) {
            $objPenParametroDTO->setStrValor(10);
            $objPenParametroDTO->setStrDescricao('Tamanho m�ximo de bloco para envio de arquivo');
            $objPenParametroBD->alterar($objPenParametroDTO);
        } else {
            $objPenParametroDTO = new PenParametroDTO();
            $objPenParametroDTO->setStrNome('PEN_TAMANHO_MAXIMO_DOCUMENTO_EXPEDIDO');
            $objPenParametroDTO->setStrValor(10);
            $objPenParametroDTO->setStrDescricao('Tamanho m�ximo de bloco para envio de arquivo');
            $objPenParametroBD->cadastrar($objPenParametroDTO);
        }

        $this->atualizarNumeroVersao("1.3.0");
    }

    /**
     * M�todo Responsavel por realizar as atualiza��es na Base de Dados referentes as novas implementa��es
     * Receber/Enviar Documento Avulso
     * Receber/Enviar Multiplos Componentes Digitais
     * @author Josinaldo J�nior <josenaldo.pedro@gmail.com>
     * @throws InfraException
     */
    protected function instalarV1400()
    {
        $objBD = new TarefaBD(BancoSEI::getInstance());
        $objDTO = new TarefaDTO();

        $fnCadastrar = function($strNome='', $strHistoricoResumido='N', $strHistoricoCompleto='N', $strFecharAndamentosAbertos='N', $strLancarAndamentoFechado='N', $strPermiteProcessoFechado='N', $strIdTarefaModulo='') use($objDTO, $objBD) {

            $objDTO->unSetTodos();
            $objDTO->setStrIdTarefaModulo($strIdTarefaModulo);

            if ($objBD->contar($objDTO) == 0) {
                $objUltimaTarefaDTO = new TarefaDTO();
                $objUltimaTarefaDTO->retNumIdTarefa();
                $objUltimaTarefaDTO->setNumMaxRegistrosRetorno(1);
                $objUltimaTarefaDTO->setOrd('IdTarefa', InfraDTO::$TIPO_ORDENACAO_DESC);
                $objUltimaTarefaDTO = $objBD->consultar($objUltimaTarefaDTO);

                $objDTO->setNumIdTarefa($objUltimaTarefaDTO->getNumIdTarefa() + 1);
                $objDTO->setStrNome($strNome);
                $objDTO->setStrSinHistoricoResumido($strHistoricoResumido);
                $objDTO->setStrSinHistoricoCompleto($strHistoricoCompleto);
                $objDTO->setStrSinFecharAndamentosAbertos($strFecharAndamentosAbertos);
                $objDTO->setStrSinLancarAndamentoFechado($strLancarAndamentoFechado);
                $objDTO->setStrSinPermiteProcessoFechado($strPermiteProcessoFechado);
                $objDTO->setStrIdTarefaModulo($strIdTarefaModulo);
                $objBD->cadastrar($objDTO);
            }
        };

        $fnCadastrar('Documento recebido da entidade @ENTIDADE_ORIGEM@ - @REPOSITORIO_ORIGEM@ (@PROCESSO@, @ENTIDADE_ORIGEM@, @UNIDADE_DESTINO@, @USUARIO@)', 'S', 'S', 'N', 'S', 'N', 'PEN_DOCUMENTO_AVULSO_RECEBIDO');

        // Modifica��es de Banco referentes a feature 76
        $objMetaBD = $this->objMeta;

        $objMetaBD->adicionarColuna('md_pen_componente_digital', 'ordem_documento', $this->inicializarObjMetaBanco()->tipoNumero(), PenMetaBD::SNULLO);
        BancoSEI::getInstance()->executarSql("update md_pen_componente_digital set ordem_documento = ordem");
        BancoSEI::getInstance()->executarSql("update md_pen_componente_digital set ordem = 1");
        $objMetaBD->alterarColuna('md_pen_componente_digital', 'ordem_documento', $this->inicializarObjMetaBanco()->tipoNumero(), PenMetaBD::NNULLO);

        // Adiciona a coluna para identificar se a cria��o do processo se deu por documento avulso (D) ou processo (P)
        // Atualizar os registros existentes para P - Tipo Processo
        $objMetaBD->adicionarColuna('md_pen_processo_eletronico', 'sta_tipo_protocolo', $this->inicializarObjMetaBanco()->tipoTextoVariavel(1), PenMetaBD::SNULLO);
        BancoSEI::getInstance()->executarSql("update md_pen_processo_eletronico set sta_tipo_protocolo = 'P'");
        $objMetaBD->alterarColuna('md_pen_processo_eletronico', 'sta_tipo_protocolo', $this->inicializarObjMetaBanco()->tipoTextoVariavel(1), PenMetaBD::NNULLO);
        $objMetaBD->adicionarValorPadraoParaColuna('md_pen_processo_eletronico', 'sta_tipo_protocolo', 'P');

        // Adicionar Chave primaria
        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());
        $this->excluirChavePrimariaComIndice('md_pen_componente_digital', 'pk_md_pen_componente_digital');
        $objInfraMetaBD->adicionarChavePrimaria('md_pen_componente_digital', 'pk_md_pen_componente_digital', array('numero_registro', 'id_procedimento', 'id_documento', 'id_tramite', 'ordem'));

        // Defini��o de ordem em que os par�metros aparecem na p�gina
        $objMetaBD->adicionarColuna('md_pen_parametro', 'sequencia', $this->inicializarObjMetaBanco()->tipoNumero(), PenMetaBD::SNULLO);
        BancoSEI::getInstance()->executarSql("update md_pen_parametro set sequencia = 1 where nome = 'PEN_ENDERECO_WEBSERVICE'");
        BancoSEI::getInstance()->executarSql("update md_pen_parametro set sequencia = 2 where nome = 'PEN_ENDERECO_WEBSERVICE_PENDENCIAS'");
        BancoSEI::getInstance()->executarSql("update md_pen_parametro set sequencia = 3 where nome = 'PEN_LOCALIZACAO_CERTIFICADO_DIGITAL'");
        BancoSEI::getInstance()->executarSql("update md_pen_parametro set sequencia = 4 where nome = 'PEN_SENHA_CERTIFICADO_DIGITAL'");
        BancoSEI::getInstance()->executarSql("update md_pen_parametro set sequencia = 5 where nome = 'PEN_ID_REPOSITORIO_ORIGEM'");
        BancoSEI::getInstance()->executarSql("update md_pen_parametro set sequencia = 6 where nome = 'PEN_TIPO_PROCESSO_EXTERNO'");
        BancoSEI::getInstance()->executarSql("update md_pen_parametro set sequencia = 7 where nome = 'PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO'");
        BancoSEI::getInstance()->executarSql("update md_pen_parametro set sequencia = 8 where nome = 'PEN_ENVIA_EMAIL_NOTIFICACAO_RECEBIMENTO'");
        BancoSEI::getInstance()->executarSql("update md_pen_parametro set sequencia = 9 where nome = 'PEN_NUMERO_TENTATIVAS_TRAMITE_RECEBIMENTO'");
        BancoSEI::getInstance()->executarSql("update md_pen_parametro set sequencia = null where nome = 'HIPOTESE_LEGAL_PADRAO'");
        // Este par�metro passar� a ser interno do sistema e ser� configurado com o valor 5 MB que ser� o valor fixo utilizado para updaload e download
        BancoSEI::getInstance()->executarSql("update md_pen_parametro set sequencia = null, valor = 5 where nome = 'PEN_TAMANHO_MAXIMO_DOCUMENTO_EXPEDIDO'");

        // Altera o par�metro da vers�o de banco
        $this->atualizarNumeroVersao("1.4.0");
    }

    protected function instalarV1401()
    {
        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());

        // Aumento de tamanho campo de armazenamento do hash dos recibos para contemplar os diferentes tamanhos de chaves criptogr�ficas
        $this->removerIndicesTabela($objInfraMetaBD, array("md_pen_recibo_tramite_recebido", "md_pen_recibo_tramite", "md_pen_tramite_recibo_envio", "md_pen_recibo_tramite_enviado"));

        // Remove chaves estrangeiras e prim�rias com supress�o de mensagens de erro devido a incompatibilidade de nomes entre diferentes vers�es do sistema
        $bolSuprimirError = true;
        $this->excluirChaveEstrangeira("md_pen_recibo_tramite_recebido", "fk_md_pen_recibo_tramite_recebido_md_pen_tramite", $bolSuprimirError);
        $this->excluirChaveEstrangeira("md_pen_recibo_tramite_recebido", "fk_md_pen_recibo_receb_tram", $bolSuprimirError);
        $this->excluirChavePrimariaComIndice("md_pen_recibo_tramite_recebido", "pk_md_pen_recibo_tramite_receb", $bolSuprimirError);
        $this->excluirChavePrimariaComIndice("md_pen_recibo_tramite_recebido", "pk_md_pen_recibo_tramite_recebido", $bolSuprimirError);

        $objInfraMetaBD->adicionarChavePrimaria("md_pen_recibo_tramite_recebido", "pk_md_pen_recibo_tramite_receb", array("numero_registro", "id_tramite"));
        $objInfraMetaBD->adicionarChaveEstrangeira("fk_md_pen_recibo_receb_tram", "md_pen_recibo_tramite_recebido", array('numero_registro', 'id_tramite'), "md_pen_tramite", array('numero_registro', 'id_tramite'), false);
        $objInfraMetaBD->alterarColuna("md_pen_recibo_tramite_recebido", "hash_assinatura", $objInfraMetaBD->tipoTextoVariavel(1000), "not null");
        $objInfraMetaBD->alterarColuna("md_pen_recibo_tramite", "hash_assinatura", $objInfraMetaBD->tipoTextoVariavel(1000), "not null");
        $objInfraMetaBD->alterarColuna("md_pen_tramite_recibo_envio", "hash_assinatura", $objInfraMetaBD->tipoTextoVariavel(1000), "not null");
        $objInfraMetaBD->alterarColuna("md_pen_recibo_tramite_enviado", "hash_assinatura", $objInfraMetaBD->tipoTextoVariavel(1000), "not null");

        // Altera o par�metro da vers�o de banco
        $this->atualizarNumeroVersao("1.4.1");
    }

    protected function instalarV1402()
    {
        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());

        // Aumento de tamanho campo de armazenamento do hash dos recibos para contemplar os diferentes tamanhos de chaves criptogr�ficas
        $this->removerIndicesTabela($objInfraMetaBD, array("md_pen_recibo_tramite_recebido", "md_pen_recibo_tramite", "md_pen_tramite_recibo_envio", "md_pen_recibo_tramite_enviado"));

        // Remove chaves estrangeiras e prim�rias com supress�o de mensagens de erro devido a incompatibilidade de nomes entre diferentes vers�es do sistema
        $bolSuprimirError = true;
        $this->excluirChaveEstrangeira("md_pen_recibo_tramite_recebido", "fk_md_pen_recibo_tramite_recebido_md_pen_tramite", $bolSuprimirError);
        $this->excluirChaveEstrangeira("md_pen_recibo_tramite_recebido", "fk_md_pen_recibo_receb_tram", $bolSuprimirError);
        $this->excluirChavePrimariaComIndice("md_pen_recibo_tramite_recebido", "pk_md_pen_recibo_tramite_receb", $bolSuprimirError);
        $this->excluirChavePrimariaComIndice("md_pen_recibo_tramite_recebido", "pk_md_pen_recibo_tramite_recebido", $bolSuprimirError);

        $objInfraMetaBD->adicionarChavePrimaria("md_pen_recibo_tramite_recebido", "pk_md_pen_recibo_tramite_receb", array("numero_registro", "id_tramite"));
        $objInfraMetaBD->adicionarChaveEstrangeira("fk_md_pen_recibo_receb_tram", "md_pen_recibo_tramite_recebido", array('numero_registro', 'id_tramite'), "md_pen_tramite", array('numero_registro', 'id_tramite'), false);

        $this->atualizarNumeroVersao("1.4.2");
    }

    protected function instalarV1403()
    {
        $this->atualizarNumeroVersao("1.4.3");
    }

    protected function instalarV1500()
    {
        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());
        $objInfraMetaBD->adicionarColuna("md_pen_componente_digital", "id_procedimento_anexado", $objInfraMetaBD->tipoNumeroGrande(), 'null');
        $objInfraMetaBD->adicionarColuna("md_pen_componente_digital", "protocolo_procedimento_anexado", $objInfraMetaBD->tipoTextoVariavel(50), 'null');
        $objInfraMetaBD->adicionarColuna("md_pen_componente_digital", "ordem_documento_anexado", $objInfraMetaBD->tipoNumero(), 'null');

        $this->atualizarNumeroVersao("1.5.0");
    }

    protected function instalarV1501()
    {
        $this->atualizarNumeroVersao("1.5.1");
    }

    protected function instalarV1502()
    {
        $this->atualizarNumeroVersao("1.5.2");
    }

    protected function instalarV1503()
    {
        $this->atualizarNumeroVersao("1.5.3");
    }

    protected function instalarV2000_beta1()
    {
        $objMetaBD = $this->objMeta;
        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());
        $objInfraMetaBD->excluirColuna("md_pen_especie_documental", "descricao");

        // Ajustes em par�metros de configura��o do m�dulo
        $objInfraMetaBD->adicionarColuna('md_pen_parametro', 'valor_novo', $objInfraMetaBD->tipoTextoGrande(), 'null');
        BancoSEI::getInstance()->executarSql("update md_pen_parametro set valor_novo = valor");
        $objInfraMetaBD->excluirColuna('md_pen_parametro', 'valor');
        $objInfraMetaBD->adicionarColuna('md_pen_parametro', 'valor', $objInfraMetaBD->tipoTextoGrande(), 'null');
        BancoSEI::getInstance()->executarSql("update md_pen_parametro set valor = valor_novo");
        $objInfraMetaBD->excluirColuna('md_pen_parametro', 'valor_novo');

        $objPenParametroDTO = new PenParametroDTO();
        $objPenParametroDTO->setStrNome("PEN_ID_REPOSITORIO_ORIGEM");
        $objPenParametroDTO->setStrDescricao("Reposit�rio de Estruturas do �rg�o");
        $objPenParametroBD = new PenParametroBD(BancoSEI::getInstance());
        $objPenParametroBD->alterar($objPenParametroDTO);

        $objPenParametroDTO = new PenParametroDTO();
        $objPenParametroDTO->setStrNome("PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO");
        $objPenParametroDTO->setStrDescricao("Unidade SEI para Representa��o de �rg�os Externos");
        $objPenParametroBD = new PenParametroBD(BancoSEI::getInstance());
        $objPenParametroBD->alterar($objPenParametroDTO);

        $this->logar("CADASTRAMENTO DE AGENDAMENTO DE TAREFAS DO PEN PARA ATUALIZA��O DE HIP�TESES LEGAIS E ESP�CIES DOCUMENTAIS");
        // Remove agendamento de tarefas de atualiza��o de hip�teses legais
        $objInfraAgendamentoTarefaBD = new InfraAgendamentoTarefaBD(BancoSEI::getInstance());
        $objInfraAgendamentoTarefaDTO = new InfraAgendamentoTarefaDTO();
        $objInfraAgendamentoTarefaDTO->setStrComando("PENAgendamentoRN::atualizarHipotesesLegais");
        $objInfraAgendamentoTarefaDTO->retNumIdInfraAgendamentoTarefa();
        $objInfraAgendamentoTarefaDTO = $objInfraAgendamentoTarefaBD->consultar($objInfraAgendamentoTarefaDTO);
        if(isset($objInfraAgendamentoTarefaDTO)){
            $objInfraAgendamentoTarefaBD->excluir($objInfraAgendamentoTarefaDTO);
        }

        // Adicionar agendamento de atualiza��o de informa��es
        $objAgendamentoInformacoesPEN = new InfraAgendamentoTarefaDTO();
        $objAgendamentoInformacoesPEN->setStrComando("PENAgendamentoRN::atualizarInformacoesPEN");
        if($objInfraAgendamentoTarefaBD->contar($objAgendamentoInformacoesPEN) == 0){
            $strDesc = "Atualiza��o de Informa��es gerais do Barramento para o correto funcionamento do m�dulo \n\n";
            $strDesc .= "- Atualiza��o de Hip�teses Legais\n";
            $strDesc .= "- Atualiza��o de Esp�cies Documentais\n";
            $strDesc .= "- Mapeamento de Esp�cies Documentais com Tipos de Documentos do SEI\n";
            $objAgendamentoInformacoesPEN->setStrDescricao($strDesc);
            $objAgendamentoInformacoesPEN->setStrStaPeriodicidadeExecucao("S");
            $objAgendamentoInformacoesPEN->setStrPeriodicidadeComplemento("1,2,3,4,5,6,7");
            $objAgendamentoInformacoesPEN->setStrSinAtivo("S");
            $objAgendamentoInformacoesPEN->setStrSinSucesso("S");
            $objInfraAgendamentoTarefaBD->cadastrar($objAgendamentoInformacoesPEN);
        }

        $this->logar("CADASTRAMENTO DE AGENDAMENTO DE TAREFAS DO PEN PARA RECEBIMENTO DE PROCESSOS DO BARRAMENTO DO SERVI�OS DO PEN");
        // Adicionar agendamento de atualiza��o de informa��es
        $objReceberProcessosPEN = new InfraAgendamentoTarefaDTO();
        $objReceberProcessosPEN->setStrComando("PENAgendamentoRN::processarTarefasPEN");
        if($objInfraAgendamentoTarefaBD->contar($objReceberProcessosPEN) == 0){
            $strDesc = "Recebe as notifica��es de novos tr�mites de processos/documentos, notifica��es de conclus�o de tr�mites ou recusas de recebimento de processos por outras institui��es. \n\n";
            $strDesc .= "Este agendamento considera os seguintes par�metros durante sua execu��o:\n";
            $strDesc .= " - debug: Indica se o log de debug gerado no processamento ser� registrado nos logs do sistema (valores: true,false | padr�o: false)\n";
            $strDesc .= " - workers: Quantidade de processos paralelos que ser�o abertos para processamento de tarefas (valores: 0-9 | padr�o: 4)\n";
            $objReceberProcessosPEN->setStrDescricao($strDesc);
            $objReceberProcessosPEN->setStrStaPeriodicidadeExecucao("N");
            $objReceberProcessosPEN->setStrPeriodicidadeComplemento("0,2,4,6,8,10,12,14,16,18,20,22,24,26,28,30,32,34,36,38,40,42,44,46,48,50,52,54,56,58");
            $objReceberProcessosPEN->setStrSinAtivo("S");
            $objReceberProcessosPEN->setStrSinSucesso("S");
            $objInfraAgendamentoTarefaBD->cadastrar($objReceberProcessosPEN);
        }


        // Remo��o de agendamento de tarefas do verifica��o dos servi�os do Barramento por n�o ser mais necess�rio
        $objInfraAgendamentoTarefaBD = new InfraAgendamentoTarefaBD(BancoSEI::getInstance());
        $objInfraAgendamentoTarefaDTO = new InfraAgendamentoTarefaDTO();
        $objInfraAgendamentoTarefaDTO->retNumIdInfraAgendamentoTarefa();
        $objInfraAgendamentoTarefaDTO->setStrComando("PENAgendamentoRN::seiVerificarServicosBarramento");
        $objInfraAgendamentoTarefaDTO->setBolExclusaoLogica(False);
        $objInfraAgendamentoTarefaDTO = $objInfraAgendamentoTarefaBD->consultar($objInfraAgendamentoTarefaDTO);
        if(isset($objInfraAgendamentoTarefaDTO)) {
            $this->logar('Removendo agendamento de verifica��o de servi�os de integra��o do Barramento PEN');
            $objInfraAgendamentoTarefaBD->excluir($objInfraAgendamentoTarefaDTO);
        }

        // Remo��o de coluna sin_padrao da tabela md_pen_rel_doc_map_enviado
        $this->logar("REMO��O DE COLUNAS DE DESATIVA��O DE MAPEAMENTO DE ESP�CIES N�O MAIS UTILIZADOS");
        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_rel_doc_map_enviado_tmp',
            'cols' => array(
                'id_mapeamento' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'codigo_especie' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_serie' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO)
            )
        ));

        BancoSEI::getInstance()->executarSql("insert into md_pen_rel_doc_map_enviado_tmp (id_mapeamento, codigo_especie, id_serie) select id_mapeamento, codigo_especie, id_serie from md_pen_rel_doc_map_enviado");
        BancoSEI::getInstance()->executarSql("drop table md_pen_rel_doc_map_enviado");
        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_rel_doc_map_enviado',
            'cols' => array(
                'id_mapeamento' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'codigo_especie' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_serie' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
            ),
            'pk' => array('cols'=>array('id_mapeamento')),
            'fks' => array(
                'serie' => array('nome' => 'fk_md_pen_rel_doc_map_env_seri', 'cols' => array('id_serie', 'id_serie')),
                'md_pen_especie_documental' => array('nome' => 'fk_md_pen_rel_doc_map_env_espe', 'cols' => array('id_especie', 'codigo_especie')),
            )
        ));

        BancoSEI::getInstance()->executarSql("insert into md_pen_rel_doc_map_enviado (id_mapeamento, codigo_especie, id_serie) select id_mapeamento, codigo_especie, id_serie from md_pen_rel_doc_map_enviado_tmp");
        BancoSEI::getInstance()->executarSql("drop table md_pen_rel_doc_map_enviado_tmp");

        // Remo��o de coluna sin_padrao da tabela md_pen_rel_doc_map_enviado
        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_rel_doc_map_recebido_tm',
            'cols' => array(
                'id_mapeamento' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'codigo_especie' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_serie' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
            )
        ));

        BancoSEI::getInstance()->executarSql("insert into md_pen_rel_doc_map_recebido_tm (id_mapeamento, codigo_especie, id_serie) select id_mapeamento, codigo_especie, id_serie from md_pen_rel_doc_map_recebido");
        BancoSEI::getInstance()->executarSql("drop table md_pen_rel_doc_map_recebido");
        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_rel_doc_map_recebido',
            'cols' => array(
                'id_mapeamento' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'codigo_especie' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_serie' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
            ),
            'pk' => array('cols'=>array('id_mapeamento')),
            'fks' => array(
                'serie' => array('nome' => 'fk_md_pen_rel_doc_map_rec_seri', 'cols' => array('id_serie', 'id_serie')),
                'md_pen_especie_documental' => array('nome' => 'fk_md_pen_rel_doc_map_rec_espe', 'cols' => array('id_especie', 'codigo_especie')),
            )
        ));

        BancoSEI::getInstance()->executarSql("insert into md_pen_rel_doc_map_recebido (id_mapeamento, codigo_especie, id_serie) select id_mapeamento, codigo_especie, id_serie from md_pen_rel_doc_map_recebido_tm");
        BancoSEI::getInstance()->executarSql("drop table md_pen_rel_doc_map_recebido_tm");

        // Atribui automaticamente a esp�cie documental 999 - Outra como mapeamento padr�o de esp�cies para envio de processo
        PenParametroRN::persistirParametro("PEN_ESPECIE_DOCUMENTAL_PADRAO_ENVIO", "999");

        // Remo��o de par�metros do banco de dados do SEI devido a necessidade de migra��o
        // para arquivo de configura��o do m�dulo em sei/config/mod-pen/ConfiguracaoModPEN.php
        $this->logar("REMO��O DE PAR�METROS DO BANCO DE DADOS DO SEI DEVIDO MIGRA��O PARA ARQUIVO DE CONFIGURA��O");
        $this->removerParametro("PEN_ENDERECO_WEBSERVICE");
        $this->removerParametro("PEN_ENDERECO_WEBSERVICE_PENDENCIAS");
        $this->removerParametro("PEN_SENHA_CERTIFICADO_DIGITAL");
        $this->removerParametro("PEN_LOCALIZACAO_CERTIFICADO_DIGITAL");
        $this->removerParametro("PEN_NUMERO_TENTATIVAS_TRAMITE_RECEBIMENTO");

        try{
            $this->logar("ATUALIZANDO LISTA DE HIP�TESES LEGAIS DO BARRAMENTO DE SERVI�OS PEN");
            $objPENAgendamentoRN = new PENAgendamentoRN();
            $objPENAgendamentoRN->atualizarHipotesesLegais();
        } catch (\Exception $th) {
            $strMensagemErroMapeamentoAutomatico = "Aviso: N�o foi poss�vel realizar a atualiza��o autom�tico das hip�teses legais do PEN pois servi�o encontra-se inacess�vel\n";
            $strMensagemErroMapeamentoAutomatico .= "A atualiza��o poder� ser realizada posteriormente de forma autom�tica pelo agendamento da tarefa PENAgendamentoRN::atualizarInformacoesPEN";
            $this->logar($strMensagemErroMapeamentoAutomatico);
        }

        try{
            $objPENAgendamentoRN = new PENAgendamentoRN();
            $objPENAgendamentoRN->atualizarEspeciesDocumentais();
        } catch (\Exception $th) {
            $strMensagemErroMapeamentoAutomatico = "Aviso: N�o foi poss�vel realizar a atualiza��o autom�tico das esp�cies documentais do PEN pois servi�o encontra-se inacess�vel\n";
            $strMensagemErroMapeamentoAutomatico .= "Mapeamento poder� ser realizado posteriormente de forma autom�tica pelo agendamento da tarefa PENAgendamentoRN::atualizarInformacoesPEN";
            $this->logar($strMensagemErroMapeamentoAutomatico);
        }

        $this->logar("INICIANDO O MAPEAMENTO AUTOM�TICO DOS TIPOS DE DOCUMENTOS DO SEI COM AS ESP�CIES DOCUMENTAIS DO PEN PARA ENVIO");
        $objPenRelTipoDocMapEnviadoRN = new PenRelTipoDocMapEnviadoRN();
        $objPenRelTipoDocMapEnviadoRN->mapearEspeciesDocumentaisEnvio();

        $this->logar("INICIANDO O MAPEAMENTO AUTOM�TICO DAS ESP�CIES DOCUMENTAIS DO PEN COM OS TIPOS DE DOCUMENTOS DO SEI PARA RECEBIMENTO");
        $objPenRelTipoDocMapRecebidoRN = new PenRelTipoDocMapRecebidoRN();
        $objPenRelTipoDocMapRecebidoRN->mapearEspeciesDocumentaisRecebimento();

        $this->atualizarNumeroVersao("2.0.0-beta1");
    }

    protected function instalarV2000_beta2()
    {
        $this->atualizarNumeroVersao("2.0.0-beta2");
    }

    protected function instalarV2000_beta3()
    {
        $this->atualizarNumeroVersao("2.0.0-beta3");
    }

    protected function instalarV2000_beta4()
    {
        $this->atualizarNumeroVersao("2.0.0-beta4");
    }

    protected function instalarV2000_beta5()
    {
        $this->atualizarNumeroVersao("2.0.0-beta5");
    }

    protected function instalarV2000()
    {
        $this->atualizarNumeroVersao("2.0.0");
    }

    protected function instalarV2001()
    {
        $this->atualizarNumeroVersao("2.0.1");
    }

    protected function instalarV2100()
    {
        // Ajuste da coluna de ordem dos documentos e componentes digitais do processo
        $recordset = $this->listarComponenteDigitaisDesatualizados();
        if(!empty($recordset)){
            foreach ($recordset as $item) {
                BancoSEI::getInstance()->executarSql("
                    update md_pen_componente_digital
                    set ordem_documento = ordem
                    where
                        numero_registro = '".$item['numero_registro']."' and
                        id_procedimento = ".$item['id_procedimento']." and
                        id_documento = ".$item['id_documento']." and
                        id_tramite = ".$item['id_tramite']." and
                        ordem = ".$item['ordem']." and
                        ordem_documento = ".$item['ordem_documento']."
                ");
            }

            foreach ($recordset as $item) {
                BancoSEI::getInstance()->executarSql("
                    update md_pen_componente_digital
                    set ordem = 1
                    where
                        numero_registro = '".$item['numero_registro']."' and
                        id_procedimento = ".$item['id_procedimento']." and
                        id_documento = ".$item['id_documento']." and
                        id_tramite = ".$item['id_tramite']." and
                        ordem = ".$item['ordem']."
                ");
            }
        }

        // Nova coluna para registro de ordem do documento refer�nciado, apresentado como doc anexado na �rvore de processo
        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());
        $objInfraMetaBD->adicionarColuna('md_pen_componente_digital','ordem_documento_referenciado', $objInfraMetaBD->tipoNumero(11), 'null');

	    $this->atualizarNumeroVersao("2.1.0");
    }

    protected function instalarV2101()
    {
        $this->atualizarNumeroVersao("2.1.1");
    }

    protected function instalarV2102()
    {
        $this->atualizarNumeroVersao("2.1.2");
    }

    protected function instalarV2103()
    {
        $this->atualizarNumeroVersao("2.1.3");
    }

    protected function instalarV2104()
    {
        $this->atualizarNumeroVersao("2.1.4");
    }

    protected function instalarV2105()
    {
        $this->atualizarNumeroVersao("2.1.5");
    }

    protected function instalarV2106()
    {
        $this->atualizarNumeroVersao("2.1.6");
    }

    protected function instalarV2107()
    {
        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());
        $objInfraMetaBD->criarIndice('md_pen_rel_hipotese_legal', 'ak1_rel_hipotese_legal', array('id_hipotese_legal', 'id_hipotese_legal_pen', 'tipo'), true);
        $this->atualizarNumeroVersao("2.1.7");
    }
    protected function instalarV3000()
    {
        $this->atualizarNumeroVersao("3.0.0");
    }
    protected function instalarV3001()
    {
        $this->atualizarNumeroVersao("3.0.1");
    }
    protected function instalarV3010()
    {
        $objInfraBanco = BancoSEI::getInstance();
        $objMetaBD = $this->objMeta;

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_expedir_lote',
            'cols' => array(
                'id_lote' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_repositorio_destino' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'str_repositorio_destino' => array($objMetaBD->tipoTextoVariavel(250), PenMetaBD::NNULLO),
                'id_repositorio_origem' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_unidade_origem' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_unidade_destino' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'str_unidade_destino' => array($objMetaBD->tipoTextoVariavel(250), PenMetaBD::NNULLO),
                'id_usuario' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_unidade' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'dth_registro' => array($objMetaBD->tipoDataHora(), PenMetaBD::NNULLO)
            ),
            'pk' => array('cols'=>array('id_lote')),
            'fks' => array(
                'usuario' => array('nome' => 'fk_md_pen_expedir_lote_usuario', 'cols' => array('id_usuario', 'id_usuario')),
                'unidade' => array('nome' => 'fk_md_pen_expedir_lote_unidade', 'cols' => array('id_unidade', 'id_unidade')),
            )
        ));

        //Sequ�ncia: md_pen_seq_lote
        $objInfraBanco->criarSequencialNativa('md_pen_seq_expedir_lote', 0);

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_rel_expedir_lote_procedimento',
            'cols' => array(
                'id_lote' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_procedimento' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'id_andamento' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_atividade_expedicao' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO)
            ),
            'pk' => array('cols'=>array('id_procedimento','id_lote')),
            'fks' => array(
                'md_pen_expedir_lote' => array('nome' => 'fk_md_pen_rel_expedir_lote', 'cols' => array('id_lote', 'id_lote')),
                'procedimento' => array('nome' => 'fk_md_pen_rel_expedir_lote_procedimento', 'cols' => array('id_procedimento', 'id_procedimento')),
            )
        ));        

        $this->atualizarNumeroVersao("3.1.0");
    }

    protected function instalarV3011()
    {
        $this->atualizarNumeroVersao("3.1.1");
    }

    protected function instalarV3012()
    {
        $this->atualizarNumeroVersao("3.1.2");
    }
}

try {

    $dirSeiWeb = !defined("DIR_SEI_WEB") ? getenv("DIR_SEI_WEB") ?: __DIR__."/../../web" : DIR_SEI_WEB;
    require_once $dirSeiWeb . '/SEI.php';

    //Normaliza o formato de n�mero de vers�o considerando dois caracteres para cada item (3.0.15 -> 030015)
    $numVersaoAtual = explode('.', SEI_VERSAO);
    $numVersaoAtual = array_map(function($item){ return str_pad($item, 2, '0', STR_PAD_LEFT); }, $numVersaoAtual);
    $numVersaoAtual = intval(join($numVersaoAtual));

    //Normaliza o formato de n�mero de vers�o considerando dois caracteres para cada item (3.1.0 -> 030100)
    // A partir da vers�o 3.1.0 � que o SEI passa a dar suporte ao UsuarioScript/SenhaScript
    $numVersaoScript = explode('.', "3.1.0");
    $numVersaoScript = array_map(function($item){ return str_pad($item, 2, '0', STR_PAD_LEFT); }, $numVersaoScript);
    $numVersaoScript = intval(join($numVersaoScript));

    if ($numVersaoAtual >= $numVersaoScript) {
        BancoSEI::getInstance()->setBolScript(true);

        if (!ConfiguracaoSEI::getInstance()->isSetValor('BancoSEI','UsuarioScript')){
            throw new InfraException('Chave BancoSEI/UsuarioScript n�o encontrada.');
        }

        if (InfraString::isBolVazia(ConfiguracaoSEI::getInstance()->getValor('BancoSEI','UsuarioScript'))){
            throw new InfraException('Chave BancoSEI/UsuarioScript n�o possui valor.');
        }

        if (!ConfiguracaoSEI::getInstance()->isSetValor('BancoSEI','SenhaScript')){
            throw new InfraException('Chave BancoSEI/SenhaScript n�o encontrada.');
        }

        if (InfraString::isBolVazia(ConfiguracaoSEI::getInstance()->getValor('BancoSEI','SenhaScript'))){
            throw new InfraException('Chave BancoSEI/SenhaScript n�o possui valor.');
        }
    }

    $objAtualizarRN = new PenAtualizarSeiRN();
    $objAtualizarRN->atualizarVersao();
    exit(0);
}
catch(InfraException $e){

    print $e->getStrDescricao().PHP_EOL;
}
catch(Exception $e) {

    print InfraException::inspecionar($e);

    try {
        LogSEI::getInstance()->gravar(InfraException::inspecionar($e));
    } catch (Exception $e) {

    }

    exit(1);
}

print PHP_EOL;
