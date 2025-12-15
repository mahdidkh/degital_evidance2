<?php

namespace App\Entity;

use App\Repository\CaseWorkRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    /**
     * @var Collection<int, Evidance>
     */
    #[ORM\OneToMany(targetEntity: Evidance::class, mappedBy: 'caseWork')]
    private Collection $evidances;

    public function __construct()
    {
        $this->investigateurs = new ArrayCollection();
        $this->evidances = new ArrayCollection();
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

    /**
     * @return Collection<int, Evidance>
     */
    public function getEvidances(): Collection
    {
        return $this->evidances;
    }

    public function addEvidance(Evidance $evidance): static
    {
        if (!$this->evidances->contains($evidance)) {
            $this->evidances->add($evidance);
            $evidance->setCaseWork($this);
        }

        return $this;
    }

    public function removeEvidance(Evidance $evidance): static
    {
        if ($this->evidances->removeElement($evidance)) {
            // set the owning side to null (unless already changed)
            if ($evidance->getCaseWork() === $this) {
                $evidance->setCaseWork(null);
            }
        }

        return $this;
    }
}
