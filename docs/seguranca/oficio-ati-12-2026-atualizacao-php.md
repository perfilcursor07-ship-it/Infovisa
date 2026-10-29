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

> **Pendência de infraestrutura:** a versão do PHP do **servidor de produção** não é determinável a
> partir do repositório. Ela precisa ser confirmada pela equipe de infraestrutura com
> `php -v` e `php-fpm -v` no servidor (ver checklist na seção 4) e registrada aqui antes do envio da
> resposta formal à ATI.
>
> A detecção remota também não é possível: `https://sistemas.saude.to.gov.br/infovisa` responde
> atrás de um proxy reverso (`Server: openresty`) que não expõe `X-Powered-By`, de modo que a versão
> do PHP do backend não aparece nos cabeçalhos HTTP — o que, do ponto de vista de segurança, é o
> comportamento correto e deve ser mantido. **A verificação tem de ser feita por acesso ao servidor.**

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
| `phpoffice/phpspreadsheet` 1.30.2 | `>=7.4.0 <8.5.0` | **Impede PHP 8.5** |
| `nette/utils` | `8.2 - 8.5` | Compatível até 8.5 |
| `nette/schema` | `8.1 - 8.5` | Compatível até 8.5 |
| `dragonmantank/cron-expression` | `^8.2 \| ^8.3 \| ^8.4 \| ^8.5` | Compatível até 8.5 |
| `dasprid/enum` | `>=7.1 <9.0` | Sem restrição relevante |
| `laravel/framework` v11.48.0 | `^8.2` | Sem teto declarado |

**Conclusão:** das quatro versões admitidas pelo ofício, o InfoVisa pode adotar hoje **8.2.35+,
8.3.36+ ou 8.4.27+**. A opção **8.5.12 está bloqueada** por `phpoffice/phpspreadsheet` 1.30.2
(dependência transitiva de `maatwebsite/excel`, usada na exportação de planilhas) — adotá-la exigiria
antes atualizar essa biblioteca.

### 1.4 Versão alvo escolhida

**PHP 8.3.36** — é a correção da própria série já usada pelo projeto (8.3), o que mantém o risco de
regressão no mínimo: nenhuma mudança de comportamento entre séries, apenas correções de segurança
dentro da 8.3. A alternativa 8.4.27 é tecnicamente suportada pelas dependências e pode ser adotada
em uma janela posterior, planejada à parte.

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
3. Preservar as configurações de upload atualmente em uso pelo InfoVisa (hoje declaradas em
   `public/.htaccess`, mas que em PHP-FPM precisam estar no `php.ini`):
   `upload_max_filesize=64M`, `post_max_size=128M`, `max_execution_time=300`,
   `max_input_time=300`, `memory_limit=512M`.
4. Backup do banco PostgreSQL e de `/var/www/html/infovisa/.env`.
5. Executar em homologação antes da produção.

**Atualização**

6. Atualizar o PHP para **8.3.36** (ou superior da série 8.3), mantendo a série para evitar
   regressão. Não adotar a série 8.5 — ver seção 1.3.
7. Reinstalar/confirmar as extensões do passo 2, em especial `gd`, `zip`, `dom`, `openssl`,
   `iconv`, `fileinfo`, `simplexml`, `xmlreader`, `xmlwriter`, `zlib`, `mbstring`, `pdo_pgsql`.
8. Reaplicar as diretivas do passo 3 no `php.ini` do PHP-FPM.
9. Reiniciar os serviços: `sudo systemctl restart php-fpm httpd`.

**Depois**

10. Confirmar a versão: `php -v` deve mostrar 8.3.36 ou superior.
11. Rodar `bash verificar-php-servidor.sh` novamente: a conclusão deve apontar
    **nenhuma não conformidade**. Esse segundo relatório é a comprovação da regularização para o
    chamado da ATI.
12. `composer install --no-dev --optimize-autoloader --ignore-platform-req="ext-*"` — agora deve
    concluir **sem** erro de versão de PHP. Se acusar a versão, a atualização não surtiu efeito no
    binário usado pelo Composer.
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

## 6. Pendência relacionada, fora do escopo deste ofício

O levantamento acima revelou, por meio de `composer audit`, **62 avisos de segurança afetando 15
pacotes de produção** do `composer.lock`, incluindo severidade crítica:

| Pacote | Avisos | Severidades |
|---|---|---|
| `league/commonmark` | 14 | 9 high, 5 medium |
| `phpoffice/phpspreadsheet` | 12 | 2 **critical**, 7 high, 3 medium |
| `guzzlehttp/guzzle` | 9 | 1 high, 8 medium |
| `dompdf/dompdf` | 6 | 4 medium, 2 low |
| `laravel/framework` | 5 | 1 **critical**, 1 high, 1 medium, 1 low |
| `guzzlehttp/psr7` | 4 | 4 medium |
| `symfony/http-foundation` | 2 | 1 high, 1 medium |
| `symfony/mime` | 2 | 1 high, 1 medium |
| `symfony/routing` | 2 | 2 medium |
| `maatwebsite/excel` | 1 | 1 high |
| `symfony/http-kernel` | 1 | 1 high |
| `symfony/mailer` | 1 | 1 medium |
| `setasign/fpdi` | 1 | 1 medium |
| `league/flysystem` | 1 | 1 low |
| `symfony/polyfill-intl-idn` | 1 | 1 low |

Isso **não** faz parte do OFÍCIO Nº 12/2026, que trata especificamente da versão do PHP, e
**nenhuma dessas dependências foi atualizada** neste trabalho — uma atualização de dependências
altera comportamento da aplicação e exige janela e testes próprios. Fica registrado aqui como
pendência de segurança a ser tratada em trabalho separado, com atenção especial a
`phpoffice/phpspreadsheet` (cuja atualização também é o que destravaria a adoção do PHP 8.5) e a
`laravel/framework`.

---

## 7. Resumo para a resposta à ATI

| Item | Situação |
|---|---|
| Versão do PHP identificada (desenvolvimento) | 8.3.14 — não conforme |
| Versão do PHP em produção | **a confirmar pela infraestrutura** (passo 1 da seção 4) |
| Versão alvo | 8.3.36 (série 8.3, mínimo risco de regressão) |
| PHP 8.5.12 | Inviável hoje — bloqueado por `phpoffice/phpspreadsheet` 1.30.2 |
| Ação no código versionado | **Concluída** — restrição de versão corrigida no Composer e checagem de versão reativada no deploy |
| Ação no servidor | **Pendente** — checklist da seção 4, responsabilidade da infraestrutura |
| Testes após a alteração | 19 testes, 62 asserções, todos passando |
| Acesso externo | Manutenção justificada (seção 5); formalizar via chamados.ati.to.gov.br |
