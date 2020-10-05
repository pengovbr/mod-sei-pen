#!/usr/bin/env bash

# Atribuição dos parâmetros de configuração do SEI
if [ -f /opt/sei/config/ConfiguracaoSEI.php ] && [ ! -f /opt/sei/config/ConfiguracaoSEI.php~ ]; then
    mv /opt/sei/config/ConfiguracaoSEI.php /opt/sei/config/ConfiguracaoSEI.php~
fi

if [ ! -f /opt/sei/config/ConfiguracaoSEI.php ]; then
    cp /ConfiguracaoSEI.php /opt/sei/config/ConfiguracaoSEI.php
fi

# Atribuição dos parâmetros de configuração do SIP
if [ -f /opt/sip/config/ConfiguracaoSip.php ] && [ ! -f /opt/sip/config/ConfiguracaoSip.php~ ]; then
    mv /opt/sip/config/ConfiguracaoSip.php /opt/sip/config/ConfiguracaoSip.php~
fi

if [ ! -f /opt/sip/config/ConfiguracaoSip.php ]; then
    cp /ConfiguracaoSip.php /opt/sip/config/ConfiguracaoSip.php
fi

# Ajustes de permissões diversos para desenvolvimento do SEI
chmod +x /opt/sei/bin/wkhtmltopdf-amd64
chmod +x /opt/sei/bin/pdfboxmerge.jar
chmod -R 777 /opt/sei/temp
chmod -R 777 /opt/sip/temp
chmod -R 777 /var/sei/arquivos

# Inicialização das rotinas de agendamento
/etc/init.d/rsyslog start
/etc/init.d/crond start

# Atualização do endereço de host da aplicação
echo "Slepping..." && sleep 45
SEI_HOST_URL=${SEI_HOST_URL:-"http://localhost"}
SEI_DATABASE_USER=${SEI_DATABASE_USER:-"root"}
SEI_DATABASE_PASSWORD=${SEI_DATABASE_PASSWORD:-"root"}
MYSQL_CMD="mysql --host mysql --user $SEI_DATABASE_USER --password=$SEI_DATABASE_PASSWORD"
$MYSQL_CMD -e "update sistema set pagina_inicial='$SEI_HOST_URL/sip' where sigla='SIP';" sip
$MYSQL_CMD -e "update sistema set pagina_inicial='$SEI_HOST_URL/sei/inicializar.php' where sigla='SEI';" sip

echo "update sip.sistema set pagina_inicial='$SEI_HOST_URL/sip' where sigla='SIP';" | sqlplus64 sei/sei_user@oracle
echo "update sip.sistema set pagina_inicial='$SEI_HOST_URL/sei/inicializar.php', web_service='$SEI_HOST_URL/sei/controlador_ws.php?servico=sip' where sigla='SEI';" | sqlplus64 sei/sei_user@oracle

echo "use sip" > /tmp/update.tmp
echo "go" >> /tmp/update.tmp
echo "update sistema set pagina_inicial='$SEI_HOST_URL/sip' where sigla='SIP'" >> /tmp/update.tmp
echo "go" >> /tmp/update.tmp
cat /tmp/update.tmp | tsql -S sqlserver -U sip_user -P sip_user

echo "use sip" > /tmp/update.tmp
echo "go" >> /tmp/update.tmp
echo "update sistema set pagina_inicial='$SEI_HOST_URL/sei/inicializar.php', web_service='$SEI_HOST_URL/sei/controlador_ws.php?servico=sip' where sigla='SEI'" >> /tmp/update.tmp
echo "go" >> /tmp/update.tmp
cat /tmp/update.tmp | tsql -S sqlserver -U sip_user -P sip_user

# Inicialização do servidor web
/usr/sbin/httpd -DFOREGROUND
