#!/bin/bash

if $(service gearmand status | grep -qv "running") ; then 
	service gearmand start;
	LOG="Servico gearman foi iniciado"
fi

if $(service supervisord status | grep -qv "running") ; then 
	service supervisord start;
	LOG="Servico supervisor foi iniciado"
else

	COMMAND=$(ps -C php -f | grep -o "PendenciasTramiteRN.php");

	if [ -z "$COMMAND" ] 
	then
		service supervisord restart;
		LOG="Servico supervisor foi reiniciado"
	fi

	COMMAND=$(ps -C php -f | grep -o "ProcessarPendenciasRN.php");
	
	if [ -z "$COMMAND" ]
	then
		service supervisord restart;
		LOG="Servico supervisor foi reiniciado"
	fi
fi

if [ -n "$LOG" ]
then
	/usr/bin/php console.php log --msg="$LOG" > /dev/null
fi


