<?php

namespace App\Entity;

use App\Repository\DetallesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DetallesRepository::class)
 */
class Detalles
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nameOne;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @ORM\OneToOne(targetEntity=User::class, inversedBy="detalles")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;


    public function __toString()
    {
     return $this->title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNameOne(): ?string
    {
        return $this->nameOne;
    }

    public function setNameOne(?string $nameOne): self
    {
        $this->nameOne = $nameOne;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
