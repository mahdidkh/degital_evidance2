<?php

namespace App\Service;

use App\Entity\ChainOfCustody;
use App\Entity\Evidence;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class IntegrityService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditService $auditService,
        private ParameterBagInterface $parameterBag
    ) {}

    public function verifyEvidenceIntegrity(Evidence $evidence, User $actor, string $verificationLevel = 'standard'): array
    {
        $destination = $this->parameterBag->get('evidence_directory');
        $filePath = $destination . '/' . $evidence->getStoredFilename();

        if (!file_exists($filePath)) {
            return [
                'status' => 'error',
                'message' => 'File not found on server.'
            ];
        }

        $currentHash = hash_file('sha256', $filePath);
        $storedHash = $evidence->getFileHash();

        if ($currentHash === $storedHash) {
            $status = 'verified';
            $message = sprintf('Integrity verified by %s. File is unchanged.', strtoupper($verificationLevel));
        } else {
            $status = 'tampered';
            $message = sprintf('TAMPERED! Current hash does not match stored hash. Flagged by %s.', strtoupper($verificationLevel));
        }

        // Log this check in the Chain of Custody for forensics
        $chainEntry = new ChainOfCustody();
        $chainEntry->setAction(sprintf('%s Integrity Verification', ucfirst($verificationLevel)));
        $chainEntry->setDescription($message);
        $chainEntry->setDateUpdate(new \DateTime());
        $chainEntry->setNewHash($currentHash);
        $chainEntry->setPreviousHash($storedHash);
        $chainEntry->setEvidence($evidence);
        $chainEntry->setUser($actor);

        $this->entityManager->persist($chainEntry);
        $this->entityManager->flush();

        // Log to audit system
        if ($status === 'tampered') {
            $this->auditService->logTamperedAlert($actor, $evidence, [
                'current_hash' => $currentHash,
                'stored_hash' => $storedHash,
                'verification_level' => $verificationLevel
            ]);
        } else {
            $this->auditService->logIntegrityCheck($actor, $evidence, $status, [
                'hash' => $currentHash,
                'verification_level' => $verificationLevel
            ]);
        }

        return [
            'status' => $status,
            'message' => $message,
            'current_hash' => $currentHash,
            'stored_hash' => $storedHash
        ];
    }
}
