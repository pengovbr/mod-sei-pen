
$(
    function () {

        var idProcedimento = $('#hdnIdProcedimento').val();


        $('#txtProcedimentoApensado').on(
            'keyup', function (event) {
                event.preventDefault();


                var encontrou = false;
                var termo = $('#txtProcedimentoApensado').val().toLowerCase();

       


                $('#selProcedimentosApensados').find('option').each(
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
                )
           
     

            }
        );













    }
)


