$(
    function () {

        $.ajax(
            {
                url: PEN_BASE_PATH + '/util/url.php',
                type: 'post',
                dataType: 'json',
                data:
                        {
                            'class': 'ExpedirProcedimentoRN',
                            'action': 'consultarUnidadesHierarquia',
                            'selRepositorioEstruturas': opener.document.frmExpedirProcedimento.selRepositorioEstruturas.value
                },
                success: function (data) {
                    var html ='<br><br><div class="row">';
                    html += '<div class="span10">';
                    html += '<input type="text"  id="selecionarHirarquiaPai"  name="selecionarHirarquiaPai" value="" class="span10" placeholder="Selecionar"/>';
                    html += '</div>';
                    html += '</div>';
                    for (var i = 0; i < data.length; i++)
                    {
                        html += '<div class="row-colplay selecionar-sub-categoria" id="' + i + '" > ' + data[ i ].estrutura.nome + ' </div>';
                        html += '<div class="row-colplay-oculdo"   id="estruturaFilha' + i + '">';
                        html += '<div class="row">';
                        html += '<div class="span10">';
                        html += '<input type="text"  id="selecionarHirarquiaFilho"  name="selecionarHirarquiaFilho" value="" class="span10" placeholder="Selecionar"  />';
                        html += '</div>';
                        html += '</div>';
                        html += '<div class="row">';
                        html += '<div class="span10">';
                        html += '<ul>';

                        for (var j = 0; j < data[ i ].hierarquia.length; j++)
                        {
                            html += '<li class="selecionar-filter-hierarquia-filho"> <a href="#" class="selecionar-link-unidade" id=" ' + data[ i ].hierarquia[ j ].numeroDeIdentificacaoDaEstrutura + ' "> ' + data[ i ].hierarquia[ j ].nome + '</a></li>';
                        }
                        html += '</ul>';
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';

                    }

                    $('#recebeUnudadesHierarquias').html(html);
      
      
                    $('.selecionar-sub-categoria').click(
                        function () {
                            var idSel = $(this).attr('id');

                            $('.row-colplay-oculdo').css({'display': 'none'});
                            $('#estruturaFilha' + idSel).css({'display': 'block'});

                        }
                    );


                    $('#selecionarHirarquiaPai').on(
                        'keyup', function (event) {
                            event.preventDefault();


                            var encontrou = false;
                            var termo = $('#selecionarHirarquiaPai').val().toLowerCase();

                            $('.selecionar-sub-categoria').each(
                                function () {


                                    if ($(this).text().toLowerCase().indexOf(termo) > -1) {
                                        encontrou = true;

                                    }

                                    if (!encontrou) {
                                        $(this).hide();
                                    }
                                    else
                                    {
                                        $(this).show();
                                    }
                                    encontrou = false;
                                }
                            );

                        }
                    );





                    $('#selecionarHirarquiaFilho').on(
                        'keyup', function (event) {
                            event.preventDefault();


                            var encontrou = false;
                            var termo = $('#selecionarHirarquiaFilho').val().toLowerCase();

                            $('.selecionar-filter-hierarquia-filho').each(
                                function () {


                                    if ($(this).text().toLowerCase().indexOf(termo) > -1) {
                                        encontrou = true;

                                    }

                                    if (!encontrou) {
                                        $(this).hide();
                                    }
                                    else
                                    {
                                        $(this).show();
                                    }
                                    encontrou = false;
                                }
                            );

                        }
                    );


                    $('.selecionar-link-unidade').click(
                        function (event) {
                                   event.preventDefault();
                                   var numeroDeIdentificacaoDaEstrutura = $(this).attr('id');
     
                                   opener.document.frmExpedirProcedimento.txtUnidade.value= $(this).text();
                                   opener.document.frmExpedirProcedimento.hdnIdUnidade.value= numeroDeIdentificacaoDaEstrutura;
       
                                   window.close(); 
                        }
                    );
      
      
                }
            }
        );
            
            
           


    }
)