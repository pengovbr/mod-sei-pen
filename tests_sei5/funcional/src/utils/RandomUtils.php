<?php
/**
 * Gera uma string aleatória de comprimento especificado.
 *
 * Esta função foi criada para substituir uma biblioteca indisponível que gerava strings aleatórias.
 * Utiliza caracteres alfanuméricos (maiúsculos, minúsculos e dígitos) para compor a string.
 *
 * @param int $length Comprimento desejado da string aleatória. Padrão: 10.
 * @return string String aleatória gerada.
 */
function randomString($length = 10) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    return substr(str_shuffle(str_repeat($chars, ceil($length/strlen($chars)))), 0, $length);
}



