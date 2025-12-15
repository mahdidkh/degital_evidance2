<?php

namespace App\Entity;

use App\Repository\InvestigateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: InvestigateurRepository::class)]
class Investigateur extends User
{
    #[ORM\Column(length: 255)]
    private ?string $employerId = null;

    #[ORM\Column(length: 255)]
    private ?string $ExpertArea = null;

    #[ORM\ManyToMany(targetEntity: CaseWork::class, inversedBy: 'investigateurs')]
    private Collection $caseWorks;

    public function __construct()
    {
        $this->caseWorks = new ArrayCollection();
    }

    public function getEmployerId(): ?string
    {
        return $this->employerId;
    }

    public function setEmployerId(string $employerId): static
    {
        $this->employerId = $employerId;
        return $this;
    }

    public function getExpertArea(): ?string
    {
        return $this->ExpertArea;
    }

    public function setExpertArea(string $ExpertArea): static
    {
        $this->ExpertArea = $ExpertArea;
        return $this;
    }

    public function getCaseWorks(): Collection
    {
        return $this->caseWorks;
    }

    public function addCaseWork(CaseWork $caseWork): self
    {
        if (!$this->caseWorks->contains($caseWork)) {
            $this->caseWorks->add($caseWork);
            $caseWork->addInvestigateur($this);
        }
        return $this;
    }

    public function removeCaseWork(CaseWork $caseWork): self
    {
        if ($this->caseWorks->removeElement($caseWork)) {
            $caseWork->removeInvestigateur($this);
        }
        return $this;
    }
}
