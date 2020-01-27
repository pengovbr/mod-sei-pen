########################################
## Arquivo de configuração padrão do módulo mod-sei-pen para funcionamento no Supervisor 4
## As diretivas abaixo deverão ser revisadas e modificadas durante o processo de instalação
## seguindo as orientações presente no arquivo README.md deste projeto
##<?php

[program:sei_processar_pendencias]
command=/usr/bin/php -c /etc/php.ini %(here)s/../rn/ProcessarPendenciasRN.php
directory=/opt/sei/web
process_name=%(program_name)s_%(process_num)02d
numprocs=4
user=apache
autostart=true
autorestart=true
startsecs=5
startretries=1000
log_stdout=true
log_stderr=true
logfile_backups=50
logfile_maxbytes=10MB
logfile=/var/log/supervisor/sei_processar_pendencias.log
stdout_logfile=/var/log/supervisor/sei_processar_pendencias.log-out
stderr_logfile=/var/log/supervisor/sei_processar_pendencias.log-err
stderr_events_enabled=true

[program:sei_monitorar_pendencias]
command=/usr/bin/php -c /etc/php.ini %(here)s/../rn/PendenciasTramiteRN.php
directory=/opt/sei/web
numprocs=1
user=apache
autostart=true
autorestart=true
startsecs=5
startretries=1000
log_stdout=true
log_stderr=true
logfile_maxbytes=10MB
logfile_backups=50
logfile=/var/log/supervisor/sei_monitorar_pendencias.log
stdout_logfile=/var/log/supervisor/sei_monitorar_pendencias.log-out
stderr_logfile=/var/log/supervisor/sei_monitorar_pendencias.log-err
stderr_events_enabled=true
