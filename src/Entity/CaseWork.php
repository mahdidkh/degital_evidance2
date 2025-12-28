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
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\ManyToMany(targetEntity: Investigateur::class, mappedBy: 'caseWorks')]
    private Collection $investigateurs;

    /**
     * @var Collection<int, Evidence>
     */
    #[ORM\OneToMany(targetEntity: Evidence::class, mappedBy: 'caseWork')]
    private Collection $evidences;

    #[ORM\ManyToOne(inversedBy: 'caseWorks')]
    private ?Team $assignedTeam = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Supervisor $createdBy = null;

    #[ORM\Column(length: 50)]
    private ?string $priority = 'medium';

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->investigateurs = new ArrayCollection();
        $this->evidences = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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
     * @return Collection<int, Evidence>
     */
    public function getEvidences(): Collection
    {
        return $this->evidences;
    }

    public function addEvidence(Evidence $evidence): static
    {
        if (!$this->evidences->contains($evidence)) {
            $this->evidences->add($evidence);
            $evidence->setCaseWork($this);
        }

        return $this;
    }

    public function removeEvidence(Evidence $evidence): static
    {
        if ($this->evidences->removeElement($evidence)) {
            // set the owning side to null (unless already changed)
            if ($evidence->getCaseWork() === $this) {
                $evidence->setCaseWork(null);
            }
        }

        return $this;
    }

    public function getAssignedTeam(): ?Team
    {
        return $this->assignedTeam;
    }

    public function setAssignedTeam(?Team $assignedTeam): static
    {
        $this->assignedTeam = $assignedTeam;

        return $this;
    }

    public function getCreatedBy(): ?Supervisor
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?Supervisor $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
