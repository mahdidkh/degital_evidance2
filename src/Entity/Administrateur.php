<?php

namespace App\Entity;

use App\Repository\AdministrateurRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdministrateurRepository::class)]
class Administrateur extends User
{
    

    #[ORM\Column(length: 255)]
    private ?string $AdminDomain = null;

   
    public function getAdminDomain(): ?string
    {
        return $this->AdminDomain;
    }

    public function setAdminDomain(string $AdminDomain): static
    {
        $this->AdminDomain = $AdminDomain;

        return $this;
    }
}
