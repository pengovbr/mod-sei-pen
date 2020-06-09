#!/bin/bash

PATH=/usr/bin:/sbin:/bin:/usr/sbin
export PATH


d=$(dirname "$0")
echo "dir: $d"
cd $d

python verificar-pendencias-represadas.py
r=$?

if [ "$r" == "2" ]; then
    echo "reboot"
    supervisorctl stop all;
    systemctl stop gearmand;
    systemctl start gearmand;
    supervisorctl start all;
    
    echo "rebootado"
    rm -rf pendencias.json
fi

exit 0
