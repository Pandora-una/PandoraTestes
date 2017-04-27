# pandora-testes

Este repositório contém ferramentas para facilitar a criação de testes de aceitação usando [Behat](https://github.com/Behat/Behat) com [MinkExtension](https://github.com/Behat/MinkExtension) e [WebApiExtension](https://github.com/Behat/WebApiExtension) em aplicações feitas em Zend Framework 2 e Doctrine 2\. As ferramentas são:

*   Interface simples para a criação de _Docrine fixtures_.
*   Passos para a criação de entidades na base de dados.
*   Passos para espera de requisições _Ajax_.
*   Banco de dados de teste limpo entre a execução dos testes.
*   Script _shell_ para alterar o enviroment do _Apache2_ para _test_ durante a execução do teste
*   Binário com _Selenium2_

### Instalação

A forma recomendada de instalação é por [composer](https://getcomposer.org/):
```
    {
        "require-dev": {
            "behat/behat": "3.0.*",
            "pandora-una/pandora-testes": "dev-master"
        },
    }
```

### Binários

_Behat_ já possui um binário para rodar os testes, porém o Zend Framework 2 não dá suporte para a separação entre um ambiente de teste e um de desenvolvimento separados de forma simples. Portanto foi necessário criar um script para facilitar a execução dos testes e para evitar erros. É necessário apenas o seguinte comando para rodar os testes:
```sh
$ vendor/bin/pandora-behat
```
O script aceita as mesmas opções que o binário do behat, o seguinte comando lista as opções:
```sh
$ vendor/bin/pandora-behat --help
```

Caso os testes necessitem de Selenium2 rodando em background, o seguinte comando tem a função de inicializá-lo:
```sh
$ java -jar vendor/bin/selenium-server-standalone-2.53.0.jar
```

### Configurações Opcionais

Abaixo temos um exemplo de uma configuração mais completa:

```php
'pandora-testes' => array(
    'fixtures_namespace' => 'Application\Fixture',
    'entities_namespace' => 'Application\Entity',
    'clean-after-suite' => false,
    'fixtures' => array(
        'base' => array('usuarioWeb'),
        'Usuario' => array(
            'identifier' => 'id',
            'entity_name' => 'SASLoginExterno\Entity\Usuario'
        )
    )
)
```

Os campos acima tem os seguintes efeitos:

- **fixtures_namespace**: O namespace das fixtures, caso omitido o padrão é *Application\Fixture*.
- **entities_namespace**: O namespace padrão das entidades, caso omitido o padrão é *Application\Entity*. É importante notar que é possível especificar um namespace para cada entidade dentro da opção *fixtures*.
- **fixtures**
    - **Nome de uma entidade**:
        - **identifier**: Campo que identifica a entidade, caso omitido o padrão é *id*.
        - **entity_name**: Nome completo da entidade, caso omitido o padrão é o namespace definido em *entities_namespace* junto com o nome simples da entidade.
    - **base**: Lista que contém as entidades que serão carregadas sempre que rodar os testes.

### Doctrine Fixtures

Uma importante função deste repositório é facilitar a criação de entidades na base de teste de forma simples e dinâmica através de fixtures como essa:
```php
namespace Application\Fixture;

use PandoraTestes\Fixture\AbstractFixture;

class Usuario extends AbstractFixture
{

    protected $params = array(
        'email' => 'usuario@email.com',
        'senha' => 'e8d95a51f3af4a3b134bf6bb680a213a'
    );

}
```
##### Parâmetros

Como pode ser visto no exemplo anterior, uma fixture deve difinir seus atributos básicos. Os atributos com valores simples, ou seja, que não fazem referência à outras entidades devem ser informados no atributo _params_. Este campo deve conter um hash no qual a chave indica o nome do atributo e o valor indica o valor do atributo.

Para o exemplo da nossa fixture de usuário, as chaves do array são pensadas de forma a “casar” com os métodos de acesso do Doctrine, ou seja, durante a criação da entidade a biblioteca executará os seguintes comandos para a criação de um usuário:
```php
$entityUsuario->setEmail('usuario@email.com');
$entityUsuario->setSenha('e8d95a51f3af4a3b134bf6bb680a213a');
```

Por isso, é importante que as chaves sejam as mesmas que os campos da entidade e que os métodos de acesso existam.

Algumas vezes o valor de um atributo não é tão simples quanto apenas um número ou uma string e é necessário fazer algum tratamento para gerar o valor (valores temporais são os mais comuns nessa categoria). Para isso é necessário definir um callback da seguinte forma:
```php
class Usuario extends AbstractFixture
{

    protected $params = array(
        'email' => 'usuario@email.com',
        'senha' => array('callback' => 'md5', 'value' => '123456'),
    );

}
```
Assim a factory da fixture vai chamar a função definida na chave _callback_ com o parâmetro definido na chave _value_. o callback aceita tudo que a função nativa "call\_user\_func" aceita no seu primeiro parâmetro.

##### Associações

Quando uma fixture faz referência a outra entidade, é possível fazer referência a uma outra fixture. Assumindo que exista uma fixture como essa:
```php
class TipoUsuario extends AbstractFixture
{
    protected $params = array(
        'descricao' => 'OPERADOR',
    );
}
```

Para criar a associação é preciso instanciar um novo campo de nossa fixture como neste exemplo:
```php
class Usuario extends AbstractFixture
{
    protected $params = array(
        'email' => 'usuario@email.com',
        'senha' => 'e8d95a51f3af4a3b134bf6bb680a213a'
    );

    protected $associations = array(
        'tipo' => 'tipoUsuario'
    );
}
```

A chave do array faz referência ao campo da entidade do doctrine (Neste caso o método _setTipo()_ será invocado) e o valor faz referência ao nome simples da classe da fixture, mas com a primeira letra minúscula. então “tipoUsuario” é o nome da fixture cuja classe é “Application/Fixture/TipoUsuario”. Obviamente é possível fazer inúmeras associações

##### Aspectos

Provavelmente nos seus testes você precisará de diferentes versões de uma mesma entidade, ou de instâncias diferentes de uma mesma entidade, isso é feito com aspectos.

Para criar um aspecto é necessário instânciar mais um novo campo de nossa fixture, o campo _traits_. Vamos supor que você queira um “segundo” usuário para o seus testes, a fixture ficaria assim:
```php
class Usuario extends AbstractFixture
{

    protected $params = array(
        'email' => 'usuario@email.com',
        'senha' => 'e8d95a51f3af4a3b134bf6bb680a213a'
    );

    protected $associations = array(
        'tipo' => 'tipoUsuario'
    );

    protected $traits = array(
        'segundo' => array(
            'email'=>'segundo.usuario@email.com'
        )
    );

}
```

As chaves do array do atributo trait se referem aos nomes dos aspectos. É sugerido que sejam utilizados adjetivos para o nome, pois ficará mais claro nos testes de aceitação a diferência entre as duas instâncias. É importante notar que a única diferença entre o “segundo usuário” e o nosso usuário original é o email, todos os outros campos são preenchidos com os mesmos valores.

Os aspectos também podem ter associações diferentes da fixture base. Seja a fixture tipoUsuario desta forma:
```php
class TipoUsuario extends AbstractFixture
{
    protected $params = array(
        'descricao' => 'OPERADOR',
    );

    protected $traits = array(
        'admin' => array(
            'descricao'=>'ADMINISTRADOR'
        )
    );
}
```
Então um aspecto para um usuário administrador com uma senha diferente fica assim:
```php
class Usuario extends AbstractFixture
{

    protected $params = array(
        'email' => 'usuario@email.com',
        'senha' => 'e8d95a51f3af4a3b134bf6bb680a213a'
    );

    protected $associations = array(
        'tipo' => 'tipoUsuario'
    );

    protected $traits = array(
        'segundo' => array(
            'email'=>'segundo.usuario@email.com'
        ),
        'administrador' => array(
            'senha' => '21232f297a57a5a743894a0e4a801fc3',
            '_associations' => array(
                'tipo' => 'admin tipoUsuario'
            )
        )
    );
}
```

É importante notar que o aspecto vem **antes** do nome da fixture. Isso porque os testes de aceitação são escritos em inglês. Além disso, apesar de não ser o mais comum, também está correto em português.

Caso você queira que o usuário administrador também tenha um email diferente, não é necessário alterar a fixture, pois é possível concatenar os aspectos. a fixture “segundo administrador usuario” cria um usuário com os seguintes parâmetros (no formato json):
```json
{
    "email": "segundo.usuario@email.com",
    "senha": "21232f297a57a5a743894a0e4a801fc3",
    "tipo": {
        "descricao": "ADMINISTRADOR"
    }
}
```

Algumas vezes precisamos de modificações mais simples na fixture original, ou então um grande número de entidades com modificações no mesmo campo. Para simplificar uma trait pode ser escrita da seguinte forma: <nome_do_campo>:<valor_do_campo>. Para dar um exemplo, suponha que em um teste seja necessário criar quatro usuários com emails diferentes, ao invés de declarar quatro traits que alteram apenas o email, as fixtures "usuario", "email:usuario2@email.com usuario", "email:usuario3@email.com usuario", "email:usuario4@email.com usuario" já resolvem o problema.

### Passos de Testes

Este repositório contém um contexto de Behat com passos customizados.

##### Configuração do Behat

Para utilizar os passos da biblioteca é importante declarar o contexto da pandora, assim como o do Mink, caso queira usar os passos relacionados com requisições Ajax. Uma configuração exemplo é a seguinte:
```yml

default:
  extensions:
    Behat\WebApiExtension:
      base_url: 'http://localhost/html/Teste/srv/public/api/'
    Behat\MinkExtension:
      base_url:  'http://localhost/html/Teste/cli/'
      sessions:
        default:
          selenium2: ~
  suites:
    srv:
      paths: [ %paths.base%/features/srv ]
      contexts:
        - Behat\WebApiExtension\Context\WebApiContext
        - FeatureContext
        - PandoraTestes\Context\PandoraSrvContext
    cli:
      paths: [ %paths.base%/features/cli ]
      contexts:
        - Behat\MinkExtension\Context\MinkContext
        - FeatureContext
        - PandoraTestes\Context\PandoraCliContext:
            error_folder: '/home/testes/screenshots'
```
Para usar o contexto deste repoistório a classe FeatureContext deve extender a classe PandoraContext, pois é necessário extender o método estático _initializeZendFramework_ responsável por iniciar a aplicação. Segue um exemplo:
```php
use PandoraTestes\Context\PandoraContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends PandoraContext
{
    /**
     * @BeforeSuite
     */
    public static function initializeZendFramework()
    {
        try {
            if (self::$zendApp === null) {
                putenv('APPLICATION_ENV=test');
                $path          = __DIR__.'/../../config/application.config.php';
                self::$zendApp = \Zend\Mvc\Application::init(require $path);
            }
        } catch (Zend\ServiceManager\Exception\ServiceNotCreatedException $e) {
            $error_message = "Exception Stack: \n";
            while ($e) {
                $error_message .= $e->getMessage() . ";\n";
                $e = $e->getPrevious();
            }
            throw new \Exception($error_message, 500);
        }
    }
}

```

##### Criação de Entidades

Para utilizar as fixtures descritas na sessão anterior este repositório tem o seguinte passo de teste:
``` feature
 Given exists a "<nome da fixture>"
```

Este passo cria as entidades necessárias para o teste, como no exemplo abaixo.
```feature
Scenario: Deslogar no sistema
    Given exists a "tipoUsuario" #cria um tipo de usuário no banco de dados.
    Given exists an "administrador usuario" #cria um usuário com o aspecto administrador no banco de dados.
    And I am logged
    When I go to "/#/page"
    And I press "Sair"
    Then I should be on "/#/login"
```

Perceba que o parâmetro do passo segue a mesma estrutura de quando referenciamos uma fixture nos aspectos, ou seja, primeiro os aspectos (adjetivos), depois o nome da fixture.

### Passos Adicionais

Além do passo de criação de entidades, essa biblioteca contém outros passos adicionais.

##### Passos de API

*Then the response should contain json with this format:*

*   Recebe um JSON como parâmetro.
*   Assume que a resposta já foi enviada e está no formato JSON.
*   Compara cada elemento do JSON da resposta com o JSON informado usando a marcação do [PHPmacher](https://github.com/coduo/php-matcher)
*   Exemplo:
```feature
Then the response should contain json with this format:
"""
    {
        "id": "@integer@",
        "username": "USUARIO",
        "email": "@string@.matchRegex('/^[^@]+@[a-z0-9]+[.][a-z0-9]+$/')",
        "permissoes": "@array@"
    }
"""
```

Mais em breve ...

### Expansões do PHPMatcher

##### Count

Verifica se um array contém um certo número de elementos.

**exemplo:**

```feature
And the response should contain json with at least these fields:
"""
{
  "_embedded": {
    "turma": [
      {
        "alunos": "@array@.count(3)"
      },
      {
        "alunos": "@array@.count(3)"
      },
      {
        "alunos": "@array@.count(2)"
      }
    ]
  }
}
"""
```
