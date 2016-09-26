<?php



$class     =  $_POST['class'];
$metodo = $_POST['action'];

if(file_exists("rn/{$class}.php"))
{
    require_once "rn/{$class}.php";
}else
{
  if(file_exists("../rn/{$class}.php") )
{
    require_once "../rn/{$class}.php";
}else
{
 if(file_exists("../../rn/{$class}.php") )
{
    require_once "../../rn/{$class}.php";
}else
{
    if(file_exists("../../../rn/{$class}.php") )
{
    require_once "../../../rn/{$class}.php";
}  else {
        throw new Exception("Arquivo {$objClass} não reconhecida !",404);
} 
}
}
}
  

$objClass = new $class();
$objClass->$metodo();