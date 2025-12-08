<?php

namespace App\Entity;

use App\Repository\CaseWorkRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CaseWorkRepository::class)]
class CaseWork
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $tittel = null;

    #[ORM\Column(length: 255)]
    private ?string $statu = null;

    #[ORM\Column(length: 255)]
    private ?string $discription = null;

    #[ORM\ManyToMany(targetEntity: Investigateur::class, mappedBy: 'caseWorks')]
    private Collection $investigateurs;

    public function __construct()
    {
        $this->investigateurs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
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

    public function getStatu(): ?string
    {
        return $this->statu;
    }

    public function setStatu(string $statu): static
    {
        $this->statu = $statu;

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

    public function getInvestigateurs(): Collection
    {
        return $this->investigateurs;
    }

    public function addInvestigateur(Investigateur $investigateur): self
    {
        if (!$this->investigateurs->contains($investigateur)) {
            $this->investigateurs->add($investigateur);
            $investigateur->addCaseWork($this);
        }

        return $this;
    }

    public function removeInvestigateur(Investigateur $investigateur): self
    {
        if ($this->investigateurs->removeElement($investigateur)) {
            $investigateur->removeCaseWork($this);
        }

        return $this;
    }
}
