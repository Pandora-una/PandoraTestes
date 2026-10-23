# Testes do pandora-testes

Suíte de caracterização do que a suíte Behat dos sistemas usa desta lib:

- `MatcherTest`: os passos JSON do `PandoraSrvContext` ("at least these fields", usado em 571
  cenários do area-restrita, "this format" e "not contain") sobre uma matriz de padrões do
  coduo/php-matcher × valores, estruturas (chave a mais, listas, aninhados) e o `panToday`;
- `WebApiContextTest`: o `WebApiContext` (behat/web-api-extension) contra um servidor eco
  (`tests/_servidor/eco.php`): o cliente que a extensão monta do `base_url`, os passos de
  requisição e de resposta, e as propriedades que o `FeatureContext` do area-restrita lê por
  reflection (`headers`, `request`, `response`, `sendRequest`);
- `FixtureBuilderTest`: fixtures (params, traits, `campo:valor`, associações, id atribuído),
  limpeza, erros e a detecção de PostgreSQL do `clean()`;
- `PandoraContextTest`: os passos de fixture, os ganchos de limpeza, `print pretty response` e a
  factory do `FixtureBuilder`.

Os instantâneos foram **gravados na pilha de produção da 2.x**: o `vendor` do area-restrita
(Behat 3.7, web-api-extension dev-master `ff62c552`, Guzzle 6.5, coduo/php-matcher 2.0.1,
data-fixtures 1.3, ORM 2.5, PHPUnit 5.7), PHP 7.2, com o código deste repositório no lugar do
instalado lá. A 3.x (PHP 8.4) tem de reproduzir os mesmos arquivos.

Cuidados do harness:
- padrões e valores do matcher em **JSON literal**: o `json_encode` de float muda com o
  `serialize_precision` de cada PHP;
- dos cabeçalhos, vale o objeto PSR-7 que o Guzzle envia: o `php -S` junta ou descarta cabeçalho
  repetido conforme a versão;
- id atribuído é testado como primeiro insert: no SQLite (IDENTITY) o Doctrine guarda o SQL do
  insert sem a coluna id; nos sistemas (PostgreSQL, SEQUENCE) não;
- linhas do banco comparadas como texto (o PDO SQLite do PHP 8.1+ devolve inteiros).

## Rodar

```bash
# 3.x: vendor deste repositório
make test

# 2.x: vendor e imagem do area-restrita (PHPUnit 5.7 de lá), num checkout com o código 2.x
# (o commit da caracterização)
git worktree add /tmp/ptestes-2x <commit da caracterização>
/tmp/ptestes-2x/ci/teste-2x.sh ~/sistemas/area-restrita/srv

# regravar os instantâneos (só na 2.x, e só de propósito)
PTESTES_GRAVAR=1 /tmp/ptestes-2x/ci/teste-2x.sh ~/sistemas/area-restrita/srv
```
