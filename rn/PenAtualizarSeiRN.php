<?php
/**
 * Atualizador do sistema SEI para instalar/atualizar o módulo PEN
 * 
 * @author Join Tecnologia
 */
class PenAtualizarSeiRN extends PenAtualizadorRN {

    protected $versaoMinRequirida = '2.6.0';
    protected $sei_versao = 'PEN_VERSAO_MODULO_SEI';
    
    protected function inicializarObjInfraIBanco() {
        if(empty($this->objBanco)) {
            
            $this->objBanco = BancoSEI::getInstance();
        }
        return $this->objBanco;
    }
    
    protected function instalarV001(){
        
        $objInfraBanco = $this->inicializarObjInfraIBanco();
        $objMetaBD = $this->inicializarObjMetaBanco();

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_processo_eletronico',
            'cols' => array(
                'numero_registro'=> array($objMetaBD->tipoTextoFixo(16), PenMetaBD::NNULLO),
                'id_procedimento' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO)
            ),
            'pk' => array('numero_registro'),
            'uk' => array('numero_registro', 'id_procedimento'),
            'fks' => array(
                'procedimento' => array('id_procedimento', 'id_procedimento')
            )
        ));
        
        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_tramite',
            'cols' => array(
                'numero_registro' => array($objMetaBD->tipoTextoFixo(16), PenMetaBD::NNULLO),
                'id_tramite'=> array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'ticket_envio_componentes'=> array($objMetaBD->tipoTextoGrande(), PenMetaBD::SNULLO),
                'dth_registro'=> array($objMetaBD->tipoDataHora(), PenMetaBD::SNULLO),
                'id_andamento'=> array($objMetaBD->tipoNumero(), PenMetaBD::SNULLO)
            ),
            'pk' => array('id_tramite'),
            'uk' => array('numero_registro', 'id_tramite'),
            'fks' => array(
                'md_pen_processo_eletronico' => array('numero_registro', 'numero_registro')
            )
        ));
         
        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_especie_documental',
            'cols' => array(
                'id_especie'=> array($objMetaBD->tipoNumero(16), PenMetaBD::NNULLO),
                'nome_especie'=> array($objMetaBD->tipoTextoVariavel(255), PenMetaBD::NNULLO),
                'descricao'=> array($objMetaBD->tipoTextoVariavel(255), PenMetaBD::NNULLO)
            ),
            'pk' => array('id_especie')
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_tramite_pendente',
            'cols' => array(
                'id'=> array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'numero_tramite'=> array($objMetaBD->tipoTextoVariavel(255)),
                'id_atividade_expedicao'=> array($objMetaBD->tipoNumero(), PenMetaBD::SNULLO)
            ),
            'pk' => array('id')
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_tramite_recibo_envio',
            'cols' => array(
                'numero_registro'=> array($objMetaBD->tipoTextoFixo(16), PenMetaBD::NNULLO),
                'id_tramite' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'dth_recebimento' => array($objMetaBD->tipoDataHora(), PenMetaBD::NNULLO),
                'hash_assinatura' => array($objMetaBD->tipoTextoVariavel(255), PenMetaBD::NNULLO)
            ),
            'pk' => array('numero_registro', 'id_tramite')   
        ));


        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_procedimento_andamento',
            'cols' => array(
                'id_andamento'=> array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'id_procedimento' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'id_tramite' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'situacao' => array($objMetaBD->tipoTextoFixo(1), 'N'),
                'data' => array($objMetaBD->tipoDataHora(), PenMetaBD::NNULLO),
                'mensagem' => array($objMetaBD->tipoTextoVariavel(255), PenMetaBD::NNULLO),
                'hash' => array($objMetaBD->tipoTextoFixo(32), PenMetaBD::NNULLO),
                'id_tarefa' => array($objMetaBD->tipoTextoVariavel(255), PenMetaBD::NNULLO)
            ),
            'pk' => array('id_andamento')   
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_protocolo',
            'cols' => array(
                'id_protocolo'=> array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'sin_obteve_recusa' => array($objMetaBD->tipoTextoFixo(1), 'N')
            ),
            'pk' => array('id_protocolo'),
            'fks' => array(
                'protocolo' => array('id_protocolo', 'id_protocolo')
            )
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_tramite_recusado',
            'cols' => array(
                'numero_registro'=> array($objMetaBD->tipoTextoFixo(16), PenMetaBD::NNULLO),
                'id_tramite' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO)
            ),
            'pk' => array('id_tramite')
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_recibo_tramite',
            'cols' => array(
                'numero_registro' => array($objMetaBD->tipoTextoFixo(16), PenMetaBD::NNULLO),
                'id_tramite' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'dth_recebimento' => array($objMetaBD->tipoDataHora(), PenMetaBD::NNULLO),
                'hash_assinatura' => array($objMetaBD->tipoTextoVariavel(255), PenMetaBD::NNULLO),
                'cadeia_certificado' => array($objMetaBD->tipoTextoVariavel(255), PenMetaBD::NNULLO)
            ),
            'pk' => array('numero_registro', 'id_tramite'),
            'fks' => array(
                'md_pen_tramite' => array(array('numero_registro', 'id_tramite'), array('numero_registro', 'id_tramite'))
            )
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_recibo_tramite_enviado',
            'cols' => array(
                'numero_registro'=> array($objMetaBD->tipoTextoFixo(16), PenMetaBD::NNULLO),
                'id_tramite' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'dth_recebimento'=> array($objMetaBD->tipoDataHora(), PenMetaBD::NNULLO),
                'hash_assinatura'=> array($objMetaBD->tipoTextoVariavel(255), PenMetaBD::NNULLO),
                'cadeia_certificado ' => array($objMetaBD->tipoTextoVariavel(255), PenMetaBD::NNULLO)
            ),
            'pk' => array('numero_registro', 'id_tramite'),
            'fks' => array(
                'md_pen_tramite' => array(array('numero_registro', 'id_tramite'), array('numero_registro', 'id_tramite'))
            )
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_recibo_tramite_recebido',
            'cols' => array(
                'numero_registro'=> array($objMetaBD->tipoTextoFixo(16), PenMetaBD::NNULLO),
                'id_tramite' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'dth_recebimento'=> array($objMetaBD->tipoDataHora(), PenMetaBD::NNULLO),
                'hash_assinatura'=> array($objMetaBD->tipoTextoVariavel(255), PenMetaBD::NNULLO)
            ),
            'pk' => array('numero_registro', 'id_tramite', 'hash_assinatura'),
            'fks' => array(
                'md_pen_tramite' => array(array('numero_registro', 'id_tramite'), array('numero_registro', 'id_tramite'))
            )
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_rel_processo_apensado',
            'cols' => array(
                'numero_registro'=> array($objMetaBD->tipoTextoFixo(16), PenMetaBD::NNULLO),
                'id_procedimento_apensado' => array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'protocolo'=> array($objMetaBD->tipoTextoVariavel(50), PenMetaBD::NNULLO)
            ),
            'pk' => array('numero_registro', 'id_procedimento_apensado'),
            'fks' => array(
                'md_pen_processo_eletronico' => array('numero_registro', 'numero_registro')
            )
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_rel_serie_especie',
            'cols' => array(
                'codigo_especie'=> array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_serie' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'sin_padrao'=> array($objMetaBD->tipoTextoFixo(1), 'N')
            ),
            'pk' => array('id_serie'),
            'uk' => array('codigo_especie', 'id_serie'),
            'fks' => array(
                'serie' => array('id_serie', 'id_serie')
            )
        ));
        
        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_rel_tarefa_operacao',
            'cols' => array(
                'id_tarefa'=> array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'codigo_operacao'=> array($objMetaBD->tipoTextoFixo(2), PenMetaBD::NNULLO)
            ),
            'pk' => array('id_tarefa', 'codigo_operacao'),
            'fks' => array(
                'tarefa' => array('id_tarefa', 'id_tarefa')
            )
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_rel_tipo_documento_mapeamento_recebido',
            'cols' => array(
                'codigo_especie'=> array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_serie'=> array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'sin_padrao'=> array($objMetaBD->tipoTextoFixo(2), PenMetaBD::NNULLO)
            ),
            'pk' => array('codigo_especie', 'id_serie'),
            'fks' => array(
                'serie' => array('id_serie', 'id_serie')
            )
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_componente_digital',
            'cols' => array(
                'numero_registro'=> array($objMetaBD->tipoTextoFixo(16), PenMetaBD::NNULLO),
                'id_procedimento'=> array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'id_documento'=> array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'id_tramite'=> array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'id_anexo'=> array($objMetaBD->tipoNumero(), PenMetaBD::SNULLO),
                'protocolo'=> array($objMetaBD->tipoTextoVariavel(50), PenMetaBD::NNULLO),
                'nome'=> array($objMetaBD->tipoTextoVariavel(100), PenMetaBD::NNULLO),
                'hash_conteudo'=> array($objMetaBD->tipoTextoVariavel(255), PenMetaBD::NNULLO),
                'algoritmo_hash'=> array($objMetaBD->tipoTextoVariavel(20), PenMetaBD::NNULLO),
                'tipo_conteudo'=> array($objMetaBD->tipoTextoFixo(3), PenMetaBD::NNULLO),
                'mime_type'=> array($objMetaBD->tipoTextoVariavel(100), PenMetaBD::NNULLO),
                'dados_complementares'=> array($objMetaBD->tipoTextoVariavel(1000), PenMetaBD::SNULLO),
                'tamanho'=> array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'ordem'=> array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'sin_enviar'=> array($objMetaBD->tipoTextoFixo(1), 'N')
            ),
            'pk' => array('numero_registro', 'id_procedimento', 'id_documento', 'id_tramite'),
            'fks' => array(
                'anexo' => array('id_anexo', 'id_anexo'),
                'documento' => array('id_documento', 'id_documento'),
                'procedimento' => array('id_procedimento', 'id_procedimento'),
                'md_pen_processo_eletronico' => array('numero_registro', 'numero_registro'),
                'md_pen_tramite' => array(array('numero_registro', 'id_tramite'), array('numero_registro', 'id_tramite'))
            )
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_unidade',
            'cols' => array(
                'id_unidade'=> array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_unidade_rh'=> array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO)
            ),
            'pk' => array('id_unidade'),
            'fks' => array(
                'unidade' => array('id_unidade', 'id_unidade')
            )
        ));
        
        //----------------------------------------------------------------------
        // Novas sequências
        //----------------------------------------------------------------------
        $objInfraSequencia = new InfraSequencia($objInfraBanco);
        
        if(!$objInfraSequencia->verificarSequencia('md_pen_procedimento_andamento')){
            
            $objInfraSequencia->criarSequencia('md_pen_procedimento_andamento', '1', '1', '9999999999');
        }
        
        if(!$objInfraSequencia->verificarSequencia('md_pen_tramite_pendente')){

            $objInfraSequencia->criarSequencia('md_pen_tramite_pendente', '1', '1', '9999999999');
        }
        //----------------------------------------------------------------------
        // Parâmetros
        //----------------------------------------------------------------------
        
        $objInfraParametro = new InfraParametro($objInfraBanco);

        $objInfraParametro->setValor('PEN_ID_REPOSITORIO_ORIGEM', '');
        $objInfraParametro->setValor('PEN_ENDERECO_WEBSERVICE', '');
        $objInfraParametro->setValor('PEN_SENHA_CERTIFICADO_DIGITAL', '1234');
        $objInfraParametro->setValor('PEN_TIPO_PROCESSO_EXTERNO', '100000320');
        $objInfraParametro->setValor('PEN_ENVIA_EMAIL_NOTIFICACAO_RECEBIMENTO', 'N');
        $objInfraParametro->setValor('PEN_ENDERECO_WEBSERVICE_PENDENCIAS', '');
        $objInfraParametro->setValor('PEN_UNIDADE_GERADORA_DOCUMENTO_RECEBIDO', '');
        $objInfraParametro->setValor('PEN_LOCALIZACAO_CERTIFICADO_DIGITAL', '');
   
        //----------------------------------------------------------------------
        // Especie de Documento
        //----------------------------------------------------------------------
        
        $objBD = new GenericoBD($this->inicializarObjInfraIBanco());
        $objDTO = new EspecieDocumentalDTO();

        $fnCadastrar = function($dblIdEspecie, $strNomeEspecie, $strDescricao) use($objDTO, $objBD){
           
            $objDTO->unSetTodos();
            $objDTO->setStrNomeEspecie($strNomeEspecie);

            if($objBD->contar($objDTO) == 0){  
                $objDTO->setDblIdEspecie($dblIdEspecie);
                $objDTO->setStrDescricao($strDescricao);
                $objBD->cadastrar($objDTO);
            }   
        };

        $fnCadastrar(1,'Abaixo-assinado','Podendo ser complementado: de Reivindicação');
        $fnCadastrar(2,'Acórdão','Expressa decisão proferida pelo Conselho Diretor, não abrangida pelos demais instrumentos deliberativos anteriores.');
        $fnCadastrar(3,'Acordo','Podendo ser complementado: de Nível de Serviço; Coletivo de Trabalho');
        $fnCadastrar(4,'Alvará','Podendo ser complementado: de Funcionamento; Judicial');
        $fnCadastrar(5,'Anais','Podendo ser complementado: de Eventos; de Engenharia');
        $fnCadastrar(6,'Anteprojeto','Podendo ser complementado: de Lei');
        $fnCadastrar(7,'Apólice','Podendo ser complementado: de Seguro');
        $fnCadastrar(8,'Apostila','Podendo ser complementado: de Curso');
        $fnCadastrar(9,'Ata','Como Documento Externo pode ser complementado: de Reunião; de Realização de Pregão');
        $fnCadastrar(10,'Atestado','Podendo ser complementado: Médico; de Comparecimento; de Capacidade Técnica');
        $fnCadastrar(11,'Ato','Expressa decisão sobre outorga, expedição, modificação, transferência, prorrogação, adaptação e extinção de concessões, permissões e autorizações para exploração de serviços, uso de recursos escassos e exploração de satélite, e Chamamento Público.');
        $fnCadastrar(12,'Auto','Podendo ser complementado: de Vistoria; de Infração');
        $fnCadastrar(13,'Aviso','Podendo ser complementado: de Recebimento; de Sinistro; de Férias');
        $fnCadastrar(14,'Balancete','Podendo ser complementado: Financeiro');
        $fnCadastrar(15,'Balanço','Podendo ser complementado: Patrimonial - BP; Financeiro');
        $fnCadastrar(16,'Bilhete','Podendo ser complementado: de Pagamento; de Loteria');
        $fnCadastrar(17,'Boletim','Podendo ser complementado: de Ocorrência; Informativo');
        $fnCadastrar(18,'Carta','Podendo ser complementado: Convite');
        $fnCadastrar(19,'Cartaz','Podendo ser complementado: de Evento');
        $fnCadastrar(20,'Cédula','Podendo ser complementado: de Identidade; de Crédito Bancário; de Crédito Comercial; de Crédito Imobiliário');
        $fnCadastrar(21,'Certidão','Como Documento Externo pode ser complementado: de Tempo de Serviço; de Nascimento; de Casamento; de Óbito; Negativa de Falência ou Concordata; Negativa de Débitos Trabalhistas; Negativa de Débitos Tributários');
        $fnCadastrar(22,'Certificado','Podendo ser complementado: de Conclusão de Curso; de Calibração de Equipamento; de Marca');
        $fnCadastrar(23,'Cheque','Podendo ser complementado: Caução');
        $fnCadastrar(24,'Comprovante','Podendo ser complementado: de Despesa; de Rendimento; de Residência; de Matrícula; de União Estável');
        $fnCadastrar(25,'Comunicado','Expediente interno entre uma unidade administrativa e um servidor ou entre um servidor e uma unidade administrativa de um mesmo órgão público.');
        $fnCadastrar(26,'Consulta','Podendo ser complementado: Pública; Interna');
        $fnCadastrar(27,'Contracheque','Espécie própria');
        $fnCadastrar(28,'Contrato','Como Documento Externo pode ser complementado: Social');
        $fnCadastrar(29,'Convênio','Espécie própria');
        $fnCadastrar(30,'Convite','Podendo ser complementado: de Reunião; para Evento; de Casamento');
        $fnCadastrar(31,'Convenção','Podendo ser complementado: Coletiva de Trabalho; Internacional');
        $fnCadastrar(32,'Crachá','Podendo ser complementado: de Identificação; de Evento');
        $fnCadastrar(33,'Cronograma','Podendo ser complementado: de Projeto; de Estudos');
        $fnCadastrar(34,'Currículo','Podendo ser complementado: de Candidato');
        $fnCadastrar(35,'Debênture','Espécie própria');
        $fnCadastrar(36,'Decisão','Podendo ser complementado: Administrativa; Judicial');
        $fnCadastrar(37,'Declaração','Como Documento Externo pode ser complementado: de Imposto de Renda; de Conformidade; de Responsabilidade Técnica; de Acumulação de Aposentadoria; de Acumulação de Cargos; de Informações Econômico-Fiscais da Pessoa Jurídica $fnCadastrar(DIPJ);');
        $fnCadastrar(38,'Decreto','Espécie própria');
        $fnCadastrar(39,'Deliberação','Podendo ser complementado: de Recursos; do Conselho');
        $fnCadastrar(40,'Demonstrativo','Podendo ser complementado: Financeiro; de Pagamento; de Arrecadação');
        $fnCadastrar(41,'Depoimento','Podendo ser complementado: das Testemunhas');
        $fnCadastrar(42,'Despacho','Espécie própria');
        $fnCadastrar(43,'Diário','Podendo ser complementado: de Justiça; Oficial');
        $fnCadastrar(44,'Diploma','Podendo ser complementado: de Conclusão de Curso');
        $fnCadastrar(45,'Diretriz','Podendo ser complementado: Orçamentária');
        $fnCadastrar(46,'Dissertação','Podendo ser complementado: de Mestrado');
        $fnCadastrar(47,'Dossiê','Podendo ser complementado: de Processo; Técnico');
        $fnCadastrar(48,'Edital','Podendo ser complementado: de Convocação; de Intimação; de Lançamento');
        $fnCadastrar(49,'E-mail','Indicado nos Parâmetros para corresponder ao envio de Correspondência Eletrônica do SEI');
        $fnCadastrar(50,'Embargos','Podendo ser complementado: de Declaração; de Execução ou Infringentes');
        $fnCadastrar(51,'Emenda','Podendo ser complementado: Constitucional; de Comissão; de Bancada; de Relatoria');
        $fnCadastrar(52,'Escala','Podendo ser complementado: de Férias');
        $fnCadastrar(53,'Escritura','Podendo ser complementado: Pública; de Imóvel');
        $fnCadastrar(54,'Estatuto','Podendo ser complementado: Social');
        $fnCadastrar(55,'Exposição de Motivos','Espécie própria');
        $fnCadastrar(56,'Extrato','Podendo ser complementado: de Sistemas; Bancário');
        $fnCadastrar(57,'Fatura','Espécie própria');
        $fnCadastrar(58,'Ficha','Podendo ser complementado: de Cadastro; de Inscrição');
        $fnCadastrar(59,'Fluxograma','Podendo ser complementado: de Processo; de Documentos; de Blocos');
        $fnCadastrar(60,'Folha','Podendo ser complementado: de Frequência de Estagiário; de Frequência de Servidor');
        $fnCadastrar(61,'Folheto/Folder','Podendo ser complementado: de Evento');
        $fnCadastrar(62,'Formulário','Podendo ser complementado: de Contato; de Revisão');
        $fnCadastrar(63,'Grade Curricular','Podendo ser complementado: do Curso');
        $fnCadastrar(64,'Guia','Podendo ser complementado: de Recolhimento da União');
        $fnCadastrar(65,'Histórico','Podendo ser complementado: Escolar');
        $fnCadastrar(66,'Indicação','Espécie própria utilizada pelo Poder Legislativo');
        $fnCadastrar(67,'Informe','Como Documento Externo pode ser complementado: de Rendimentos');
        $fnCadastrar(68,'Instrução','Podendo ser complementado: Normativa');
        $fnCadastrar(69,'Inventário','Podendo ser complementado: de Estoque; Extrajudicial; Judicial; em Cartório');
        $fnCadastrar(70,'Laudo','Podendo ser complementado: Médico; Conclusivo');
        $fnCadastrar(71,'Lei','Podendo ser complementado: Complementar');
        $fnCadastrar(72,'Lista/Listagem','Podendo ser complementado: de Presença');
        $fnCadastrar(73,'Livro','Podendo ser complementado: Caixa');
        $fnCadastrar(74,'Mandado','Podendo ser complementado: de Busca e Apreensão; de Citação; de Intimação');
        $fnCadastrar(75,'Manifesto','Espécie própria');
        $fnCadastrar(76,'Manual','Podendo ser complementado: do Usuário; do Sistema; do Equipamento');
        $fnCadastrar(77,'Mapa','Podendo ser complementado: de Ruas; de Risco');
        $fnCadastrar(78,'Medida Provisória','Espécie própria');
        $fnCadastrar(79,'Memorando','Como Documento Externo pode ser complementado: de Entendimento');
        $fnCadastrar(80,'Memorando-circular','Mesma definição do Memorando com apenas uma diferença: é encaminhado simultaneamente a mais de um cargo.');
        $fnCadastrar(81,'Memorial','Podendo ser complementado: Descritivo; de Incorporação');
        $fnCadastrar(82,'Mensagem','Podendo ser complementado: de Aniversário; de Boas Vindas');
        $fnCadastrar(83,'Minuta','Podendo ser complementado: de Portaria; de Resolução');
        $fnCadastrar(84,'Moção','Podendo ser complementado: de Apoio; de Pesar; de Repúdio');
        $fnCadastrar(85,'Norma','Podendo ser complementado: Técnica; de Conduta');
        $fnCadastrar(86,'Nota','Podendo ser complementado: Técnica; de Empenho');
        $fnCadastrar(87,'Notificação','Podendo ser complementado: de Lançamento');
        $fnCadastrar(88,'Ofício','Modalidades de comunicação oficial. É expedido para e pelas autoridades. Tem como finalidade o tratamento de assuntos oficiais pelos órgãos da Administração Pública entre si e também com particulares.');
        $fnCadastrar(89,'Ofício-Circular','Espécie própria');
        $fnCadastrar(90,'Orçamento','Podendo ser complementado: de Obra; de Serviço');
        $fnCadastrar(91,'Ordem','Podendo ser complementado: de Serviço; de Compra; do Dia');
        $fnCadastrar(92,'Organograma','Podendo ser complementado: da Empresa');
        $fnCadastrar(93,'Orientação','Podendo ser complementado: Normativa; Jurisprudencial');
        $fnCadastrar(94,'Panfleto','Podendo ser complementado: de Promoção; de Evento');
        $fnCadastrar(95,'Parecer','Tipo de Documento próprio da AGU e outros órgãos públicos.');
        $fnCadastrar(96,'Passaporte','Espécie própria');
        $fnCadastrar(97,'Pauta','Podendo ser complementado: de Julgamentos; de Audiências; das Seções');
        $fnCadastrar(98,'Petição','Podendo ser complementado: Inicial; Incidental');
        $fnCadastrar(99,'Planilha','Podendo ser complementado: de Custos e Formação de Preços');
        $fnCadastrar(100,'Plano','Podendo ser complementado: de Serviço; de Contas Contábil');
        $fnCadastrar(101,'Planta','Podendo ser complementado: Baixa; de Localização; de Situação');
        $fnCadastrar(102,'Portaria','Expressa decisão relativa a assuntos de interesse interno da Agência.');
        $fnCadastrar(103,'Precatório','Podendo ser complementado: Alimentar; Federal; Estadual; Municipal');
        $fnCadastrar(104,'Processo','Processo');
        $fnCadastrar(105,'Procuração','Espécie própria');
        $fnCadastrar(106,'Programa','Podendo ser complementado: de Governo; de Melhoria');
        $fnCadastrar(107,'Projeto','Podendo ser complementado: Técnico; Comercial');
        $fnCadastrar(108,'Prontuário','Podendo ser complementado: Médico; Odontológico');
        $fnCadastrar(109,'Pronunciamento','Espécie própria');
        $fnCadastrar(110,'Proposta','Podendo ser complementado: Comercial; de Orçamento; Técnica');
        $fnCadastrar(111,'Prospecto','Podendo ser complementado: de Fundos');
        $fnCadastrar(112,'Protocolo','Podendo ser complementado: de Entendimentos; de Entrega');
        $fnCadastrar(113,'Prova','Podendo ser complementado: de Conceito; de Proficiência');
        $fnCadastrar(114,'Questionário','Podendo ser complementado: de Avaliação; de Pesquisa; Socioeconômico');
        $fnCadastrar(115,'Receita','Espécie própria');
        $fnCadastrar(116,'Recibo','Podendo ser complementado: de Pagamento; de Entrega');
        $fnCadastrar(117,'Recurso','Podendo ser complementado: Administrativo; Judicial');
        $fnCadastrar(118,'Regimento','Podendo ser complementado: Interno');
        $fnCadastrar(119,'Registro','Podendo ser complementado: de Detalhes de Chamadas - CDR; de Acesso; Comercial');
        $fnCadastrar(120,'Regulamento','Podendo ser complementado: Geral; Disciplinar; de Administração');
        $fnCadastrar(121,'Relação','Podendo ser complementado: de Bens Reversíveis - RBR');
        $fnCadastrar(122,'Relatório','Podendo ser complementado: de Conformidade; de Medições; de Prestação de Contas; de Viagem a Serviço; Fotográfico; Técnico');
        $fnCadastrar(123,'Release','Podendo ser complementado: de Resultados; de Produtos; de Serviços');
        $fnCadastrar(124,'Representação','Podendo ser complementado: Comercial; Processual; Fiscal');
        $fnCadastrar(125,'Requerimento','Podendo ser complementado: Administrativo; de Adaptação; de Alteração Técnica; de Alteração Técnica; de Autocadastramento de Estação; de Licenciamento de Estação; de Serviço de Telecomunicações');
        $fnCadastrar(126,'Requisição','Podendo ser complementado: de Auditoria; de Exclusão; de Segunda Via');
        $fnCadastrar(127,'Resolução','Expressa decisão quanto ao provimento normativo que regula a implementação da política de telecomunicações brasileira, a prestação dos serviços de telecomunicações, a administração dos recursos à prestação e o funcionamento da Agência.');
        $fnCadastrar(128,'Resumo','Podendo ser complementado: Técnico');
        $fnCadastrar(129,'Roteiro','Podendo ser complementado: de Instalação; de Inspeção');
        $fnCadastrar(130,'Sentença','Podendo ser complementado: de Mérito; Terminativa; Declaratória; Constitutiva; Condenatória; Mandamental; Executiva');
        $fnCadastrar(131,'Sinopse','Podendo ser complementado: do Livro; do Estudo Técnico');
        $fnCadastrar(132,'Solicitação','Podendo ser complementado: de Pagamento');
        $fnCadastrar(133,'Súmula','Expressa decisão quanto à interpretação da legislação de telecomunicações e fixa entendimento sobre matérias de competência da Agência, com efeito vinculativo.');
        $fnCadastrar(134,'Tabela','Podendo ser complementado: de Visto; de Passaporte; de Certidão');
        $fnCadastrar(135,'Telegrama','Espécie própria');
        $fnCadastrar(136,'Termo','Podendo ser complementado: de Opção por Auxílio Financeiro; de Opção para Contribuição ao CPSS; de Conciliação; de Devolução; de Doação; de Recebimento; de Rescisão; de Compromisso de Estágio; de Representação; de Responsabilidade de Instalação - TRI');
        $fnCadastrar(137,'Tese','Podendo ser complementado: de Doutorado');
        $fnCadastrar(138,'Testamento','Podendo ser complementado: Particular; Vital; Cerrado; Conjuntivo');
        $fnCadastrar(139,'Título','Podendo ser complementado: de Eleitor; Público; de Capitalização');
        $fnCadastrar(140,'Voto','Espécie própria');
        $fnCadastrar(141,'Carteira','Podendo ser complementado: Nacional de Habilitação');
        $fnCadastrar(142,'Cartão','Podendo ser complementado: de Identificação');
        $fnCadastrar(143,'CPF/CIC','Espécie própria');
        $fnCadastrar(144,'CNPJ','Espécie própria');
        $fnCadastrar(145,'Calendário','Podendo ser complementado: de Reuniões');
        $fnCadastrar(146,'CNH','CNH');
        $fnCadastrar(147,'RG','RG');
        $fnCadastrar(148,'Agenda','Podendo ser complementado: de Reunião');
        $fnCadastrar(149,'Análise','Como Documento Externo pode ser complementado: Contábil');
        $fnCadastrar(150,'Anotação','Podendo ser complementado: de Responsabilidade Técnica - ART');
        $fnCadastrar(151,'Áudio','Podendo ser complementado: de Reunião');
        $fnCadastrar(152,'Boleto','Podendo ser complementado: de Pagamento; de Cobrança; de Cobrança Registrada; de Cobrança sem Registro');
        $fnCadastrar(153,'Conta','Podendo ser complementado: Telefônica; de Água; de Luz');
        $fnCadastrar(154,'Contrarrazões','Podendo ser complementado: em Recurso; em Apelação; em Embargos Infringentes');
        $fnCadastrar(155,'Correspondência','Espécie própria');
        $fnCadastrar(156,'Cota','Tipo de Documento próprio da AGU.');
        $fnCadastrar(157,'Credencial','Podendo ser complementado: de Segurança; de Agente de Fiscalização');
        $fnCadastrar(158,'Croqui','Podendo ser complementado: de Acesso, Urbano');
        $fnCadastrar(159,'Defesa','Podendo ser complementado: Administrativa; Judicial');
        $fnCadastrar(160,'Demonstração','Podendo ser complementado: de Resultado do Exercício - DRE; de Fluxo de Caixa; Financeira; Contábil');
        $fnCadastrar(161,'Denúncia','Espécie própria');
        $fnCadastrar(162,'Esclarecimento','Espécie própria utilizada em Licitação $fnCadastrar(ComprasNet);');
        $fnCadastrar(163,'Escrituração','Podendo ser complementado: Contábil Digital - ECD; Fiscal Digital - EFD; Fiscal Digital - EFD-Contribuições');
        $fnCadastrar(164,'Estratégia','Podendo ser complementado: da Contratação');
        $fnCadastrar(165,'Impugnação','Espécie própria utilizada em Licitação $fnCadastrar(ComprasNet);');
        $fnCadastrar(166,'Informação','Tipo de Documento próprio da AGU.');
        $fnCadastrar(167,'Intenção','Podendo ser complementado: de Recurso; de Compra; de Venda');
        $fnCadastrar(168,'Licença','Podendo ser complementado: de Estação');
        $fnCadastrar(169,'Matéria','Podendo ser complementado: para Apreciação');
        $fnCadastrar(170,'Material','Podendo ser complementado: Publicitário; de Evento; de Promoção');
        $fnCadastrar(171,'Memória','Podendo ser complementado: de Cálculo');
        $fnCadastrar(172,'Movimentação','Podendo ser complementado: de Bens Móveis');
        $fnCadastrar(173,'Pedido','Podendo ser complementado: de Reconsideração; de Esclarecimento');
        $fnCadastrar(174,'Reclamação','Espécie própria');
        $fnCadastrar(175,'Referendo','Espécie própria');
        $fnCadastrar(176,'Resultado','Podendo ser complementado: de Exame Médico; de Contestação');
        $fnCadastrar(177,'Vídeo','Podendo ser complementado: de Reunião');
        
