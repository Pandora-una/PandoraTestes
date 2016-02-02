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

_Behat_ já possui um binário para rodar os testes, porém o Zend Framework 2 não suporta a separação entre um ambiente de teste e um de desenvolvimento separados de forma simples. Portanto foi necessário criar um script para facilitar a execução dos testes e para evitar erros. Portanto é necessário apenas o seguinte comando para rodar os testes:
```sh
$ vendor/bin/pandora-behat
```
O script aceita as mesmas opções que o binário do behat, o seguinte comando lista as opções:
```sh
$ vendor/bin/pandora-behat --help
```

Caso os testes necessitem de Selenium2 rodando em background, o seguinte comando tem a função de inicializá-lo:
```sh
$ java -jar vendor/bin/selenium-server-standalone-2.45.0.jar
```

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

Como pode ser visto no exemplo anterior, Uma fixture deve ter definido seus atributos básicos. Quando esses atributos são valores simples, o lugar para informálos é no campo _params_. Este campo deve conter um hash da qual a chave indica o nome do atributo e o valor indíca o valor do atributo.

Para o exemplo da nossa fixture de usuário, as chaves do array são pensadas de forma a “casar” com os métodos de acesso do Doctrine, ou seja, durante a criação da entidade a biblioteca executará os seguintes comandos para a criação de um usuário:
```php
$entityUsuario->setEmail('usuario@email.com');
$entityUsuario->setSenha('e8d95a51f3af4a3b134bf6bb680a213a');
```

Por isso, é importante que as chaves sejam as mesmas que os campos da entidade e que os métodos de acesso existam.

##### Associações

Alguns atributos de nossa fixture farão refererência a outras tabelas, portanto as nossas fixtures deverão fazer referência a outras fixtures. Assumindo que exista uma fixture como essa:  
```php
class TipoUsuario extends AbstractFixture
{
    protected $params = array(
        'descricao' => 'OPERADOR',
    );
}
```

Para criar a associação é preciso instânciar um novo campo de nossa fixture como neste exemplo:
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

As chaves do array do trait se referem aos nomes dos aspectos. É sugerido que sejam utilizados adjetivos para o nome, pois ficará mais claro nos testes de aceitação a diferência entre as duas instâncias. É importante notar que a única diferença entre o “segundo usuário” e o nosso usuário original é o email.

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
É importante notar que para usar o contexto deste repoistório a classe FeatureContext tem que extender o contexto da pandora, pois é necessário extender o método estático _initializeZendFramework_ responsável por iniciar a aplicação como no exemplo abaixo:
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
    static public function initializeZendFramework()
    {
        if (self::$zendApp === null) {
            putenv("APPLICATION_ENV=test");
            $path = __DIR__ . '/../../config/application.config.php';
            self::$zendApp = \Zend\Mvc\Application::init(require $path);
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
Scenario: Desogar no sistema
    Given exists a "tipoUsuario" #cria um tipo de usuário no banco de dados. 
    Given exists an "administrador usuario" #cria um usuário com o aspecto administrador no banco de dados. 
    And I am logged
    When I go to "/#/page"
    And I press "Sair"
    Then I should be on "/#/login"
```

Perceba que o parâmetro passado para o passo segue a mesma estrutura de quando referenciamos uma fixture nos aspectos, ou seja, primeiro os aspectos (adjetivos), depois o nome da fixture.

### Passos Adicionais

Além do passo de criação de entidades, essa biblioteca contém outros passos adicionais.

#### Passos de API


