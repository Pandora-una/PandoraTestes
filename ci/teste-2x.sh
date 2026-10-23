#!/bin/sh
# Roda a suíte na pilha 2.x (ZF2, PHP 7.2): vendor e imagem do area-restrita.
# Uso: ci/teste-2x.sh [<caminho do srv do area-restrita>] [argumentos do phpunit]
# Com PTESTES_GRAVAR=1 regrava os instantâneos (só aqui, e só de propósito).
set -e
SRV=${1:-$HOME/sistemas/area-restrita/srv}
[ $# -gt 0 ] && shift
exec docker run --rm -u "$(id -u):$(id -g)" \
  -v "$(cd "$(dirname "$0")/.." && pwd)":/pacote -v "$SRV/vendor":/vendor-ar:ro -w /pacote \
  -e PTESTES_VENDOR=/vendor-ar -e PTESTES_GRAVAR \
  --entrypoint php "${IMAGEM:-area-restrita-backend}" /vendor-ar/bin/phpunit "$@"
