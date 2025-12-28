<?php

namespace App\Entity;

use App\Repository\EvidenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;

#[ORM\Entity(repositoryClass: EvidenceRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Evidence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $fileHash = null;

   // nouveau : nom du fichier stocké sur le serveur
    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $storedFilename = null;

    // nouveau : remarque
    #[ORM\Column(type:"text", nullable:true)]
    private ?string $remarque = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'evidences')]
    private ?CaseWork $caseWork = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $uploadedBy = null;

     /**
     * @var Collection<int, ChainOfCustody>
     */
    #[ORM\OneToMany(targetEntity: ChainOfCustody::class, mappedBy: 'evidence')]
    private Collection $chainEntries;

    public function __construct()
    {
        $this->chainEntries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

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
     * @return Collection<int, ChainOfCustody>
     */
    public function getChainEntries(): Collection
    {
        return $this->chainEntries;
    }

    public function addChainEntry(ChainOfCustody $chainEntry): static
    {
        if (!$this->chainEntries->contains($chainEntry)) {
            $this->chainEntries->add($chainEntry);
            $chainEntry->setEvidence($this);
        }

        return $this;
    }

    public function removeChainEntry(ChainOfCustody $chainEntry): static
    {
        if ($this->chainEntries->removeElement($chainEntry)) {
            // set the owning side to null (unless already changed)
            if ($chainEntry->getEvidence() === $this) {
                $chainEntry->setEvidence(null);
            }
        }

        return $this;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): static
    {
        $this->uploadedBy = $uploadedBy;

        return $this;
    }

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }
}
