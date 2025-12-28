<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Team;
use App\Entity\CaseWork;
use App\Entity\Evidence;
use App\Entity\ChainOfCustody;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;

#[IsGranted('ROLE_INVESTIGATEUR')]
class InvestigateurController extends AbstractController
{
    #[Route('/investigateur', name: 'app_investigateur_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $teams = $user->getTeams();
        $cases = [];
        $totalEvidence = 0;
        
        foreach ($teams as $team) {
            foreach ($team->getCaseWorks() as $casework) {
                $cases[] = $casework;
                $totalEvidence += $casework->getEvidences()->count();
            }
        }
        
        // Sort cases by creation date (newest first)
        usort($cases, function($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });
        
        $recentCases = array_slice($cases, 0, 5);
        
        // Get recent forensic activity for these cases
        $caseIds = array_map(fn($c) => $c->getId(), $cases);
        
        $recentActivities = [];
        if (!empty($caseIds)) {
            $recentActivities = $entityManager->getRepository(ChainOfCustody::class)
                ->createQueryBuilder('c')
                ->where('c.evidence IN (
                    SELECT e.id FROM App\Entity\Evidence e WHERE e.caseWork IN (:caseIds)
                )')
                ->setParameter('caseIds', $caseIds)
                ->orderBy('c.date_update', 'DESC')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();
        }

        return $this->render('investigateur/index.html.twig', [
            'stats' => [
                'total_teams' => $teams->count(),
                'total_cases' => count($cases),
                'total_evidence' => $totalEvidence,
            ],
            'recent_cases' => $recentCases,
            'recent_activities' => $recentActivities,
        ]);
    }

    #[Route('/investigateur/teams', name: 'app_investigateur_teams')]
    public function teams(): Response
    {
        return $this->render('investigateur/teams.html.twig');
    }

    #[Route('/investigateur/team/{id}/members', name: 'app_investigateur_team_members')]
    public function teamMembers(Team $team): Response
    {
        // Check if the investigator is part of this team
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        if (!$user->getTeams()->contains($team)) {
            throw $this->createAccessDeniedException('You are not a member of this team.');
        }

        return $this->render('investigateur/team_members.html.twig', [
            'team' => $team,
        ]);
    }

    #[Route('/investigateur/cases', name: 'app_investigateur_cases')]
    public function cases(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Get all teams the investigator belongs to
        $teams = $user->getTeams();
        
        // Collect all cases from all teams
        $cases = [];
        foreach ($teams as $team) {
            foreach ($team->getCaseWorks() as $casework) {
                $cases[] = $casework;
            }
        }
        
        // Sort by creation date (newest first)
        usort($cases, function($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });

        return $this->render('investigateur/cases.html.twig', [
            'cases' => $cases,
        ]);
    }

    #[Route('/investigateur/case/{id}', name: 'app_investigateur_case_show')]
    public function showCase(CaseWork $casework): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Check if the case is assigned to one of the investigator's teams
        $assignedTeam = $casework->getAssignedTeam();
        if (!$assignedTeam || !$user->getTeams()->contains($assignedTeam)) {
            throw $this->createAccessDeniedException('You do not have access to this case.');
        }

        return $this->render('investigateur/case_show.html.twig', [
            'casework' => $casework,
        ]);
    }

    #[Route('/investigateur/case/{id}/explore', name: 'app_investigateur_case_explore')]
    public function exploreCase(CaseWork $casework): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Check if the case is assigned to one of the investigator's teams
        $assignedTeam = $casework->getAssignedTeam();
        if (!$assignedTeam || !$user->getTeams()->contains($assignedTeam)) {
            throw $this->createAccessDeniedException('You do not have access to this case.');
        }

        return $this->render('investigateur/explore.html.twig', [
            'casework' => $casework,
        ]);
    }

    #[Route('/investigateur/evidence/{id}/verify', name: 'app_investigateur_evidence_verify', methods: ['POST'])]
    public function verifyIntegrity(Evidence $evidence, EntityManagerInterface $entityManager, \App\Service\AuditService $auditService): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $casework = $evidence->getCaseWork();
        $assignedTeam = $casework?->getAssignedTeam();
        
        if (!$assignedTeam || !$user->getTeams()->contains($assignedTeam)) {
             return new JsonResponse(['status' => 'error', 'message' => 'Access denied.'], 403);
        }

        $destination = $this->getParameter('evidence_directory');
        $filePath = $destination . '/' . $evidence->getStoredFilename();

        if (!file_exists($filePath)) {
            return new JsonResponse(['status' => 'error', 'message' => 'File not found on server.'], 404);
        }

        $currentHash = hash_file('sha256', $filePath);
        $storedHash = $evidence->getFileHash();

        if ($currentHash === $storedHash) {
            $status = 'verified';
            $message = 'Integrity verified. File is unchanged.';
        } else {
            $status = 'tampered';
            $message = 'TAMPERED! Current hash does not match stored hash.';
        }

        // Log this check in the Chain of Custody for forensics
        $chainEntry = new ChainOfCustody();
        $chainEntry->setAction('Integrity Verification');
        $chainEntry->setDescription(sprintf('Verification performed. status: %s. Message: %s', strtoupper($status), $message));
        $chainEntry->setDateUpdate(new \DateTime());
        $chainEntry->setNewHash($currentHash);
        $chainEntry->setPreviosHash($storedHash);
        $chainEntry->setEvidence($evidence);
        $chainEntry->setUser($user);

        $entityManager->persist($chainEntry);
        $entityManager->flush();

        // Log to audit system
        if ($status === 'tampered') {
            $auditService->logTamperedAlert($user, $evidence, [
                'current_hash' => $currentHash,
                'stored_hash' => $storedHash
            ]);
        } else {
            $auditService->logIntegrityCheck($user, $evidence, $status, [
                'hash' => $currentHash
            ]);
        }

        if ($status === 'verified') {
            return new JsonResponse([
                'status' => 'verified',
                'message' => $message,
                'hash' => $currentHash
            ]);
        } else {
            return new JsonResponse([
                'status' => 'tampered',
                'message' => $message,
                'current_hash' => $currentHash,
                'stored_hash' => $storedHash
            ]);
        }
    }
}
