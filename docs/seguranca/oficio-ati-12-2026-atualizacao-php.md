# Resposta ao OFÍCIO CIRCULAR Nº 12/2026/GABPRES/ATI — Atualização do PHP

| Campo | Valor |
|---|---|
| Ofício | OFÍCIO CIRCULAR Nº 12/2026/GABPRES/ATI |
| SGD | 2026/26819/009610 |
| Origem | Agência de Tecnologia da Informação do Tocantins (ATI-TO) — Palmas, 22/10/2026 |
| Fundamento | Alerta nº 93/2026 do CTIR Gov |
| Sistema | InfoVisa — Sistema Estadual de Vigilância Sanitária do Tocantins |
| Stack | Laravel 11.48.0 / PHP 8.3 / PostgreSQL / Apache (httpd + php-fpm) |
| Caminho em produção | `/var/www/html/infovisa` |
| Documento elaborado em | 29/10/2026 |

O ofício determina a atualização do PHP para **8.2.35, 8.3.36, 8.4.27, 8.5.12 ou superior** e
restringe o acesso externo de aplicações PHP até a regularização. Este documento registra o
levantamento feito no repositório do InfoVisa, o que já foi corrigido no código versionado, o que
depende da equipe de infraestrutura e a justificativa institucional de manutenção do acesso externo
exigida pelo item 7 do ofício.

---

## 1. Situação encontrada (levantamento com números reais)

### 1.1 Versão do PHP em execução

Ambiente de desenvolvimento (WAMP, Windows):

```
$ php -v
PHP 8.3.14 (cli) (built: Nov 19 2024 15:53:22) (ZTS Visual C++ 2019 x64)
Zend Engine v4.3.14
    with Zend OPcache v8.3.14
    with Xdebug v3.3.2
```

**PHP 8.3.14 — NÃO conforme.** A série 8.3 exige 8.3.36 ou superior; faltam 22 versões de patch.

