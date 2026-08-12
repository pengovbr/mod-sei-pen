#!/bin/sh

set -e

npm install -g terser >/dev/null
# Versao fixada: este script reescreve todo o JS que vai no pacote distribuido.
# --ignore-scripts evita postinstall.
npm install -g --ignore-scripts terser@5 >/dev/null

find /app -name '*.js' | while IFS= read -r file; do
# vendor fica de fora: todo .js de la e' do php_codesniffer (require-dev), apagado
# logo depois pelo 'composer install --no-dev'.
find /app -path '/app/vendor' -prune -o -name '*.js' -print | while IFS= read -r file; do
    iconv -f ISO-8859-1 -t UTF-8 "$file" \
    | terser --compress --mangle \
    | iconv -f UTF-8 -t ISO-8859-1 > "$file.tmp"

    # 'set -e' nao pega falha no meio do pipeline: o status e' o do ultimo iconv,
    # que termina bem com entrada vazia. Sem isto, o .js original virava vazio.
    if [ ! -s "$file.tmp" ]; then
        rm -f "$file.tmp"
        echo "ERRO: minificacao de $file resultou em arquivo vazio; original preservado." >&2
        exit 1
    fi

    mv "$file.tmp" "$file"
done
