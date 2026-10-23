<?php

namespace PandoraTestesTeste\Fixture;

use PandoraTestes\Fixture\AbstractFixture;

/** Autor com uma coleção de livros (associação em lista: addLivros). */
class Estante extends AbstractFixture
{
    protected $params = ['nome' => 'Estante', 'ativo' => true];

    protected $associations = ['livros' => ['livro', 'semAutor livro']];
}