Builds de PHP disponíveis no WAMP local (`C:\wamp64\bin\php\`) e sua conformidade com o ofício:

| Build instalado | Mínimo exigido na série | Situação |
|---|---|---|
| php7.4.33 | — (série sem suporte) | Não conforme (fim de vida) |
| php8.0.30 | — (série sem suporte) | Não conforme (fim de vida) |
| php8.1.31 | — (série sem suporte) | Não conforme (fim de vida) |
| php8.2.26 | 8.2.35 | Não conforme |
| **php8.3.14** (em uso) | **8.3.36** | **Não conforme** |
| php8.4.0 | 8.4.27 | Não conforme |

Nenhum build corrigido está instalado no ambiente local — a atualização é obrigatória, não há como
apenas apontar o WAMP para outra pasta já existente.

Ambiente de **produção** (verificado em 29/10/2026 com
[`verificar-php-servidor.sh`](verificar-php-servidor.sh), executado como root no servidor):

| Item | Valor |
|---|---|
| Servidor | `sses482001.tocantins.governo` |
| Sistema | Red Hat Enterprise Linux 9.8 (Plow), kernel 5.14.0-503.40.1.el9_5 |
| **PHP (PHP-FPM, o que atende as requisições)** | **8.2.31** — binário `/usr/sbin/php-fpm`, master PID 3622405 |
| PHP (CLI) | 8.2.31 — `/bin/php` (NTS). **Não divergem**, boa notícia |
| Origem do pacote | AppStream do RHEL, módulo `php:8.2`, pacote `php-8.2.31-1.module+el9.8.0+24325+74f58d38` |
| Serviços | `php-fpm` e `httpd` ambos *active/enabled* |
| Configuração | `/etc/php.ini`, scan em `/etc/php.d` (`10-opcache.ini`), pool `/etc/php-fpm.d/www.conf` |
| Extensões | 54 carregadas; **todas as 24 exigidas pelo InfoVisa presentes** |
| OPcache / Xdebug | OPcache carregado e habilitado; Xdebug ausente (correto) |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` (corretos) |

**PHP 8.2.31 — NÃO conforme.** A série 8.2 exige 8.2.35; faltam 4 versões de patch.

> A detecção remota não é possível: `https://sistemas.saude.to.gov.br/infovisa` responde atrás de um
> proxy reverso (`Server: openresty`) que não expõe `X-Powered-By`, de modo que a versão do PHP do
> backend não aparece nos cabeçalhos HTTP — o que, do ponto de vista de segurança, é o comportamento
> correto e deve ser mantido. A verificação foi feita por acesso direto ao servidor.

### 1.2 Onde a versão do PHP estava fixada no repositório

Varredura completa feita por nome de arquivo e por conteúdo (`php8`, `php:8`, `php-version`,
`php_version`):

| Local | Antes | Observação |
|---|---|---|
| `composer.json` → `require.php` | `^8.2` | Aceitava **8.2.0**, isto é, todas as versões vulneráveis |
| `composer.lock` → `platform.php` | `^8.2` | Idem |
| `composer.json` → `config.platform` | *ausente* | Não havia pin de plataforma |
| `deploy.sh:43` | `--ignore-platform-reqs` | **Anulava a checagem de versão do PHP no deploy** |
| `fix-production-deploy.sh:30` | `--ignore-platform-reqs` | Idem |
| `Dicas.md:26` | `--ignore-platform-reqs` | Procedimento manual documentado, mesmo efeito |
| `public/.htaccess` | sem pin | Usa `mod_php.c` / `mod_php8.c`, sem versão específica — nada a alterar |
| `phpunit.xml` | sem pin | Nada a alterar |

**Não existem** no projeto: `.github/workflows/`, `Dockerfile`, `docker-compose*.yml`, `.ddev/`,
`.gitlab-ci.yml`, `Procfile` ou qualquer outro pipeline de CI/CD. Portanto não há pin de PHP em
CI ou em contêineres — a versão do PHP é definida exclusivamente pelo servidor e pelo Composer.

### 1.3 Compatibilidade das dependências com as versões corrigidas

`composer.lock` tem 104 pacotes de produção e 38 de desenvolvimento. Dependências que impõem teto de
versão de PHP:

| Pacote | Restrição de PHP | Efeito |
|---|---|---|
| `phpoffice/phpspreadsheet` 1.30.7 | `>=7.4.0 <8.5.0` | **Impede PHP 8.5** |
| `nette/utils` | `8.2 - 8.5` | Compatível até 8.5 |
| `nette/schema` | `8.1 - 8.5` | Compatível até 8.5 |
| `dragonmantank/cron-expression` | `^8.2 \| ^8.3 \| ^8.4 \| ^8.5` | Compatível até 8.5 |
| `dasprid/enum` | `>=7.1 <9.0` | Sem restrição relevante |
| `laravel/framework` v11.48.0 | `^8.2` | Sem teto declarado |

**Conclusão:** das quatro versões admitidas pelo ofício, o InfoVisa pode adotar hoje **8.2.35+,
8.3.36+ ou 8.4.27+**. A opção **8.5.12 está bloqueada** por `phpoffice/phpspreadsheet` 1.30.7
(dependência transitiva de `maatwebsite/excel`, usada na exportação de planilhas) — adotá-la exigiria
antes atualizar essa biblioteca.

### 1.4 Versão alvo escolhida

**PHP 8.3.36** — é a correção da série que o ambiente de desenvolvimento já usa (8.3), com nenhuma
mudança de comportamento entre séries, apenas correções de segurança. A alternativa 8.4.27 é
tecnicamente suportada pelas dependências e pode ser adotada em uma janela posterior.

Atenção: **produção está na série 8.2**, não na 8.3. Isso significa que regularizar o servidor
envolve uma decisão que não é só de número de versão — ver 1.5.

### 1.5 O obstáculo real: o PHP de produção vem do AppStream do RHEL

Produção não usa um PHP compilado nem um repositório de terceiros: usa o **module stream `php:8.2`
do AppStream do Red Hat Enterprise Linux 9**, pacote
`php-8.2.31-1.module+el9.8.0+24325+74f58d38`. Isso tem duas consequências que decidem o caminho da
regularização.

**(a) Red Hat corrige sem mudar o número da versão.** A prática da Red Hat é aplicar as correções de
segurança (*backport*) mantendo a versão upstream do pacote. Em outras palavras, o
`php-8.2.31` do RHEL 9.8 pode **já conter** as correções que o upstream publicou no 8.2.35, e
provavelmente o stream `php:8.2` nunca exibirá o número "8.2.35". Nesse cenário o servidor está
tecnicamente protegido, mas **não atende à letra do ofício**, que cobra o número da versão.

A evidência é o changelog do pacote, que cita os CVEs corrigidos:

```bash
rpm -q --changelog php | grep -oE 'CVE-[0-9]{4}-[0-9]+' | sort -u
dnf -q module list php      # quais séries estão disponíveis (8.2, 8.3, 8.4...)
dnf -q check-update 'php*'  # há atualização pendente no stream atual?
```

O script `verificar-php-servidor.sh` passou a coletar esses três itens (seções 5.2 a 5.4 do
relatório).

**(b) Mudar de série exige trocar o module stream, não um `dnf update`.** Ir de 8.2 para 8.3 ou 8.4
é uma troca de stream:

```bash
sudo dnf module reset php
sudo dnf module enable php:8.3     # se o stream existir no RHEL 9.8
sudo dnf distro-sync
```

### 1.6 Os três caminhos possíveis

| Caminho | O que dá | Custo / risco |
|---|---|---|
| **A. Comprovar o backport** e pedir à ATI o aceite pelo CVE, não pelo número | Mantém o suporte da Red Hat, sem mudança de versão; é a resposta tecnicamente correta para um parque RHEL | Depende do aceite da ATI. **Não** satisfaz a restrição do `composer.json` (ver 2.4) |
| **B. Trocar o stream** para `php:8.3` ou `php:8.4` do próprio RHEL | Mantém o suporte da Red Hat e sobe de série | O stream do RHEL também pode exibir um número abaixo do exigido (ex.: 8.3.x < 8.3.36), recaindo no mesmo impasse do caminho A |
| **C. Repositório Remi** para obter o upstream real (8.3.36+) | Satisfaz a **letra** do ofício | Sai do pacote suportado pela Red Hat; passa a depender de um repositório de terceiros para correções futuras |

**Recomendação:** começar pelo **caminho A**, porque é o único que não altera a base de suporte do
servidor e porque, se os CVEs do Alerta 93/2026 já estiverem corrigidos no pacote instalado, o risco
de segurança real já está tratado — o que resta é formalizar. Rodar os três comandos de 1.5 é o que
decide: se os CVEs do alerta aparecerem no changelog, protocole o caminho A; se não aparecerem, a
regularização é obrigatória e segue para B (preferível) ou C.

### 1.7 Outros achados de produção, decorrentes da mesma verificação

Não fazem parte do ofício, mas foram revelados pela verificação e dois deles afetam o funcionamento
do sistema hoje:

| Achado | Valor em produção | Esperado / observação |
|---|---|---|
| `upload_max_filesize` | **2M** | O InfoVisa espera **64M**. Sob PHP-FPM as diretivas `php_value` do `public/.htaccess` **não se aplicam** — logo, envios acima de 2 MB falham hoje |
| `post_max_size` | **8M** | Esperado 128M |
| `memory_limit` | 128M | Esperado 512M |
| `max_execution_time` | 0 (CLI) | Esperado 300 no pool FPM — confirmar em `www.conf`, pois os valores lidos são os do CLI |
| `expose_php` | **On** | Deve ser `Off`. Hoje o proxy `openresty` mascara o cabeçalho, mas a origem não deveria anunciar a versão |
| Composer | **ausente** no servidor e no `PATH` | `deploy.sh:43` e `fix-production-deploy.sh:30` executam `php composer.phar install` e **falhariam** |
| `php artisan --version` | não respondeu | Investigar: `vendor/` incompleto, permissão ou erro de configuração |

Os três primeiros devem ser corrigidos no `php.ini` / `www.conf` **na mesma janela** da atualização
do PHP (passos 3 e 8 do checklist), já que o serviço será reiniciado de todo modo.

---

## 2. O que foi alterado no repositório

### 2.1 `composer.json` e `composer.lock` — restrição de versão corrigida

```diff
-        "php": "^8.2",
+        "php": ">=8.2.35 <8.3.0 || >=8.3.36 <8.4.0 || >=8.4.27 <8.5.0",
```

A mesma restrição foi propagada para `platform.php` no `composer.lock` (via
`composer update --lock`, que regenera apenas os metadados — a contagem de pacotes permaneceu
104 produção / 38 desenvolvimento, nenhuma dependência foi atualizada de versão).

A restrição é escrita por faixas explícitas, e não como `^8.2.35`, de propósito: `^8.2.35` permitiria
8.3.0, que é vulnerável. A forma adotada aceita **somente** versões de patch já corrigidas de cada
série, e exclui a série 8.5 pelo motivo técnico da seção 1.3. Com isso o próprio Composer passa a
barrar a instalação em um PHP vulnerável.

### 2.2 Deploy deixa de ignorar a versão do PHP

`deploy.sh`, `fix-production-deploy.sh` e `Dicas.md` usavam `--ignore-platform-reqs`, que desliga a
checagem de **todos** os requisitos de plataforma — inclusive a da versão do PHP. Com isso, a
restrição do `composer.json` seria silenciosamente ignorada no deploy e a proteção não teria efeito
prático:

```diff
-php composer.phar install --no-dev --optimize-autoloader --ignore-platform-reqs
+php composer.phar install --no-dev --optimize-autoloader --ignore-platform-req="ext-*"
```

A troca preserva a tolerância a extensões PHP ausentes (motivo original do flag) mas **volta a
exigir a versão do PHP**. Verificado: com PHP 8.3.14, `composer install` passa a falhar com

```
Root composer.json requires php >=8.2.35 <8.3.0 || >=8.3.36 <8.4.0 || >=8.4.27 <8.5.0
but your php version (8.3.14) does not satisfy that requirement.
```

Esse é o comportamento desejado: o deploy passa a ser bloqueado em servidor com PHP vulnerável.

### 2.3 Efeito colateral esperado e intencional

Enquanto o PHP do ambiente não for atualizado, `composer install` e `composer check-platform-reqs`
falharão apontando a versão em uso. **Isso não é uma regressão** — é exatamente a trava de
segurança pedida pelo ofício entrando em vigor. A falha desaparece assim que o PHP for atualizado
para 8.3.36+.

### 2.4 Consequência imediata para o deploy em produção

> **O deploy em produção falhará enquanto o PHP for 8.2.31.** Isto é consequência direta das
> alterações 2.1 e 2.2 e precisa de decisão antes do próximo deploy.

Produção roda 8.2.31, que **não** satisfaz `>=8.2.35`. Como `deploy.sh` deixou de ignorar os
requisitos de plataforma (2.2), o passo de `composer install` será interrompido com o erro de versão.

Isso é o comportamento pretendido — a trava existe justamente para impedir que o sistema seja
implantado sobre um PHP vulnerável — mas implica que **a atualização do PHP passa a ser
pré-requisito do próximo deploy**, e não uma tarefa paralela.

Se for adotado o **caminho A** da seção 1.6 (manter o pacote do RHEL, com os CVEs corrigidos por
backport mas exibindo 8.2.31), a restrição precisa de uma exceção documentada, porque o número da
versão nunca satisfará a regra. Nesse caso, e **somente** se o changelog comprovar que os CVEs do
Alerta 93/2026 estão corrigidos, acrescente ao `composer.json` a versão real da plataforma:

```json
"config": {
    "platform": {
        "php": "8.2.35"
    }
}
```

Isso declara ao Composer a versão a considerar na resolução, sem afrouxar a restrição `require.php`
— que continua documentando o que o ofício exige. A exceção deve ser registrada no chamado da ATI,
com o changelog anexado, e **revertida** quando o servidor migrar para uma versão que satisfaça a
regra pelo número.

---

## 3. Verificações executadas

| Verificação | Comando | Resultado |
|---|---|---|
| Suíte de testes | `php artisan test --testsuite Unit` | **19 testes, 62 asserções — todos passaram** (5,17 s) |
| Requisitos de plataforma (antes da alteração) | `composer check-platform-reqs --no-dev` | **Passou** — todas as 23 linhas `success`; nenhuma extensão faltando |
| Requisitos de plataforma (depois da alteração) | `composer check-platform-reqs --no-dev` | **Falha esperada** na linha `php`: 8.3.14 não satisfaz `>=8.3.36` |
| Integridade do lock | `composer update --lock` | Sem mudança de versões de pacote (104/38 mantidos) |

Observação sobre a suíte: este projeto não possui o diretório `tests/Feature`, então o parâmetro
`--testsuite Unit` é obrigatório — sem ele o PHPUnit falha ao resolver as suítes.

Todas as extensões exigidas pelas dependências estão presentes no ambiente (`ext-gd`, `ext-zip`,
`ext-dom`, `ext-openssl`, `ext-iconv`, `ext-fileinfo`, `ext-simplexml`, `ext-xmlreader`,
`ext-xmlwriter`, `ext-zlib`, entre outras) — elas precisam continuar habilitadas após a atualização
do PHP (ver checklist).

---

## 4. Checklist de atualização do servidor (equipe de infraestrutura)

> Fora do escopo do repositório. A atualização do binário do PHP no servidor é atividade de
> infraestrutura e **não** é resolvida por nenhuma alteração de código.

**Antes**

1. Registrar a versão atual para a resposta à ATI. O script
   [`verificar-php-servidor.sh`](verificar-php-servidor.sh) deste mesmo diretório faz os passos 1, 2
   e 3 de uma vez e gera o relatório a anexar ao chamado:

   ```bash
   bash verificar-php-servidor.sh                      # usa /var/www/html/infovisa
   bash verificar-php-servidor.sh /outro/caminho       # se a aplicação estiver em outro lugar
   ```

   Ele **não altera nada** — só lê versões, diretivas e extensões — e pode rodar com o sistema no
   ar. Rode com `sudo` para que ele consiga identificar o binário do PHP-FPM em execução.
   Manualmente, o equivalente é `php -v` e `php-fpm -v`.

   > **Atenção:** o que vale para o ofício é a versão do **PHP-FPM**, não a do CLI. Quando há mais de
   > uma instalação de PHP no servidor as duas divergem, e é o FPM que atende as requisições. O
   > script resolve isso lendo o binário do processo em execução
   > (`readlink /proc/<pid-do-master>/exe`) em vez de confiar no `php` do `PATH`.

2. Listar as extensões hoje habilitadas: `php -m > /tmp/php-modules-antes.txt`
   (o script já grava esse arquivo e confere as extensões exigidas pelo InfoVisa uma a uma).
3. Corrigir as diretivas de upload no `php.ini` / `/etc/php-fpm.d/www.conf`. Produção está hoje com
   `upload_max_filesize=2M` e `post_max_size=8M`, e o InfoVisa espera
   `upload_max_filesize=64M`, `post_max_size=128M`, `max_execution_time=300`,
   `max_input_time=300`, `memory_limit=512M`. Sob PHP-FPM as diretivas `php_value` do
   `public/.htaccess` **não se aplicam** — ver seção 1.7.
4. Backup do banco PostgreSQL e de `/var/www/html/infovisa/.env`.
5. Executar em homologação antes da produção.

**Atualização**

6. **Decidir o caminho antes de executar** — ver seções 1.5 e 1.6. Produção está na série 8.2
   (8.2.31), servida pelo module stream `php:8.2` do AppStream do RHEL 9, e a Red Hat corrige por
   backport sem mudar o número da versão. Rode primeiro os três comandos de 1.5
   (`rpm -q --changelog php`, `dnf -q module list php`, `dnf -q check-update 'php*'`):
   - CVEs do Alerta 93/2026 já no changelog → **caminho A**: nenhuma atualização de versão é
     necessária, protocole a comprovação na ATI e aplique a exceção da seção 2.4;
   - não estão → **caminho B** (trocar o stream para `php:8.3`/`php:8.4` do próprio RHEL,
     preferível) ou **caminho C** (repositório Remi, para obter 8.3.36 do upstream).

   Em B e C, não adotar a série 8.5 — ver seção 1.3.
7. Reinstalar/confirmar as extensões do passo 2, em especial `gd`, `zip`, `dom`, `openssl`,
   `iconv`, `fileinfo`, `simplexml`, `xmlreader`, `xmlwriter`, `zlib`, `mbstring`, `pdo_pgsql`.
8. Reaplicar as diretivas do passo 3 no `php.ini` do PHP-FPM.
9. Reiniciar os serviços: `sudo systemctl restart php-fpm httpd`.

**Depois**

10. Confirmar a versão com `php -v` **e** `php-fpm -v`. Nos caminhos B e C deve exibir uma versão
    corrigida (8.3.36+ ou 8.4.27+); no caminho A continuará em 8.2.31, e a comprovação é o
    changelog, não o número.
11. Rodar `bash verificar-php-servidor.sh` novamente. Nos caminhos B e C a conclusão deve apontar
    **nenhuma não conformidade**; esse segundo relatório é a comprovação da regularização para o
    chamado da ATI. No caminho A o script continuará acusando a versão — é esperado, e o que se
    anexa ao chamado é a seção 5.4 do relatório (CVEs corrigidos por backport).
12. `composer install --no-dev --optimize-autoloader --ignore-platform-req="ext-*"` — deve concluir
    **sem** erro de versão de PHP. Se acusar a versão: nos caminhos B e C a atualização não surtiu
    efeito no binário usado pelo Composer; no caminho A falta aplicar a exceção da seção 2.4.
    Lembre que o Composer **não está instalado** no servidor hoje (seção 1.7) — `deploy.sh` espera
    um `composer.phar` no diretório da aplicação.
13. `php artisan optimize:clear` e, em seguida, `php artisan config:cache`.
14. Validar os fluxos que dependem das extensões atualizadas: geração de PDF de receituário
    (dompdf/fpdi), leitura de PDF (`smalot/pdfparser`), exportação de planilhas
    (`maatwebsite/excel`/phpspreadsheet), QR Code (`endroid/qr-code`) e upload de imagens
    (`intervention/image`).
15. Confirmar que a versão do PHP exposta em cabeçalhos HTTP está suprimida (`expose_php = Off`).
16. Registrar a conclusão no chamado da ATI e anexar o relatório do script e a saída de `php -v`.

---

## 5. Justificativa institucional de acesso externo (item 7 do ofício)

O ofício restringe o acesso externo de aplicações PHP até a regularização e exige justificativa
formal para a manutenção desse acesso. A justificativa do InfoVisa é a seguinte.

**O InfoVisa é o sistema estadual de vigilância sanitária do Tocantins e depende, por natureza, de
acesso público pela Internet.** Ele não é um sistema de uso interno da administração: parte
essencial de seus usuários é **externa ao Estado**, e o acesso pela Internet é o próprio meio de
prestação do serviço público. Em particular:

- **Estabelecimentos regulados** (empresas sujeitas à vigilância sanitária) acessam a área
  `/company` do sistema para solicitar licenciamento sanitário, acompanhar o andamento de seus
  processos, enviar documentação e obter seus documentos emitidos. São pessoas jurídicas privadas,
  sem vínculo com a rede do Estado e sem possibilidade de acesso por VPN corporativa.
- **Profissionais externos** utilizam o sistema para emissão e validação de receituários, atividade
  com prazo próprio e demanda contínua.
- **Municípios e órgãos parceiros** acessam o sistema no exercício de competências de vigilância
  sanitária descentralizadas.

A restrição de acesso externo, portanto, equivaleria à **interrupção de um serviço público de
vigilância sanitária**, com impacto direto sobre o licenciamento de estabelecimentos, sobre prazos
regulatórios e sobre a emissão de receituários — e não apenas sobre a produtividade interna de uma
equipe.

**Inviabilidade de VPN como alternativa:** a exigência de VPN para acesso ao InfoVisa não é
aplicável ao público externo. Não é possível fornecer e gerir credenciais de VPN do Estado para o
universo aberto de estabelecimentos regulados e profissionais de saúde do Tocantins, que se
renova continuamente a cada novo licenciamento. A VPN pode ser adotada, se a ATI entender
necessário, apenas para os **acessos administrativos internos** (servidores da vigilância
sanitária), preservando-se o acesso público às áreas destinadas a estabelecimentos e profissionais.

**Medidas compensatórias já em curso:** as restrições de versão de PHP descritas na seção 2 foram
aplicadas ao repositório, de modo que o sistema não possa ser implantado sobre uma versão
vulnerável do PHP; a atualização para 8.3.36 está encaminhada junto à equipe de infraestrutura
conforme o checklist da seção 4.

**Encaminhamento formal:** esta justificativa deve ser protocolada junto à ATI pelo portal de
chamados **https://chamados.ati.to.gov.br**, referenciando o SGD **2026/26819/009610**, solicitando:

1. a manutenção do acesso externo ao InfoVisa pelo caráter de serviço público prestado a usuários
   externos ao Estado;
2. a dispensa de exigência de VPN para o público externo (estabelecimentos e profissionais), com
   eventual aplicação restrita aos acessos administrativos internos;
3. o registro do prazo de conclusão da atualização do PHP para 8.3.36 no servidor de produção.

---

## 6. Atualização das dependências com avisos de segurança

O levantamento feito para este ofício revelou, por meio de `composer audit`, **62 avisos de segurança
afetando 15 pacotes de produção**, incluindo severidade crítica. Isso **não** faz parte do OFÍCIO
Nº 12/2026, que trata especificamente da versão do PHP, mas foi tratado em trabalho separado logo
depois, em 29/10/2026. Esta seção registra o resultado.

### 6.1 Resultado

| Momento | Avisos (produção) | Pacotes afetados |
|---|---|---|
| Antes | 62 | 15 |
| Depois | **5** | **1** (`laravel/framework`) |

Verificação: `composer audit --no-dev`.

### 6.2 O que foi atualizado

Todas as correções couberam **dentro das restrições já declaradas no `composer.json`** — nenhuma
restrição de versão precisou ser elevada ou afrouxada, e nenhuma migração de major de biblioteca foi
necessária.

| Pacote | Antes | Depois | Avisos zerados |
|---|---|---|---|
| `league/commonmark` | 2.8.0 | 2.10.4 | 14 (9 high, 5 medium) |
| `phpoffice/phpspreadsheet` | 1.30.2 | 1.30.7 | 12 (2 **critical**, 7 high, 3 medium) |
| `guzzlehttp/guzzle` | 7.10.0 | 7.16.1 | 9 (1 high, 8 medium) |
| `dompdf/dompdf` | v3.1.4 | v3.1.6 | 6 (4 medium, 2 low) |
| `guzzlehttp/psr7` | 2.8.0 | 2.13.2 | 4 medium |
| `symfony/http-foundation` | v7.4.5 | v7.4.22 | 2 (1 high, 1 medium) |
| `symfony/mime` | v7.4.5 | v7.4.22 | 2 (1 high, 1 medium) |
| `symfony/routing` | v7.4.4 | v7.4.22 | 2 medium |
| `maatwebsite/excel` | 3.1.67 | 3.1.70 | 1 high |
| `symfony/http-kernel` | v7.4.5 | v7.4.22 | 1 high |
| `symfony/mailer` | v7.4.4 | v7.4.22 | 1 medium |
| `setasign/fpdi` | v2.6.4 | v2.6.9 | 1 medium |
| `league/flysystem` | 3.31.0 | 3.36.0 | 1 low |
| `symfony/polyfill-intl-idn` | v1.33.0 | v1.43.0 | 1 low |

São **57 avisos zerados** em 14 pacotes. No total, 46 entradas do `composer.lock` mudaram, contando as
dependências transitivas arrastadas que não tinham aviso (`symfony/console`, `symfony/string`,
`nette/*`, `masterminds/html5`, `sabberworm/php-css-parser`, polyfills, entre outras).

Pontos que merecem registro:

- **`phpoffice/phpspreadsheet` não exigiu troca de série.** A versão 1.30.7 zera todos os 12 avisos,
  inclusive os 2 críticos (CVE-2026-45034 e CVE-2026-34084). A migração para 2.x/3.x/5.x — que muda a
  API da biblioteca e era o risco previsto — **não foi necessária**.
- **`thecodingmachine/safe` v3.3.0 saiu do lock.** Era dependência do `maatwebsite/excel` 3.1.67 e
  deixou de ser exigida na 3.1.70. Não há nenhuma chamada a `Safe\` no código do InfoVisa.
- **`maennchen/zipstream-php` recuou de 3.2.1 para 3.1.2**, por exigência de versão de PHP — ver 6.4.
- Em desenvolvimento, `symfony/yaml` foi de v7.4.1 para v7.4.22, zerando outros 4 avisos que vinham
  pelo `laravel/sail`. Com isso o `composer audit` completo (produção + desenvolvimento) também fica
  em 5 avisos.

### 6.3 Pendência: `laravel/framework` (5 avisos, 1 crítico)

Os 5 avisos restantes são todos do `laravel/framework` e **não têm correção dentro da série 11**. A
versão mais recente da série é a v11.57.1 e continua afetada por todos os cinco:

| Aviso | Severidade | Corrigido em | Aplica-se ao InfoVisa? |
|---|---|---|---|
| CVE-2026-114509 — bypass de autenticação no `TokenGuard` com credencial de API não-string | **crítica** | >= 12.68.0 / >= 13.29.0 | **Não.** Os três guards de `config/auth.php` (`web`, `externo`, `interno`) usam driver `session`; nenhum usa driver `token` |
| PKSA-3r5d-mb8f-1qw9 e CVE-2026-48019 — injeção de CRLF na regra de validação `email` | high | >= 12.60.0 / >= 13.10.0 | **Sim.** A regra `email` é usada em cadastro de usuário interno, recuperação de senha e cadastro de estabelecimento |
| PKSA-m5cs-t1y6-qpcs — confusão de caminho em URL assinada temporária | medium | >= 12.61.1 / >= 13.12.0 | **Não.** O sistema não usa `signedRoute`, `temporarySignedRoute` nem o middleware `signed` |
| CVE-2026-102279 — XSS na página de debug | low | >= 12.69.0 / >= 13.30.0 | **Só com `APP_DEBUG=true`.** O padrão de `config/app.php` é `false` |

Eliminar esses avisos exige **migração de major do framework (Laravel 11 → 12 ou 13)**. Isso muda
comportamento da aplicação, não é atualização de dependência e precisa de projeto próprio, com janela
e testes. Fica registrado como pendência.

Mitigações aplicáveis enquanto a migração não ocorrer:

- manter `APP_DEBUG=false` em produção (cobre o aviso low);
- não introduzir guard com driver `token` — para API, usar o Sanctum, já instalado (mantém o crítico
  fora de alcance);
- quanto ao CRLF na regra `email`: o caminho de envio de mensagem já está coberto, porque
  `symfony/mime` v7.4.22 — atualizado neste ciclo — passou a rejeitar CRLF em
  `Symfony\Component\Mime\Address` (CVE-2026-45067). O risco residual é o armazenamento de um endereço
  malformado, não a injeção de cabeçalho no envio.

### 6.4 Trava de resolução do Composer (`config.platform`)

O `composer.json` fixa o alvo de resolução de dependências:

```json
"config": {
    "platform": {
        "php": "8.2.35"
    }
}
```

O motivo é que o PHP de desenvolvimento (8.3.14) é **mais novo** que o de produção (8.2.31, que será
regularizado para 8.2.35 — seções 1.1 e 1.5). Sem essa trava, resolver dependências na máquina de
desenvolvimento faz o Composer escolher pacotes que exigem PHP mais novo do que o do servidor. Isso
aconteceu de fato durante este trabalho: na primeira tentativa, feita com `--ignore-platform-req=php`
(necessário enquanto o PHP local não atende à restrição da seção 2.1), a resolução trouxe
`symfony/string` 8.1.10 e `symfony/event-dispatcher` 8.1.5, que exigem **PHP >= 8.4.1** e quebrariam o
deploy. Com a trava em 8.2.35, os dois ficaram em v7.4.22 e v7.4.17.

Duas consequências registradas:

- `maennchen/zipstream-php` 3.2.x exige `php-64bit ^8.3`; por isso o lock usa a **3.1.2**
  (`php-64bit ^8.2`) em vez da 3.2.1 anterior. A 3.1.2 não tem avisos de segurança e a escrita e
  leitura de `.xlsx` foram validadas nessa versão (6.5). Note-se que o lock anterior já exigia
  PHP 8.3 por causa desse pacote, o que era incompatível com o servidor em 8.2.
- Quando produção subir para a série 8.3 ou 8.4, basta elevar a trava para a versão correspondente e
  reexecutar a resolução, para voltar a receber os pacotes que exigem PHP mais novo.

A trava **não** mascara a checagem do ofício: `composer check-platform-reqs` continua acusando falha
apenas na linha do `php` enquanto o PHP local for 8.3.14, como descrito em 2.3.

### 6.5 Validação executada

- `composer audit --no-dev`: 62 → 5 avisos, 15 → 1 pacote.
- Compatibilidade do lock inteiro (produção e desenvolvimento) verificada contra PHP 8.2.35:
  **nenhum pacote incompatível**.
- `php artisan test --testsuite Unit`: **19 testes, 62 asserções, todos passando**.
- `php artisan route:list`: 656 rotas carregadas. `config:cache` e `view:cache` concluídos sem erro —
  exercita `symfony/routing`, `symfony/http-foundation` e a compilação de todas as views Blade.

A suíte automatizada não cobre nenhuma das bibliotecas atualizadas, então os fluxos afetados foram
exercitados por script, reproduzindo as chamadas reais do sistema. **15 verificações, todas OK:**

| Fluxo do sistema | Biblioteca | O que foi exercitado |
|---|---|---|
| Geração de PDF (receituário, documento emitido) | `dompdf/dompdf` v3.1.6 | `Pdf::loadHTML` + `setPaper('a4','portrait')` + `setOption` + `output()`, PDF de 2 páginas |
| Carimbo de validação sobre o PDF | `setasign/fpdi` v2.6.9 | `setSourceFile`, `importPage`, `getTemplateSize`, `AddPage`, `useTemplate`, `Rect`, `Line`, `Image`, `Cell`, `Output('F')`, como em `CarimboValidacaoService` |
| QR Code de validação | `endroid/qr-code` 6.0.9 | `QrCode` + `PngWriter->getString()`, PNG embutido no carimbo |
| Leitura de PDF | `smalot/pdfparser` v2.12.3 | `parseFile()->getText()` no PDF do dompdf e no PDF carimbado |
| Exportação de planilha | `phpoffice/phpspreadsheet` 1.30.7 | escrita de `.xlsx` e releitura do conteúdo (round-trip) |
| Leitura de `.pptx` (treinamentos) | `phpoffice/phppresentation` 1.2.0 | escrita e `IOFactory::load()` |
| Integrações externas (CNPJ, WhatsApp, IA) | `guzzlehttp/guzzle` 7.16.1, `guzzlehttp/psr7` 2.13.2 | cliente Guzzle, `Request`/`Uri` PSR-7, `Http::` com JSON e com multipart |
| Markdown de e-mail | `league/commonmark` 2.10.4 | `Str::markdown` |
| Upload e processamento de imagem | `intervention/image` 3.11.6 | leitura de PNG e redimensionamento, via fachada e via `ImageManager` |
| Armazenamento de arquivos | `league/flysystem` 3.36.0 | `Storage::put`, `get` e `delete` |

Ressalva honesta: as chamadas de rede reais (API de CNPJ, WhatsApp, provedor de IA) **não** foram
exercitadas contra os serviços externos — foram validadas a montagem da requisição e o tratamento da
resposta, não a integração ponta a ponta.

### 6.6 PHP 8.5 continua bloqueado — e o motivo pode ser removido

`phpoffice/phpspreadsheet` 1.30.7 mantém a restrição `>=7.4.0 <8.5.0`. A conclusão da seção 1.3,
portanto, não muda: das versões admitidas pelo ofício, o InfoVisa pode adotar **8.2.35+, 8.3.36+ ou
8.4.27+**, e **8.5.12 segue inviável**.

Achado relevante para quem for tratar isso: a fachada `Maatwebsite\Excel\Facades\Excel` é importada em
`app/Http/Controllers/Admin/RelatorioController.php` mas **nunca é chamada**, e não há nenhuma
referência a `PhpSpreadsheet` no código do InfoVisa. Todas as exportações do sistema são CSV, geradas
com `fputcsv` e `response()->streamDownload`. Ou seja: o teto de PHP 8.5 vem de uma dependência que a
aplicação não exercita. Há dois caminhos, ambos decisão de projeto e nenhum deles tomado aqui:

1. migrar para `maatwebsite/excel` 4.x, que usa `phpspreadsheet` 5.x e não tem o teto de versão; ou
2. **remover `maatwebsite/excel` do `composer.json`**, que elimina de uma vez o teto de PHP 8.5 e a
   superfície de segurança desses dois pacotes — foram 13 dos 62 avisos deste ciclo, 2 deles críticos.

---

## 7. Resumo para a resposta à ATI

| Item | Situação |
|---|---|
| **Versão do PHP em produção** | **8.2.31** (PHP-FPM, `sses482001.tocantins.governo`, RHEL 9.8) — **não conforme**: a série 8.2 exige 8.2.35 |
| Versão do PHP em desenvolvimento | 8.3.14 — não conforme (exige 8.3.36) |
| Origem do pacote em produção | AppStream do RHEL 9, module stream `php:8.2` |
| Obstáculo principal | A Red Hat corrige por *backport* sem mudar o número da versão: o stream `php:8.2` provavelmente nunca exibirá 8.2.35. Ver seções 1.5 e 1.6 |
| Decisão pendente | Caminho A (comprovar backport e pedir aceite por CVE), B (trocar o stream do RHEL) ou C (repositório Remi). Recomendado: **A** |
| PHP 8.5.12 | Inviável — bloqueado por `phpoffice/phpspreadsheet` 1.30.7 (ver 6.6, que mostra como o bloqueio pode ser removido) |
| Ação no código versionado | **Concluída** — restrição de versão corrigida no Composer e checagem de versão reativada no deploy |
| Ação no servidor | **Pendente** — checklist da seção 4 |
| **Risco imediato** | **O próximo deploy falhará** enquanto o PHP for 8.2.31 (seção 2.4) |
| Extensões em produção | Todas as 24 exigidas estão presentes — a atualização não depende de instalar extensão nova |
| Testes após a alteração | 19 testes, 62 asserções, todos passando |
| Avisos de segurança das dependências | **62 → 5** (de 15 pacotes para 1). Os 5 restantes são do `laravel/framework` e só têm correção no Laravel 12/13 — ver seção 6 |
| Acesso externo | Manutenção justificada (seção 5); formalizar via chamados.ati.to.gov.br |
