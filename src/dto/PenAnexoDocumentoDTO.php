<?php
 
require_once DIR_SEI_WEB.'/SEI.php';
 
class PenAnexoDocumentoDTO extends AnexoDTO
{
  public function getStrNomeTabela(): ?string
    {
      return 'md_pen_anexo_documento';
  }
 
  public function getStrNomeSequenciaNativa(): string
    {
      return 'md_pen_seq_anexo_documento';
  }
}