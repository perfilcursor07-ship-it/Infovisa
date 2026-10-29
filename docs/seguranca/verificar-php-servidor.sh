#!/usr/bin/env bash
#
# InfoVisa - Verificacao pre-atualizacao do PHP no servidor
#
# Atende o passo 1 do checklist da secao 4 de
# docs/seguranca/oficio-ati-12-2026-atualizacao-php.md
# (OFICIO CIRCULAR 12/2026/GABPRES/ATI - SGD 2026/26819/009610,
#  fundamentado no Alerta 93/2026 do CTIR Gov).
#
# ESTE SCRIPT NAO ALTERA NADA. Ele apenas le versoes, configuracoes e
# extensoes e grava um relatorio em /tmp. Pode ser executado em producao com
# o sistema no ar.
#
# Uso:
#   bash verificar-php-servidor.sh
#   bash verificar-php-servidor.sh /caminho/da/aplicacao    # padrao: /var/www/html/infovisa
#
# O relatorio gerado deve ser anexado ao chamado da ATI em
# https://chamados.ati.to.gov.br
#

APP_DIR="${1:-/var/www/html/infovisa}"
RELATORIO="/tmp/infovisa-php-$(date +%Y%m%d-%H%M%S).txt"

# Versoes minimas exigidas pelo oficio, por serie
MIN_82=8.2.35
MIN_83=8.3.36
MIN_84=8.4.27
MIN_85=8.5.12

# Alvo recomendado para o InfoVisa (mesma serie em uso, menor risco de regressao)
ALVO=8.3.36

PROBLEMAS=0

titulo() { printf '\n==================================================================\n%s\n==================================================================\n' "$1"; }
sub()    { printf '\n--- %s\n' "$1"; }
ok()     { printf '  [OK]        %s\n' "$1"; }
alerta() { printf '  [ATENCAO]   %s\n' "$1"; }
falha()  { printf '  [NAO CONF]  %s\n' "$1"; PROBLEMAS=$((PROBLEMAS + 1)); }
info()   { printf '  %s\n' "$1"; }

# Retorna 0 se $1 >= $2 (comparacao de versao)
ver_ge() {
    [ "$(printf '%s\n%s\n' "$1" "$2" | sort -V 2>/dev/null | head -n1)" = "$2" ]
}

# Limpa sufixos de distribuicao: 8.3.14-1.el9.remi -> 8.3.14
limpa_versao() {
    printf '%s' "$1" | sed 's/[-~+].*$//' | grep -oE '^[0-9]+\.[0-9]+\.[0-9]+' 2>/dev/null
}

# Avalia uma versao contra as exigencias do oficio
avaliar() {
    local rotulo="$1" bruta="$2" v serie minimo
    v="$(limpa_versao "$bruta")"

    if [ -z "$v" ]; then
        alerta "$rotulo: nao foi possivel interpretar a versao ('$bruta')"
        return
    fi

    serie="$(printf '%s' "$v" | cut -d. -f1,2)"
    case "$serie" in
        8.2) minimo="$MIN_82" ;;
        8.3) minimo="$MIN_83" ;;
        8.4) minimo="$MIN_84" ;;
        8.5) minimo="$MIN_85" ;;
        *)
            falha "$rotulo: $v - serie $serie esta fora de suporte do PHP; nao ha versao corrigida nessa serie. Migrar para $ALVO."
            return
            ;;
    esac

    if ver_ge "$v" "$minimo"; then
        ok "$rotulo: $v (serie $serie exige >= $minimo) - CONFORME ao oficio"
        if [ "$serie" = "8.5" ]; then
            alerta "$rotulo: a serie 8.5 atende a ATI mas NAO e suportada por este projeto - phpoffice/phpspreadsheet 1.30.2 exige < 8.5.0 e composer install vai falhar. Use $ALVO."
        fi
    else
        falha "$rotulo: $v - serie $serie exige >= $minimo. ATUALIZACAO OBRIGATORIA (recomendado: $ALVO)."
    fi
}

# ------------------------------------------------------------------------------

