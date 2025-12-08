<?php

namespace App\Entity;

use App\Repository\SupervisorRepository;
use Doctrine\ORM\Mapping as ORM;

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
}
