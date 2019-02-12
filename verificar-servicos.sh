#!/bin/bash

PATH=/usr/bin:/sbin:/bin:/usr/sbin
export PATH

GEARMAN=$(ls /etc/init.d | grep -owih gearman.*)
if [[ -z $GEARMAN ]]; then
	echo "ERROR: Instalação do Gearman não pode ser localizada." 	
	exit 1
else
	ps cax | grep -ih gearman.* > /dev/null
	if [ $? -ne 0 ]; then
        echo "Gearman: Iniciando serviço de gerenciamento de fila de tarefas..."
		/etc/init.d/$GEARMAN start;	        
	fi
fi

SUPERVISOR=$(ls /etc/init.d | grep -owih supervisor.*)
if [[ -z $SUPERVISOR ]]; then
	echo "ERROR: Instalação do Supervisor não pode ser localizada."
	exit 1
else
	ps cax | grep -ih supervisor.* > /dev/null
	if [ $? -ne 0 ]; then
            echo "Supervisor: Iniciando serviço de monitoramento dos processos de integração..."
        	/etc/init.d/$SUPERVISOR start;
	else
	
	    COMMAND=$(ps -C php -f | grep -o "PendenciasTramiteRN.php");
	    if [ -z "$COMMAND" ]; then
            echo "Supervisor: Reiniciando serviço de monitoramento dos processos de integração..."
            /etc/init.d/$SUPERVISOR stop;
            /etc/init.d/$SUPERVISOR start;                 
	    fi
	
        COMMAND=$(ps -C php -f | grep -o "ProcessarPendenciasRN.php");
        if [ -z "$COMMAND" ]; then
            echo "Supervisor: Reiniciando serviço de monitoramento dos processos de integração..."
	        /etc/init.d/$SUPERVISOR stop;
	        /etc/init.d/$SUPERVISOR start;	                
	    fi
	fi
fi
