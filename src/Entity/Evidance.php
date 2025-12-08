<?php

namespace App\Entity;

use App\Repository\EvidanceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EvidanceRepository::class)]
class Evidance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $tittel = null;

    #[ORM\Column(length: 255)]
    private ?string $fileHash = null;

    #[ORM\Column(length: 255)]
    private ?string $discription = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTittel(): ?string
    {
        return $this->tittel;
    }

    public function setTittel(string $tittel): static
    {
        $this->tittel = $tittel;

        return $this;
    }

    public function getFileHash(): ?string
    {
        return $this->fileHash;
    }

    public function setFileHash(string $fileHash): static
    {
        $this->fileHash = $fileHash;

        return $this;
    }

    public function getDiscription(): ?string
    {
        return $this->discription;
    }

    public function setDiscription(string $discription): static
    {
        $this->discription = $discription;

        return $this;
    }
}
