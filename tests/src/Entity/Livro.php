<?php

namespace PandoraTestesTeste\Entity;

use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity @ORM\Table(name="livro") */
class Livro
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    protected $id;

    /** @ORM\Column(type="string") */
    protected $titulo;

    /** @ORM\ManyToOne(targetEntity="Autor", inversedBy="livros") */
    protected $autor;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getTitulo()
    {
        return $this->titulo;
    }

    public function setTitulo($titulo)
    {
        $this->titulo = $titulo;
    }

    public function getAutor()
    {
        return $this->autor;
    }

    public function setAutor($autor)
    {
        $this->autor = $autor;
    }
}
