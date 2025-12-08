<?php

namespace App\Entity;

use App\Repository\ChainOfCoadyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChainOfCoadyRepository::class)]
class ChainOfCoady
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $action = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date_update = null;

    #[ORM\Column(length: 255)]
    private ?string $newHash = null;

    #[ORM\Column(length: 255)]
    private ?string $PreviosHash = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDateUpdate(): ?\DateTime
    {
        return $this->date_update;
    }

    public function setDateUpdate(\DateTime $date_update): static
    {
        $this->date_update = $date_update;

        return $this;
    }

    public function getNewHash(): ?string
    {
        return $this->newHash;
    }

    public function setNewHash(string $newHash): static
    {
        $this->newHash = $newHash;

        return $this;
    }

    public function getPreviosHash(): ?string
    {
        return $this->PreviosHash;
    }

    public function setPreviosHash(string $PreviosHash): static
    {
        $this->PreviosHash = $PreviosHash;

        return $this;
    }
}
