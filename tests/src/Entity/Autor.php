<?php

namespace PandoraTestesTeste\Entity;

use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity @ORM\Table(name="autor") */
class Autor
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    protected $id;

    /** @ORM\Column(type="string") */
    protected $nome;

    /** @ORM\Column(type="boolean") */
    protected $ativo;

    /** @ORM\OneToMany(targetEntity="Livro", mappedBy="autor") */
    protected $livros;

    public function __construct()
    {
        $this->livros = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getNome()
    {
        return $this->nome;
    }

    public function setNome($nome)
    {
        $this->nome = $nome;
    }

    public function getAtivo()
    {
        return $this->ativo;
    }

    public function setAtivo($ativo)
    {
        $this->ativo = $ativo;
    }

    public function getLivros()
    {
        return $this->livros;
    }

    public function addLivros($livros)
    {
        foreach ($livros as $livro) {
            $livro->setAutor($this);
            $this->livros->add($livro);
        }
    }
}
