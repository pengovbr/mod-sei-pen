#!/bin/bash

PATH=/usr/bin:/sbin:/bin:/usr/sbin
export PATH

echo "Vamos iniciar a verificacao da fila de processamento... $(date)"

d=$(dirname "$0")
echo "dir: $d"
cd $d

python verificar-pendencias-represadas.py
r=$?

if [ "$r" == "2" ]; then
    echo "Retornou com erro critico. Reboot... $(date)"
    kill $(ps -ef | grep "MonitoramentoEnvioTarefasPEN.php" | grep -v grep | awk '{print $2}')
    kill $(ps -ef | grep "MonitoramentoRecebimentoTarefasPEN.php" | grep -v grep | awk '{print $2}')
    echo "rebootado"
    rm -rf pendencias.json
fi

exit 0