<?php
/**
 * Gera uma string aleat�ria de comprimento especificado.
 *
 * Esta fun��o foi criada para substituir uma biblioteca indispon�vel que gerava strings aleat�rias.
 * Utiliza caracteres alfanum�ricos (mai�sculos, min�sculos e d�gitos) para compor a string.
 *
 * @param int $length Comprimento desejado da string aleat�ria. Padr�o: 10.
 * @return string String aleat�ria gerada.
 */
function randomString($length = 10) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    return substr(str_shuffle(str_repeat($chars, ceil($length/strlen($chars)))), 0, $length);
}
