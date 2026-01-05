<?php

namespace App\Entity;

use App\Repository\SupervisorRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Investigateur;

#[ORM\Entity(repositoryClass: SupervisorRepository::class)]
class Supervisor extends User
{
    

    #[ORM\Column(length: 255)]
    private ?string $TeamScoop = null;

    #[ORM\Column(length: 255)]
    private ?string $escalation = null;

    

    public function getTeamScoop(): ?string
    {
        return $this->TeamScoop;
    }

    public function setTeamScoop(string $TeamScoop): static
    {
        $this->TeamScoop = $TeamScoop;

        return $this;
    }

    public function getEscalation(): ?string
    {
        return $this->escalation;
    }

    public function setEscalation(string $escalation): static
    {
        $this->escalation = $escalation;

        return $this;
    }
    #[ORM\OneToMany(mappedBy: 'supervisor', targetEntity: Investigateur::class)]
    private Collection $investigateurs;

    public function __construct()
    {
        $this->investigateurs = new ArrayCollection();
        $this->teams = new ArrayCollection();
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
            $investigateur->setSupervisor($this);
        }

        return $this;
    }

    public function removeInvestigateur(Investigateur $investigateur): static
    {
        if ($this->investigateurs->removeElement($investigateur)) {
           
            if ($investigateur->getSupervisor() === $this) {
                $investigateur->setSupervisor(null);
            }
        }

        return $this;
    }

    #[ORM\OneToMany(mappedBy: 'supervisor', targetEntity: Team::class)]
    private Collection $teams;

    /**
     * @return Collection<int, Team>
     */
    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(Team $team): static
    {
        if (!$this->teams->contains($team)) {
            $this->teams->add($team);
            $team->setSupervisor($this);
        }

        return $this;
    }

    public function removeTeam(Team $team): static
    {
        if ($this->teams->removeElement($team)) {
            
            if ($team->getSupervisor() === $this) {
                $team->setSupervisor(null);
            }
        }

        return $this;
    }
}
