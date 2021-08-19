"""
Rotina verificadora para notificar ferramentas de monitoramento sobre tramites parados
sem processamento no barramento
Lista todos os tramites e salva em um arquivo a situacao com a data/hora
Nas execucoes seguintes, compara as datas com os tramites atuais para definir o
tempo que estah parado no barramento

Autor: Marcelo Linhares Castro (marlinhares@gmail.com)
Original em:
https://github.com/spbgovbr/mod-sei-pen

Modo de uso:
- certifique-se q hah permissao de leitura e escrita na pasta atual
- certifique-se q o certificado esteja na pasta atual como certall.pem, ou
que o parametro esteja corretamente apontando para o caminho
- certifique-se que hah rota para o barramento
- execute como indicado abaixo

Execute:
python pendencias-represadas.py
ou, usando argumentos para sobrescrever os valores default de execucao:
python pendencias-represadas.py intMaxTimeWarning intMaxTimeCritical strCaminhoCert strURL
onde
- intMaxTimeWarning: tempo (mins) do tramite parado no barramento para gerar warning
- intMaxTimeCritical: tempo (mins) do tramite parado no barramento para gerar critical
- strCaminhoCert: caminho completo do certificado para conectar no barramento
- strURL: url do barramento para recuperar as pendencias

Retornos da rotina:
exit 0 - sucesso
exit 1 - warning (processo ha no minimo MAXTIME_WARNING parado no barramento)
exit 2 - critical (processo ha pelo menos MAXTIME_CRITICAL parado no barramento)
"""

import json
import os
import sys
from datetime import datetime

# tempo (mins) para gerar warning. Programa retorna com exit 1 - Warning do Nagios
MAXTIME_WARNING = 60
# tempo (mins) para gerar critical. Programa retorna com exit 2 - Critical do Nagios
MAXTIME_CRITICAL = 90
# caminho completo do certificado para conectar no barramento
CERT = "./certall.pem"
# url para recuperar as pendencias no barramento
API_URL = "https://api.conectagov.processoeletronico.gov.br/interoperabilidade/rest/v3/tramites/pendentes"

# vamos sobrescrever os valores padrao, caso existam argumentos
try:
    args = sys.argv
    if( len(sys.argv) > 1): MAXTIME_WARNING = int(args[1])
    if( len(sys.argv) > 2): MAXTIME_CRITICAL = int(args[2])
    if( len(sys.argv) > 3): CERT = str(args[3])
    if( len(sys.argv) > 4): API_URL = str(args[4])

except Exception as e:
    print('Falha ao ler ou setar parametros: '+ str(e))
    exit(4)

COMANDO_PENDENCIAS = 'curl -s -S --cert ' + CERT + ' ' + API_URL

# buscar as pendencias
try:
    strPends = os.popen(COMANDO_PENDENCIAS).read()
    tmp = json.loads(strPends)
except Exception as e:
    print('Falha ao ler ou abrir a lista de pendencias do barramento. ' + str(e))
    print('Verifique se o certificado eh aceito pelo barrramento. Rode o seguinte comando e ')
    print('certifique-se que retorne um json valido: ' + COMANDO_PENDENCIAS)
    exit(4)

# transformar pendencias em um json plano (cada elemento um idt unico)
jsoPends = {}
for t in tmp:
    j = {
            str(t['IDT']):
                {
                    "status": t['status']
                }
        }
    jsoPends.update(j)


# ler arquivo de pendencias anterior
if not os.path.isfile('pendencias.json'):
    with open('pendencias.json', 'w') as json_file:
        json.dump({'1': '1'}, json_file)

try:
    with open('pendencias.json', 'r') as json_file:
        jsoAntigos = json.load(json_file)
except:
    print('Excessao ao abrir ou carregar o arquivo pendencias.json. Apague o mesmo e verifique se ha permissao suficiente no diretorio')
    exit(4)

# apagar tramites ja efetuados do arquivo de pendencias
lstApagar = []
for idt in jsoAntigos:
    if not (idt in jsoPends):
        lstApagar.append(idt)

for idt in lstApagar:
    jsoAntigos.pop(idt)


# agora vamos verificar se ha algo pendente dentro do intervalo de tempo determinado
for idt in sorted(jsoPends):

    dtAtual = datetime.now()

    if ((idt in jsoAntigos) and (jsoPends[idt]['status'] == jsoAntigos[idt]['status'])):

        dtAntiga = datetime.strptime( jsoAntigos[idt]['tempo'] , '%Y-%m-%d %H:%M')
        flDelta = (dtAtual - dtAntiga)
        flDelta = (flDelta.microseconds + (flDelta.seconds + flDelta.days * 24 * 3600) * 10**6) / 10**6 / 60

        if ( flDelta >= MAXTIME_WARNING and flDelta < MAXTIME_CRITICAL ):
            print("IDT: " + str(idt) + " --- " + str(flDelta) + " minutos parado no barramento")
            exit(1)
        elif ( flDelta >= MAXTIME_CRITICAL ):
            print("IDT: " + str(idt) + " --- " + str(flDelta) + " minutos parado no barramento")
            exit(2)

    # vamos atualizar a lista com a data/status atual caso o idt nao exista nela
    newJson = {
                "status": jsoPends[idt]['status'],
                "tempo": dtAtual.strftime('%Y-%m-%d %H:%M')
              }

    if( (idt not in jsoAntigos) or (jsoAntigos[idt]['status'] != jsoPends[idt]['status'] ) ):
        jsoAntigos[idt] = newJson

    # salvar a data e status dos idts pendentes para comparar na prox execucao
    with open('pendencias.json', 'w') as json_file:
        json.dump(jsoAntigos, json_file)

# caso tenha chegado ate aqui significa q o processamento esta em dia
exit(0)