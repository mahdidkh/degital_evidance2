<?php

namespace App\Entity;

use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'teams')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Supervisor $supervisor = null;

    #[ORM\ManyToMany(targetEntity: Investigateur::class, inversedBy: 'teams')]
    private Collection $investigateurs;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, CaseWork>
     */
    #[ORM\OneToMany(targetEntity: CaseWork::class, mappedBy: 'assignedTeam')]
    private Collection $caseWorks;

    public function __construct()
    {
        $this->investigateurs = new ArrayCollection();
        $this->caseWorks = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSupervisor(): ?Supervisor
    {
        return $this->supervisor;
    }

    public function setSupervisor(?Supervisor $supervisor): static
    {
        $this->supervisor = $supervisor;

        return $this;
    }

    /**
     * @return Collection<int, Investigateur>
     */
    public function getInvestigateurs(): Collection
    {
        return $this->investigateurs;
    }

    public function addInvestigateur(Investigateur $investigateur): static
    {
        if (!$this->investigateurs->contains($investigateur)) {
            $this->investigateurs->add($investigateur);
        }

        return $this;
    }

    public function removeInvestigateur(Investigateur $investigateur): static
    {
        $this->investigateurs->removeElement($investigateur);

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, CaseWork>
     */
    public function getCaseWorks(): Collection
    {
        return $this->caseWorks;
    }

    public function addCaseWork(CaseWork $caseWork): static
    {
        if (!$this->caseWorks->contains($caseWork)) {
            $this->caseWorks->add($caseWork);
            $caseWork->setAssignedTeam($this);
        }

        return $this;
    }

    public function removeCaseWork(CaseWork $caseWork): static
    {
        if ($this->caseWorks->removeElement($caseWork)) {
            // set the owning side to null (unless already changed)
            if ($caseWork->getAssignedTeam() === $this) {
                $caseWork->setAssignedTeam(null);
            }
        }

        return $this;
    }
}