executar() {

titulo "InfoVisa - verificacao pre-atualizacao do PHP"
info "Data/hora........: $(date '+%d/%m/%Y %H:%M:%S %Z')"
info "Servidor.........: $(hostname 2>/dev/null || echo '?')"
info "Sistema..........: $( { . /etc/os-release 2>/dev/null && echo "$PRETTY_NAME"; } || uname -sr )"
info "Kernel...........: $(uname -r 2>/dev/null)"
info "Usuario..........: $(id -un 2>/dev/null)"
info "Diretorio da app.: $APP_DIR"
info "Oficio...........: 12/2026/GABPRES/ATI - SGD 2026/26819/009610"
info "Versoes aceitas..: $MIN_82+ | $MIN_83+ | $MIN_84+ | $MIN_85+"

if ! printf '1\n2\n' | sort -V >/dev/null 2>&1; then
    alerta "'sort -V' indisponivel: a comparacao de versoes pode ser imprecisa. Confira os numeros manualmente."
fi

# -----------------------------------------------------------------------------
titulo "1. Versao do PHP"
# -----------------------------------------------------------------------------

sub "1.1 PHP da linha de comando (CLI)"
if command -v php >/dev/null 2>&1; then
    V_CLI="$(php -r 'echo PHP_VERSION;' 2>/dev/null)"
    info "Binario..........: $(command -v php)"
    info "Versao...........: ${V_CLI:-?}"
    info "Zend/Thread Safe.: $(php -r 'echo PHP_ZTS ? "ZTS" : "NTS";' 2>/dev/null)"
    avaliar "PHP CLI" "$V_CLI"
else
    falha "Comando 'php' nao encontrado no PATH deste usuario."
    V_CLI=""
fi

sub "1.2 PHP que REALMENTE atende as requisicoes (PHP-FPM em execucao)"
info "Esta e a versao que vale para o oficio: e o PHP que serve o site."
info "Ela pode diferir da versao do CLI quando ha mais de uma instalacao."

FPM_PID="$(pgrep -f 'php-fpm: master' 2>/dev/null | head -n1)"
[ -z "$FPM_PID" ] && FPM_PID="$(pgrep -x php-fpm 2>/dev/null | head -n1)"

if [ -n "$FPM_PID" ]; then
    FPM_BIN="$(readlink -f "/proc/$FPM_PID/exe" 2>/dev/null)"
    info "PID do master....: $FPM_PID"
    info "Binario em uso...: ${FPM_BIN:-'(sem permissao para ler /proc - rode com sudo)'}"
    if [ -n "$FPM_BIN" ] && [ -x "$FPM_BIN" ]; then
        V_FPM="$(limpa_versao "$("$FPM_BIN" -v 2>/dev/null | head -n1 | awk '{print $2}')")"
        info "Versao...........: ${V_FPM:-?}"
        avaliar "PHP-FPM (em execucao)" "$V_FPM"
        if [ -n "$V_CLI" ] && [ -n "$V_FPM" ] && [ "$(limpa_versao "$V_CLI")" != "$V_FPM" ]; then
            alerta "CLI ($V_CLI) e FPM ($V_FPM) DIVERGEM. Para o oficio vale a do FPM. Atualize ambos."
        fi
    else
        alerta "Nao foi possivel ler o binario do processo. Execute este script com sudo."
        V_FPM=""
    fi
else
    alerta "Nenhum processo php-fpm em execucao. O site pode usar mod_php ou estar parado."
    V_FPM=""
    if command -v php-fpm >/dev/null 2>&1; then
        info "php-fpm instalado: $(php-fpm -v 2>/dev/null | head -n1)"
    fi
fi

sub "1.3 Servicos web"
for svc in php-fpm httpd apache2 nginx; do
    if command -v systemctl >/dev/null 2>&1 && systemctl list-unit-files 2>/dev/null | grep -q "^${svc}"; then
        info "$(printf '%-10s' "$svc"): $(systemctl is-active "$svc" 2>/dev/null) / $(systemctl is-enabled "$svc" 2>/dev/null)"
    fi
done

sub "1.4 Outras instalacoes de PHP no servidor"
info "Varias instalacoes sao a causa mais comum de 'atualizei e nao mudou nada'."
ENCONTRADOS="$(ls -1 /usr/bin/php* /usr/local/bin/php* /opt/remi/*/root/usr/bin/php /opt/rh/*/root/usr/bin/php /opt/plesk/php/*/bin/php 2>/dev/null)"
if [ -n "$ENCONTRADOS" ]; then
    printf '%s\n' "$ENCONTRADOS" | while IFS= read -r b; do
        [ -x "$b" ] || continue
        printf '  %-45s %s\n' "$b" "$("$b" -v 2>/dev/null | head -n1 | awk '{print $2}')"
    done
else
    info "(nenhuma instalacao alternativa localizada nos caminhos usuais)"
fi

# -----------------------------------------------------------------------------
titulo "2. Configuracao a preservar na atualizacao"
# -----------------------------------------------------------------------------
info "Anote os valores abaixo: eles precisam ser reaplicados no php.ini depois"
info "da atualizacao (passo 8 do checklist). Em PHP-FPM as diretivas php_value"
info "do .htaccess NAO se aplicam."

sub "2.1 Arquivos de configuracao em uso"
if command -v php >/dev/null 2>&1; then
    php -i 2>/dev/null | grep -E '^(Configuration File|Loaded Configuration|Scan this dir|Additional \.ini)' || info "(php -i indisponivel)"
fi
for d in /etc/php-fpm.d /etc/php/*/fpm/pool.d; do
    [ -d "$d" ] && info "Pools do FPM.....: $d  ($(ls -1 "$d" 2>/dev/null | tr '\n' ' '))"
done

sub "2.2 Diretivas relevantes do InfoVisa"
if command -v php >/dev/null 2>&1; then
    for d in upload_max_filesize post_max_size memory_limit max_execution_time max_input_time max_file_uploads date.timezone expose_php display_errors; do
        printf '  %-22s = %s\n' "$d" "$(php -r "echo ini_get('$d') === false ? '?' : (ini_get('$d') === '' ? '(vazio)' : ini_get('$d'));" 2>/dev/null)"
    done
    info ""
    info "Esperado pelo InfoVisa: upload_max_filesize=64M, post_max_size=128M,"
    info "memory_limit=512M, max_execution_time=300, max_input_time=300."
    EXPOE="$(php -r "echo ini_get('expose_php') ? 'On' : 'Off';" 2>/dev/null)"
    if [ "$EXPOE" = "On" ]; then
        alerta "expose_php=On: o servidor anuncia a versao do PHP nos cabecalhos HTTP. Desligue (passo 15 do checklist)."
    else
        ok "expose_php=Off (a versao do PHP nao e exposta nos cabecalhos)"
    fi
    info "Observacao: os valores acima sao os do CLI. Confirme os do FPM na pagina"
    info "de status da aplicacao ou no php.ini do pool, pois podem diferir."
fi

# -----------------------------------------------------------------------------
titulo "3. Extensoes PHP carregadas"
# -----------------------------------------------------------------------------
info "Todas as marcadas como OBRIGATORIA precisam continuar habilitadas depois"
info "da atualizacao, senao o InfoVisa quebra (passo 7 do checklist)."

if command -v php >/dev/null 2>&1; then
    php -m 2>/dev/null | tr 'A-Z' 'a-z' | sed 's/ //g' | grep -v '^\[' | grep -v '^$' | sort -u > /tmp/.infovisa-ext.$$ 2>/dev/null

    sub "3.1 Exigidas pelo InfoVisa"
    for ext in ctype curl dom fileinfo filter gd hash iconv json libxml mbstring openssl pcre pdo pdo_pgsql pgsql session simplexml tokenizer xml xmlreader xmlwriter zip zlib; do
        if grep -qx "$ext" /tmp/.infovisa-ext.$$ 2>/dev/null; then
            printf '  [OK]        %-14s OBRIGATORIA\n' "$ext"
        else
            falha "$(printf '%-14s OBRIGATORIA - AUSENTE' "$ext")"
        fi
    done

    sub "3.2 Lista completa"
    tr '\n' ' ' < /tmp/.infovisa-ext.$$ 2>/dev/null; printf '\n'
    info "Total: $(wc -l < /tmp/.infovisa-ext.$$ 2>/dev/null | tr -d ' ') extensoes"
    info "Guardado em /tmp/php-modules-antes.txt para comparar depois da atualizacao."
    cp /tmp/.infovisa-ext.$$ /tmp/php-modules-antes.txt 2>/dev/null
    rm -f /tmp/.infovisa-ext.$$ 2>/dev/null

    sub "3.3 Aceleradores"
    php -r 'echo "  OPcache......: ", (extension_loaded("Zend OPcache") ? "carregado (habilitado=" . (ini_get("opcache.enable") ? "sim" : "nao") . ")" : "ausente"), "\n";' 2>/dev/null
    php -r 'if (extension_loaded("xdebug")) echo "  Xdebug.......: PRESENTE - nao deve ficar ativo em producao\n"; else echo "  Xdebug.......: ausente (correto para producao)\n";' 2>/dev/null
fi

# -----------------------------------------------------------------------------
titulo "4. Aplicacao"
# -----------------------------------------------------------------------------

if [ -d "$APP_DIR" ]; then
    ok "Diretorio encontrado: $APP_DIR"

    sub "4.1 Identificacao"
    if [ -f "$APP_DIR/artisan" ]; then
        ok "artisan presente (aplicacao Laravel)"
        # Aceita apenas a linha no formato esperado: se o autoload estiver
        # incompleto, 'php artisan' imprime um fatal error no stdout e nao deve
        # poluir o relatorio.
        A_VER="$( cd "$APP_DIR" 2>/dev/null && php artisan --version --no-ansi 2>/dev/null | grep -iE 'laravel framework' | head -n1 )"
        if [ -n "$A_VER" ]; then
            info "$A_VER"
        else
            alerta "'php artisan --version' nao respondeu. Vendor incompleto, permissao ou erro de configuracao - rode 'composer install' e tente de novo."
        fi
    else
        alerta "Nao ha 'artisan' em $APP_DIR - confirme se este e o diretorio do InfoVisa Laravel."
        info "Se o endereco publico servir outro sistema (ASP.NET, por exemplo), o"
        info "InfoVisa Laravel pode estar em outro caminho ou host."
    fi
    [ -f "$APP_DIR/composer.json" ] && info "Restricao de PHP do projeto: $(grep -oE '"php"[[:space:]]*:[[:space:]]*"[^"]*"' "$APP_DIR/composer.json" 2>/dev/null | head -n1)"

    sub "4.2 Ambiente (sem expor segredos)"
    if [ -f "$APP_DIR/.env" ]; then
        A_ENV="$(grep -E '^APP_ENV=' "$APP_DIR/.env" 2>/dev/null | head -n1 | cut -d= -f2- | tr -d '"'"'"' ')"
        A_DBG="$(grep -E '^APP_DEBUG=' "$APP_DIR/.env" 2>/dev/null | head -n1 | cut -d= -f2- | tr -d '"'"'"' ')"
        info "APP_ENV..........: ${A_ENV:-'(nao definido)'}"
        info "APP_DEBUG........: ${A_DBG:-'(nao definido)'}"
        case "$A_ENV" in
            production|producao) ok "APP_ENV=production" ;;
            "") alerta "APP_ENV nao definido" ;;
            *) alerta "APP_ENV='$A_ENV' em um servidor de producao - deveria ser 'production'." ;;
        esac
        case "$(printf '%s' "$A_DBG" | tr 'A-Z' 'a-z')" in
            true|1|on)
                falha "APP_DEBUG=true em producao: expoe stack traces, caminhos e trechos de configuracao a qualquer visitante. Corrija junto com a atualizacao."
                ;;
            false|0|off) ok "APP_DEBUG=false" ;;
            *) alerta "APP_DEBUG='$A_DBG' - verifique" ;;
        esac
        info "(nenhum outro valor do .env e lido ou gravado por este script)"
    else
        alerta "Nao ha .env em $APP_DIR"
    fi

    sub "4.3 Composer"
    if [ -f "$APP_DIR/composer.phar" ]; then
        C_VER="$(cd "$APP_DIR" && php composer.phar --version --no-ansi 2>/dev/null | grep -iE 'composer version' | head -n1)"
        info "composer.phar....: ${C_VER:-(nao foi possivel obter a versao)}"
        info ""
        info "Requisitos de plataforma (a linha 'php' deve estar 'success' DEPOIS da atualizacao):"
        ( cd "$APP_DIR" && php composer.phar check-platform-reqs --no-dev --no-ansi 2>/dev/null | grep -E '^(php|php-64bit) ' | sed 's/^/  /' ) || info "  (nao foi possivel executar)"
        info ""
        info "Enquanto o PHP nao for atualizado, a linha 'php' aparece como 'failed'."
        info "Isso e esperado: e a trava de seguranca exigida pelo oficio."
    elif command -v composer >/dev/null 2>&1; then
        C_VER="$(composer --version --no-ansi 2>/dev/null | grep -iE 'composer version' | head -n1)"
        if [ -n "$C_VER" ]; then
            info "composer.........: $C_VER"
        else
            alerta "Composer em $(command -v composer) nao respondeu a --version."
        fi
    else
        alerta "Composer nao localizado em $APP_DIR nem no PATH."
    fi
else
    alerta "Diretorio $APP_DIR nao existe. Informe o caminho correto:"
    info "  bash verificar-php-servidor.sh /caminho/da/aplicacao"
fi

# -----------------------------------------------------------------------------
titulo "5. Pacotes disponiveis para atualizacao"
# -----------------------------------------------------------------------------
info "Consulta somente leitura ao gerenciador de pacotes. Nada e instalado."

if command -v dnf >/dev/null 2>&1; then
    info "Gerenciador......: dnf"
    sub "5.1 Pacotes PHP instalados"
    rpm -qa 2>/dev/null | grep -iE '^php' | sort | sed 's/^/  /' | head -40 || info "(nenhum)"
    sub "5.2 Modulo PHP ativo (AppStream)"
    dnf module list --enabled php 2>/dev/null | sed 's/^/  /' | head -15 || info "(sem modulos)"
elif command -v yum >/dev/null 2>&1; then
    info "Gerenciador......: yum"
    rpm -qa 2>/dev/null | grep -iE '^php' | sort | sed 's/^/  /' | head -40
elif command -v apt >/dev/null 2>&1; then
    info "Gerenciador......: apt"
    dpkg -l 2>/dev/null | awk '/^ii[[:space:]]+php/ {printf "  %-40s %s\n", $2, $3}' | head -40
else
    alerta "Gerenciador de pacotes nao identificado."
fi

# -----------------------------------------------------------------------------
titulo "6. Conclusao"
# -----------------------------------------------------------------------------

V_VALE="${V_FPM:-$V_CLI}"
ORIGEM="PHP-FPM"
[ -z "$V_FPM" ] && ORIGEM="PHP CLI (FPM nao detectado)"

info "Versao que vale para o oficio ($ORIGEM): ${V_VALE:-'NAO DETERMINADA'}"
info "Versao alvo recomendada.................: $ALVO"
info ""

if [ "$PROBLEMAS" -eq 0 ]; then
    ok "Nenhuma nao conformidade detectada."
    info ""
    info "Proximos passos:"
    info "  1. Anexe este relatorio ao chamado da ATI."
    info "  2. Informe a versao em uso e que o sistema esta regularizado."
else
    falha "$PROBLEMAS item(ns) em nao conformidade - veja as linhas [NAO CONF] acima."
    info ""
    info "Proximos passos:"
    info "  1. Anexe este relatorio ao chamado em https://chamados.ati.to.gov.br"
    info "     referenciando o SGD 2026/26819/009610."
    info "  2. Siga o checklist da secao 4 de"
    info "     docs/seguranca/oficio-ati-12-2026-atualizacao-php.md"
    info "     (backup -> atualizacao para $ALVO -> extensoes -> php.ini -> restart)."
    info "  3. Protocole tambem a justificativa de acesso externo (secao 5 do"
    info "     mesmo documento), exigida pelo item 7 do oficio."
    info "  4. Rode este script novamente apos a atualizacao para comprovar a"
    info "     regularizacao."
fi

info ""
info "Lembrete: atualize o PHP primeiro em homologacao (passo 5 do checklist)."
info "Nao adote a serie 8.5: phpoffice/phpspreadsheet 1.30.2 exige < 8.5.0."
printf '\n'

}

# ------------------------------------------------------------------------------

# Remove sequencias de cor ANSI para que o relatorio anexado ao chamado fique
# em texto limpo.
executar 2>&1 | sed 's/\x1b\[[0-9;]*[mGKHF]//g' | tee "$RELATORIO"

printf '\n'
printf 'Relatorio gravado em: %s\n' "$RELATORIO"
printf 'Extensoes atuais em.: /tmp/php-modules-antes.txt\n'
printf 'Anexe o relatorio ao chamado da ATI (SGD 2026/26819/009610).\n'
printf '\n'