//        $objDTO = new PenRelTipoDocMapEnviadoDTO();
//        
//        $fnCadastrar = function($numCodigoEspecie, $numIdSerie) use($objDTO, $objBD){
//            
//            $objDTO->unSetTodos();
//            $objDTO->setNumCodigoEspecie($numCodigoEspecie);
//            $objDTO->setNumIdSerie($numIdSerie);
//            
//            if($objBD->contar($objDTO) == 0){   
//                //$objDTO->strStrSinPadrao('S');
//                $objBD->cadastrar($objDTO); 
//            }   
//        };
        //----------------------------------------------------------------------
        // Especie de Documento por Série
        //----------------------------------------------------------------------
//        $fnCadastrar(1, 46);// Abaixo-assinado;
//        $fnCadastrar(2, 8);// Acordao;
//        $fnCadastrar(3, 106);// Acordo;
//        $fnCadastrar(4, 48);// Alvara;
//        $fnCadastrar(5, 116);// Anais;
//        $fnCadastrar(6, 117);// Anteprojeto;
//        $fnCadastrar(7, 118);// Apolice;
//        $fnCadastrar(8, 119);// Apostila;
//        $fnCadastrar(9, 67);// Ata;
//        $fnCadastrar(10, 74);// Atestado;
//        $fnCadastrar(11, 3);// Ato;
//        $fnCadastrar(12, 120);// Auto;
//        $fnCadastrar(13, 121);// Aviso;
//        $fnCadastrar(14, 32);// Balancete;
//        $fnCadastrar(15, 33);// Balanco;
//        $fnCadastrar(16, 122);// Bilhete;
//        $fnCadastrar(17, 97);// Boletim;
//        $fnCadastrar(18, 80);// Carta;
//        $fnCadastrar(19, 123);// Cartaz;
//        $fnCadastrar(20, 124);// Cedula;
//        $fnCadastrar(21, 81);// Certidao;
//        $fnCadastrar(22, 82);// Certificado;
//        $fnCadastrar(23, 125);// Cheque;
//        $fnCadastrar(24, 35);// Comprovante;
//        $fnCadastrar(25, 14);// Comunicado;
//        $fnCadastrar(26, 197);// Consulta;
//        $fnCadastrar(27, 126);// Contracheque;
//        $fnCadastrar(28, 37);// Contrato;
//        $fnCadastrar(29, 232);// Convenio;
//        $fnCadastrar(30, 127);// Convite;
//        $fnCadastrar(31, 105);// Convencao;
//        $fnCadastrar(32, 128);// Cracha;
//        $fnCadastrar(33, 129);// Cronograma;
//        $fnCadastrar(34, 130);// Curriculo;
//        $fnCadastrar(35, 198);// Debenture;
//        $fnCadastrar(36, 41);// Decisao;
//        $fnCadastrar(37, 83);// Declaracao;
//        $fnCadastrar(38, 131);// Decreto;
//        $fnCadastrar(39, 132);// Deliberacao;
//        $fnCadastrar(40, 133);// Demonstrativo;
//        $fnCadastrar(41, 134);// Depoimento;
//        $fnCadastrar(42, 5);// Despacho;
//        $fnCadastrar(43, 135);// Diario;
//        $fnCadastrar(44, 77);// Diploma;
//        $fnCadastrar(45, 199);// Diretriz;
//        $fnCadastrar(46, 136);// Dissertacao;
//        $fnCadastrar(47, 137);// Dossie;
//        $fnCadastrar(48, 68);// Edital;
//        $fnCadastrar(49, 30);// E-mail;
//        $fnCadastrar(50, 138);// Embargos;
//        $fnCadastrar(51, 139);// Emenda;
//        $fnCadastrar(52, 140);// Escala;
//        $fnCadastrar(53, 141);// Escritura;
//        $fnCadastrar(54, 50);// Estatuto;
//        $fnCadastrar(55, 142);// Exposicao de Motivos;
//        $fnCadastrar(56, 51);// Extrato;
//        $fnCadastrar(57, 84);// Fatura;
//        $fnCadastrar(58, 143);// Ficha;
//        $fnCadastrar(59, 144);// Fluxograma;
//        $fnCadastrar(60, 99);// Folha;
//        $fnCadastrar(61, 200);// Folheto/Folder;
//        $fnCadastrar(62, 145);// Formulario;
//        $fnCadastrar(63, 201);// Grade Curricular;
//        $fnCadastrar(64, 146);// Guia;
//        $fnCadastrar(65, 79);// Historico;
//        $fnCadastrar(66, 202);// Indicacao;
//        $fnCadastrar(67, 16);// Informe;
//        $fnCadastrar(68, 203);// Instrucao;
//        $fnCadastrar(69, 147);// Inventario;
//        $fnCadastrar(70, 54);// Laudo;
//        $fnCadastrar(71, 148);// Lei;
//        $fnCadastrar(72, 100);// Lista/Listagem;
//        $fnCadastrar(73, 56);// Livro;
//        $fnCadastrar(74, 69);// Mandado;
//        $fnCadastrar(75, 149);// Manifesto;
//        $fnCadastrar(76, 113);// Manual;
//        $fnCadastrar(77, 150);// Mapa;
//        $fnCadastrar(78, 151);// Medida Provisoria;
//        $fnCadastrar(79, 12);// Memorando;
//        $fnCadastrar(80, 13);// Memorando-circular;
//        $fnCadastrar(81, 152);// Memorial;
//        $fnCadastrar(82, 153);// Mensagem;
//        $fnCadastrar(83, 204);// Minuta;
//        $fnCadastrar(84, 235);// Mocao;
//        $fnCadastrar(85, 58);// Norma;
//        $fnCadastrar(86, 91);// Nota;
//        $fnCadastrar(87, 59);// Notificacao;
//        $fnCadastrar(88, 11);// Oficio;
//        $fnCadastrar(89, 205);// Oficio-Circular;
//        $fnCadastrar(90, 206);// Orcamento;
//        $fnCadastrar(91, 207);// Ordem;
//        $fnCadastrar(92, 155);// Organograma;
//        $fnCadastrar(93, 156);// Orientacao;
//        $fnCadastrar(94, 157);// Panfleto;
//        $fnCadastrar(95, 191);// Parecer;
//        $fnCadastrar(96, 158);// Passaporte;
//        $fnCadastrar(97, 159);// Pauta;
//        $fnCadastrar(98, 160);// Peticao;
//        $fnCadastrar(99, 104);// Planilha;
//        $fnCadastrar(100, 73);// Plano;
//        $fnCadastrar(101, 161);// Planta;
//        $fnCadastrar(102, 10);// Portaria;
//        $fnCadastrar(103, 162);// Precatorio;
//        $fnCadastrar(104, 71);// Processo;
//        $fnCadastrar(105, 60);// Procuracao;
//        $fnCadastrar(106, 163);// Programa;
//        $fnCadastrar(107, 101);// Projeto;
//        $fnCadastrar(108, 75);// Prontuario;
//        $fnCadastrar(109, 208);// Pronunciamento;
//        $fnCadastrar(110, 85);// Proposta;
//        $fnCadastrar(111, 164);// Prospecto;
//        $fnCadastrar(112, 165);// Protocolo;
//        $fnCadastrar(113, 166);// Prova;
//        $fnCadastrar(114, 167);// Questionario;
//        $fnCadastrar(115, 168);// Receita;
//        $fnCadastrar(116, 169);// Recibo;
//        $fnCadastrar(117, 111);// Recurso;
//        $fnCadastrar(118, 170);// Regimento;
//        $fnCadastrar(119, 62);// Registro;
//        $fnCadastrar(120, 171);// Regulamento;
//        $fnCadastrar(121, 102);// Relacao;
//        $fnCadastrar(122, 63);// Relatorio;
//        $fnCadastrar(123, 172);// Release;
//        $fnCadastrar(124, 173);// Representacao;
//        $fnCadastrar(125, 64);// Requerimento;
//        $fnCadastrar(126, 65);// Requisicao;
//        $fnCadastrar(127, 1);// Resolucao;
//        $fnCadastrar(128, 174);// Resumo;
//        $fnCadastrar(129, 175);// Roteiro;
//        $fnCadastrar(130, 176);// Sentenca;
//        $fnCadastrar(131, 177);// Sinopse;
//        $fnCadastrar(132, 178);// Solicitacao;
//        $fnCadastrar(133, 2);// Sumula;
//        $fnCadastrar(134, 179);// Tabela;
//        $fnCadastrar(135, 180);// Telegrama;
//        $fnCadastrar(136, 90);// Termo;
//        $fnCadastrar(137, 181);// Tese;
//        $fnCadastrar(138, 182);// Testamento;
//        $fnCadastrar(139, 183);// Titulo;
//        $fnCadastrar(140, 94);// Voto;
//        $fnCadastrar(141, 87);// Carteira;
//        $fnCadastrar(142, 209);// Cartao;
//        $fnCadastrar(143, 88);// CPF/CIC;
//        $fnCadastrar(144, 201);// CNPJ;
//        $fnCadastrar(145, 211);// Calendario
//        $fnCadastrar(147, 89);// RG;
//        $fnCadastrar(148, 95);// Agenda;
//        $fnCadastrar(149, 7);// Analise;
//        $fnCadastrar(150, 96);// Anotacao;
//        $fnCadastrar(151, 52);// audio;
//        $fnCadastrar(152, 97);// Boleto;
//        $fnCadastrar(153, 36);// Conta;
//        $fnCadastrar(154, 112);// Contrarrazoes;
//        $fnCadastrar(155, 38);// Correspondencia;
//        $fnCadastrar(156, 29);// Cota;
//        $fnCadastrar(157, 98);// Credencial;
//        $fnCadastrar(158, 39);// Croqui;
//        $fnCadastrar(159, 42);// Defesa;
//        $fnCadastrar(160, 44);// Demonstracao;
//        $fnCadastrar(161, 45);// Denuncia;
//        $fnCadastrar(162, 108);// Esclarecimento;
//        $fnCadastrar(163, 49);// Escrituracao
//        $fnCadastrar(165, 109);// Impugnacao;
//        $fnCadastrar(166, 92);// Informacao;
//        $fnCadastrar(167, 110);// Intencao;
//        $fnCadastrar(168, 55);// Licenca;
//        $fnCadastrar(169, 212);// Materia;
//        $fnCadastrar(170, 78);// Material;
//        $fnCadastrar(171, 57);// Memoria;
//        $fnCadastrar(172, 213);// Movimentacao;
//        $fnCadastrar(173, 107);// Pedido;
//        $fnCadastrar(174, 61);// Reclamacao;
//        $fnCadastrar(175, 27);// Referendo;
//        $fnCadastrar(176, 76);// Resultado;
//        $fnCadastrar(177, 53);// Video
                
        //----------------------------------------------------------------------
        // Tarefas
        //----------------------------------------------------------------------
        $objDTO = new TarefaDTO();

        $fnCadastrar = function($numIdTarefa, $strNome = '', $strHistoricoCompleto = 'N', $strHistoricoCompleto = 'N', $strFecharAndamentosAbertos = 'N', $strLancarAndamentoFechado = 'N', $strPermiteProcessoFechado = 'N') use($objDTO, $objBD){
          
            $objDTO->unSetTodos();
            $objDTO->setNumIdTarefa($numIdTarefa);

            if($objBD->contar($objDTO) == 0){   
                
                $objDTO->setStrNome($strNome);
                $objDTO->setStrSinHistoricoResumido($strHistoricoCompleto);
                $objDTO->setStrSinHistoricoCompleto($strHistoricoCompleto);
                $objDTO->setStrSinFecharAndamentosAbertos($strFecharAndamentosAbertos);
                $objDTO->setStrSinLancarAndamentoFechado($strLancarAndamentoFechado);
                $objDTO->setStrSinPermiteProcessoFechado($strPermiteProcessoFechado);
                
                $objBD->cadastrar($objDTO); 
            }   
        };
        
        
        $fnCadastrar(22, 'Em processamento. @MOTIVO@', 'S', 'S', 'S', 'N', 'N');
        $fnCadastrar(23, 'Bloqueado. @MOTIVO@', 'S', 'S', 'S', 'N', 'N');
        $fnCadastrar(501, 'Processo expedido para a entidade @UNIDADE_DESTINO@ - @REPOSITORIO_DESTINO@ (@PROCESSO@, @UNIDADE@, @USUARIO@)', 'S', 'S', 'N', 'S', 'N');
        $fnCadastrar(502, 'Processo recebido da entidade @ENTIDADE_ORIGEM@ - @REPOSITORIO_ORIGEM@ (@PROCESSO@, @ENTIDADE_ORIGEM@, @UNIDADE_DESTINO@, @USUARIO@)', 'S', 'S', 'N', 'S', 'N');
        $fnCadastrar(505, 'O processo foi recusado pelo orgão @UNIDADE_DESTINO@ pelo seguinte motivo: @MOTIVO@', 'S', 'S', 'N', 'N', 'S');
        $fnCadastrar(504, 'Expedição do Processo Cancelada em @DATA_HORA@ pelo Usuário @USUARIO@', 'S', 'S', 'N', 'S', 'N');
        $fnCadastrar(506, 'Operacao externa de @OPERACAO@ registrada em @DATA_HORA@ (@PESSOA_IDENTIFICACAO@ - @PESSOA_NOME@)\n @COMPLEMENTO@', 'S', 'S', 'N', 'S', 'N');

        //----------------------------------------------------------------------
        // Operações por Tarefas
        //----------------------------------------------------------------------
        $objDTO = new RelTarefaOperacaoDTO();

        $fnCadastrar = function($strCodigoOperacao, $numIdTarefa) use($objDTO, $objBD){

            $objDTO->unSetTodos();
            $objDTO->setStrCodigoOperacao($strCodigoOperacao);
            $objDTO->setNumIdTarefa($numIdTarefa);

            if($objBD->contar($objDTO) == 0){   
                $objBD->cadastrar($objDTO); 
            }   
        };

        //$fnCadastrar("01", 0);// Registro (Padrão);
        $fnCadastrar("02", 32);//  Envio de documento avulso/processo ($TI_PROCESSO_REMETIDO_UNIDADE = 32;);
        $fnCadastrar("03", 51);//  Cancelamento/exclusao ou envio de documento ($TI_CANCELAMENTO_DOCUMENTO = 51;);
        $fnCadastrar("04", 13);//  Recebimento de documento ($TI_RECEBIMENTO_DOCUMENTO = 13;);
        $fnCadastrar("05", 1);// Autuacao ($TI_GERACAO_PROCEDIMENTO = 1;);
        $fnCadastrar("06", 101);// Juntada por anexacao ($TI_ANEXADO_PROCESSO = 101;);
        //$fnCadastrar("07", 0);// Juntada por apensacao;
        //$fnCadastrar("08", 0);// Desapensacao;
        $fnCadastrar("09", 24);//  Arquivamento ($TI_ARQUIVAMENTO = 24;);
        //$fnCadastrar("10", 0);// Arquivamento no Arquivo Nacional;
        //$fnCadastrar("11", 0);// Eliminacao;
        //$fnCadastrar("12", 0);// Sinistro;
        //$fnCadastrar("13", 0);// Reconstituicao de processo;
        $fnCadastrar("14", 26);// Desarquivamento ($TI_DESARQUIVAMENTO = 26;);
        //$fnCadastrar("15", 0);// Desmembramento;
        //$fnCadastrar("16", 0);// Desentranhamento;
        //$fnCadastrar("17", 0);// Encerramento/abertura de volume no processo;
        //$fnCadastrar("18", 0);// Registro de extravio;
        
        $objDTO = new InfraAgendamentoTarefaDTO();

        $fnCadastrar = function($strComando, $strDesc) use($objDTO, $objBD, $objRN){

            $objDTO->unSetTodos();
            $objDTO->setStrComando($strComando);
            
            if($objBD->contar($objDTO) == 0){
                
                $objDTO->setStrDescricao($strDesc);
                $objDTO->setStrStaPeriodicidadeExecucao('D');
                $objDTO->setStrPeriodicidadeComplemento('0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23');
                $objDTO->setStrSinAtivo('S');
                $objDTO->setStrSinSucesso('S');
                
                $objBD->cadastrar($objDTO);
            }   
        };
        
        $fnCadastrar('PENAgendamentoRN::verificarTramitesRecusados', 'Verificação de processos recusados');
        $fnCadastrar('PENAgendamentoRN::seiVerificarServicosBarramento', 'Verificação dos serviços de fila de processamento estão em execução');
    
        //----------------------------------------------------------------------
        // Correções para id_unidade_rh
        //----------------------------------------------------------------------        
        $objDTO = new UnidadeDTO();
        $objDTO->retNumIdUnidade();
        
        $arrObjDTO = $objBD->listar($objDTO);
        if(!empty($arrObjDTO)) {
            
            $objDTO = new PenUnidadeDTO();
            
            foreach($arrObjDTO as $objUnidadeDTO) {
                
                $objDTO->unSetTodos();
                $objDTO->setNumIdUnidade($objUnidadeDTO->getNumIdUnidade());
                
                if($objBD->contar($objDTO) == 0) {
                    $objDTO->setNumIdUnidadeRH(0);
                    $objBD->cadastrar($objDTO);
    }
            }
        }
        //----------------------------------------------------------------------
    }
    
    /**
     * Tratamento da Fila do Gearman
     * 
     * @refs 3670
     */
    protected function instalarV002R003S000US024(){
        
        $objInfraBanco = $this->inicializarObjInfraIBanco();
        $objMetaBD = $this->inicializarObjMetaBanco();
        
        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_tramite_processado',
            'cols' => array(
                'id_tramite'=> array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'dth_ultimo_processamento' => array($objMetaBD->tipoDataHora(), PenMetaBD::NNULLO),
                'numero_tentativas' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'sin_recebimento_concluido' => array($objMetaBD->tipoTextoFixo(1), PenMetaBD::NNULLO)
            ),
            'pk' => array('id_tramite')
        )); 
        
        $objInfraParametro = new InfraParametro($objInfraBanco);
        $objInfraParametro->setValor('PEN_NUMERO_TENTATIVAS_TRAMITE_RECEBIMENTO', '3');
    }
    
    /**
     * Erro no histórico de processo no momento do recebimento
     * 
     * @refs 3671
     */
    protected function instalarV002R003S000IW001(){
        
        $objInfraBanco = $this->inicializarObjInfraIBanco();
        //$objMetaBD = $this->inicializarObjMetaBanco();
        
        
        $objDTO = new TarefaDTO();
        $objBD = new TarefaBD($objInfraBanco);
        
        $fnAlterar = function($numIdTarefa, $strNome) use($objDTO, $objBD){
          
            $objDTO->unSetTodos();
            $objDTO->setNumIdTarefa($numIdTarefa);
            $objDTO->setNumMaxRegistrosRetorno(1);
            $objDTO->retStrNome();
            $objDTO->retNumIdTarefa();
            
            $objDTO = $objBD->consultar($objDTO);
            
            if(empty($objDTO)){   
                
                $objDTO->setStrNome($strNome);
                $objBD->cadastrar($objDTO); 
            }else{
                
                $objDTO->setStrNome($strNome);
                $objBD->alterar($objDTO); 
            }   
        };
        
        $fnAlterar('502', 'Processo recebido da entidade @ENTIDADE_ORIGEM@ - @REPOSITORIO_ORIGEM@');
    }
    
    /**
     * Tratamento da Fila do Gearman para Recibos de Conclusão de Trâmite
     * 
     * @refs 3791
     */
    protected function instalarV002R003S001US035(){
        
        $objMetaBanco = $this->inicializarObjMetaBanco();

        if(!$objMetaBanco->isColuna('md_pen_tramite_processado', 'tipo_tramite_processo')) {
            $objMetaBanco->adicionarColuna('md_pen_tramite_processado', 'tipo_tramite_processo', 'CHAR(2)', PenMetaBD::NNULLO);
            $objMetaBanco->adicionarValorPadraoParaColuna('md_pen_tramite_processado', 'tipo_tramite_processo', 'RP');
        }
        
        if($objMetaBanco->isChaveExiste('md_pen_tramite_processado', 'pk_md_pen_tramite_processado')) {
        
            $objMetaBanco->removerChavePrimaria('md_pen_tramite_processado', 'pk_md_pen_tramite_processado');
            $objMetaBanco->adicionarChavePrimaria('md_pen_tramite_processado', 'pk_md_pen_tramite_processado', array('id_tramite', 'tipo_tramite_processo'));
        }
    }
    
    /**
     * Erro no Mapeamento Tipo de Documento de Envio
     * 
     * @refs 3870
     */
    protected function instalarV003R003S003IW001() {
        
        $objMetaBD = $this->inicializarObjMetaBanco();
        $objInfraBanco = $this->inicializarObjInfraIBanco();
        
        
        //----------------------------------------------------------------------
        // Novas sequências
        //----------------------------------------------------------------------
        $objInfraSequencia = new InfraSequencia($objInfraBanco);
        
        if(!$objInfraSequencia->verificarSequencia('md_pen_rel_doc_map_enviado')){
            $objInfraSequencia->criarSequencia('md_pen_rel_doc_map_enviado', '1', '1', '9999999999');
        }
        
        if(!$objInfraSequencia->verificarSequencia('md_pen_rel_doc_map_recebido')){
            $objInfraSequencia->criarSequencia('md_pen_rel_doc_map_recebido', '1', '1', '9999999999');
        }
        
        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_rel_doc_map_enviado',
            'cols' => array(
                'id_mapeamento'=> array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'codigo_especie'=> array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_serie' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'sin_padrao' => array($objMetaBD->tipoTextoFixo(1), 'S')
            ),
            'pk' => array('id_mapeamento'),
            //'uk' => array('codigo_especie', 'id_serie'),
            'fks' => array(
                'serie' => array('id_serie', 'id_serie'),
                'md_pen_especie_documental' => array('id_especie', 'codigo_especie'),
            )
        ));

        $objMetaBD->criarTabela(array(
            'tabela' => 'md_pen_rel_doc_map_recebido',
            'cols' => array(
                'id_mapeamento'=> array($objMetaBD->tipoNumeroGrande(), PenMetaBD::NNULLO),
                'codigo_especie'=> array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'id_serie' => array($objMetaBD->tipoNumero(), PenMetaBD::NNULLO),
                'sin_padrao' => array($objMetaBD->tipoTextoFixo(1), 'S')
            ),
            'pk' => array('id_mapeamento'),
            //'uk' => array('codigo_especie', 'id_serie'),
            'fks' => array(
                'serie' => array('id_serie', 'id_serie'),
                'md_pen_especie_documental' => array('id_especie', 'codigo_especie'),
            )
        )); 
        
        $objBD = new GenericoBD($objInfraBanco);
        
        if($objMetaBD->isTabelaExiste('md_pen_rel_tipo_documento_mapeamento_recebido')) {
                
            $objDTO = new PenRelTipoDocMapRecebidoDTO();

            $fnCadastrar = function($numCodigoEspecie, $numIdSerie) use($objDTO, $objBD){
                
                $objDTO->unSetTodos();
                $objDTO->setNumCodigoEspecie($numCodigoEspecie);
                $objDTO->setNumIdSerie($numIdSerie);

                if($objBD->contar($objDTO) == 0){
                    
                    $objDTO->setStrPadrao('S');
                    $objBD->cadastrar($objDTO); 
                }   
            };
            
            $arrDados = $objInfraBanco->consultarSql('SELECT DISTINCT codigo_especie, id_serie FROM md_pen_rel_tipo_documento_mapeamento_recebido');
            if(!empty($arrDados)) {
                foreach($arrDados as $arrDocMapRecebido) {

                    $fnCadastrar($arrDocMapRecebido['codigo_especie'], $arrDocMapRecebido['id_serie']);
                }
            }

            $objMetaBD->removerTabela('md_pen_rel_tipo_documento_mapeamento_recebido');  
        }
        
        
        if($objMetaBD->isTabelaExiste('md_pen_rel_serie_especie')) {
            
            $objDTO = new PenRelTipoDocMapEnviadoDTO();

            $fnCadastrar = function($numCodigoEspecie, $numIdSerie) use($objDTO, $objBD){
                
                $objDTO->unSetTodos();
                $objDTO->setNumCodigoEspecie($numCodigoEspecie);
                $objDTO->setNumIdSerie($numIdSerie);

                if($objBD->contar($objDTO) == 0){
                    
                    $objDTO->setStrPadrao('S');
                    $objBD->cadastrar($objDTO); 
                }   
            };
            
            $arrDados = $objInfraBanco->consultarSql('SELECT DISTINCT codigo_especie, id_serie FROM md_pen_rel_serie_especie');
            if(!empty($arrDados)) {
                foreach($arrDados as $arrDocMapEnviado) {

                    $fnCadastrar($arrDocMapEnviado['codigo_especie'], $arrDocMapEnviado['id_serie']);
                }
            }

            $objMetaBD->removerTabela('md_pen_rel_serie_especie');  
        }
    }
        
    protected function instalarV004R003S003IW002(){
        
        $strTipo = $this->inicializarObjMetaBanco()->tipoTextoGrande();

        $this->inicializarObjMetaBanco()
            ->alterarColuna('md_pen_recibo_tramite', 'cadeia_certificado', $strTipo)
            ->alterarColuna('md_pen_recibo_tramite_enviado', 'cadeia_certificado', $strTipo);         
    }
    
    /**
     * Criar script de BD para voltar o processo ao normal, como um processo de contingência
     * 
     * @version 4
     * @release 3
     * @sprint 5
     * @refs 4047
     * @return null
     */
    protected function instalarV005R003S005IW018(){
        
        $objBD = new GenericoBD($this->inicializarObjInfraIBanco());
        $objDTO = new TarefaDTO();

        $fnCadastrar = function($numIdTarefa, $strNome = '', $strHistoricoCompleto = 'N', $strHistoricoCompleto = 'N', $strFecharAndamentosAbertos = 'N', $strLancarAndamentoFechado = 'N', $strPermiteProcessoFechado = 'N') use($objDTO, $objBD){
          
            $objDTO->unSetTodos();
            $objDTO->setNumIdTarefa($numIdTarefa);

            if($objBD->contar($objDTO) == 0){   
                
                $objDTO->setStrNome($strNome);
                $objDTO->setStrSinHistoricoResumido($strHistoricoCompleto);
                $objDTO->setStrSinHistoricoCompleto($strHistoricoCompleto);
                $objDTO->setStrSinFecharAndamentosAbertos($strFecharAndamentosAbertos);
                $objDTO->setStrSinLancarAndamentoFechado($strLancarAndamentoFechado);
                $objDTO->setStrSinPermiteProcessoFechado($strPermiteProcessoFechado);
                
                $objBD->cadastrar($objDTO); 
            }   
        };
        
        $fnCadastrar(507, 'Expedição do processo foi abortada manualmente devido a falha no trâmite', 'S', 'S', 'N', 'N', 'S');
    }
    
        /**
     * Criar script de BD para voltar o processo ao normal, como um processo de contingência
     * 
     * @version 4
     * @release 3
     * @sprint 5
     * @refs 4047
     * @return null
     */
    protected function instalarV005R003S005IW023(){
        
        $objBD = new GenericoBD($this->inicializarObjInfraIBanco());
        
        $objDTO = new TarefaDTO();
        $objDTO->retNumIdTarefa();
        $objDTO->retStrNome();

        $fnAtualizar = function($numIdTarefa, $strNome) use($objDTO, $objBD){
          
            $objDTO->unSetTodos();
            $objDTO->setNumIdTarefa($numIdTarefa);

            $objTarefaDTO = $objBD->consultar($objDTO);
            
            if(!empty($objTarefaDTO)) {
                
                $objTarefaDTO->setStrNome($strNome);
                
                $objBD->alterar($objTarefaDTO);   
            } 
        };
        // Tramitação externa do processo @processo@ concluída com sucesso. Recebido na @UnidadeDestino@ - @hierarquia_superior@ -@repositório_de_estruturas@
        $fnAtualizar(501, 'Processo em tramitação externa para @UNIDADE_DESTINO@ - @UNIDADE_DESTINO_HIRARQUIA@ - @REPOSITORIO_DESTINO@');
        $fnAtualizar(502, 'Processo recebido da unidade externa @ENTIDADE_ORIGEM@ - @ENTIDADE_ORIGEM_HIRARQUIA@ - @REPOSITORIO_ORIGEM@');
        $fnAtualizar(506, 'Tramitação externa do processo @PROTOCOLO_FORMATADO@ concluída com sucesso. Recebido em @UNIDADE_DESTINO@ - @UNIDADE_DESTINO_HIRARQUIA@ - @REPOSITORIO_DESTINO@');
    }
}