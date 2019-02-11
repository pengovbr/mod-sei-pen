#!/bin/bash

PATH=/usr/bin:/sbin:/bin:/usr/sbin
export PATH

GEARMAN=$(ls /etc/init.d | grep -owih gearman.* | grep -v job)
ps cax | grep -ih gearman.* | grep -v job > /dev/null
if [ $? -ne 0 ]; then
	/etc/init.d/$GEARMAN start;
        echo "Serviço gearman foi iniciado"
fi


SUPERVISOR=$(ls /etc/init.d | grep -owih supervisor.*)
ps cax | grep -ih supervisor.* > /dev/null
if [ $? -ne 0 ]; then
        /etc/init.d/$SUPERVISOR start;
        echo "Serviço supervisor foi iniciado"
else

	COMMAND=$(ps -C php -f | grep -o "PendenciasTramiteRN.php");
        if [ -z "$COMMAND" ]
        then
                /etc/init.d/$SUPERVISOR stop;
                /etc/init.d/$SUPERVISOR start;
                echo "Serviço supervisor foi reiniciado"
        fi

        COMMAND=$(ps -C php -f | grep -o "ProcessarPendenciasRN.php");
        if [ -z "$COMMAND" ]
        then
                /etc/init.d/$SUPERVISOR stop;
                /etc/init.d/$SUPERVISOR start;
                echo "Serviço supervisor foi reiniciado"
        fi
fi
