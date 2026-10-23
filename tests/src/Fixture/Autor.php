<?php

namespace PandoraTestesTeste\Fixture;

use PandoraTestes\Fixture\AbstractFixture;

class Autor extends AbstractFixture
{
    protected $params = ['nome' => 'Ana', 'ativo' => true];

    protected $traits = [
        'inativo' => ['ativo' => false],
        'comId' => ['id' => 50],
        'maiusculo' => ['nome' => ['callback' => 'strtoupper', 'value' => 'clarice']],
        'callbackInvalido' => ['nome' => ['callback' => 'nao_existe', 'value' => 'x']],
    ];
}
