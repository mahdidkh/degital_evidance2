<?php

namespace App\Entity;

use App\Repository\EvidanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

   // nouveau : nom du fichier stocké sur le serveur
    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $storedFilename = null;

    // nouveau : remarque
    #[ORM\Column(type:"text", nullable:true)]
    private ?string $remarque = null;

    #[ORM\Column(length: 255)]
    private ?string $discription = null;

    #[ORM\ManyToOne(inversedBy: 'evidances')]
    private ?CaseWork $caseWork = null;

    /**
     * @var Collection<int, ChainOfCoady>
     */
    #[ORM\OneToMany(targetEntity: ChainOfCoady::class, mappedBy: 'evidence')]
    private Collection $chainEntries;

    public function __construct()
    {
        $this->chainEntries = new ArrayCollection();
    }

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

    public function getStoredFilename(): ?string 
    {
         return $this->storedFilename;
    }
    public function setStoredFilename(?string $storedFilename): self 
    { $this->storedFilename = $storedFilename; 
        return $this; 
    }

    public function getRemarque(): ?string { 
        return $this->remarque; 
    }
    public function setRemarque(?string $remarque): self { 
        $this->remarque = $remarque; return $this; 
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

    public function getCaseWork(): ?CaseWork
    {
        return $this->caseWork;
    }

    public function setCaseWork(?CaseWork $caseWork): static
    {
        $this->caseWork = $caseWork;

        return $this;
    }

    /**
     * @return Collection<int, ChainOfCoady>
     */
    public function getChainEntries(): Collection
    {
        return $this->chainEntries;
    }

    public function addChainEntry(ChainOfCoady $chainEntry): static
    {
        if (!$this->chainEntries->contains($chainEntry)) {
            $this->chainEntries->add($chainEntry);
            $chainEntry->setEvidence($this);
        }

        return $this;
    }

    public function removeChainEntry(ChainOfCoady $chainEntry): static
    {
        if ($this->chainEntries->removeElement($chainEntry)) {
            // set the owning side to null (unless already changed)
            if ($chainEntry->getEvidence() === $this) {
                $chainEntry->setEvidence(null);
            }
        }

        return $this;
    }
}
