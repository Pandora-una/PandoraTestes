<?php

namespace PandoraTestesTeste\Fixture;

use PandoraTestes\Fixture\AbstractFixture;

class Livro extends AbstractFixture
{
    protected $params = ['titulo' => 'Perto do Coração Selvagem'];

    protected $associations = ['autor' => 'autor'];

    protected $traits = [
        'deInativo' => ['_associations' => ['autor' => 'inativo autor']],
        'semAutor' => ['_associations' => ['autor' => null]],
    ];
}
