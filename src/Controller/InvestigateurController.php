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
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $teams = $user->getTeams();
        
        // TEMPORARY: Add test teams if no teams exist
        if ($teams->isEmpty()) {
            $mockTeams = [
                (object)[
                    'id' => 1,
                    'name' => 'Crime Investigation Unit',
                    'supervisor' => (object)['first_name' => 'John', 'last_name' => 'Doe'],
                    'investigateurs' => [(object)['first_name' => 'Jane', 'last_name' => 'Smith'], (object)['first_name' => 'Bob', 'last_name' => 'Johnson']],
                    'caseWorks' => [(object)['title' => 'Bank Robbery'], (object)['title' => 'Assault Case']]
                ],
                (object)[
                    'id' => 2,
                    'name' => 'Cyber Crime Division',
                    'supervisor' => (object)['first_name' => 'Alice', 'last_name' => 'Brown'],
                    'investigateurs' => [(object)['first_name' => 'Charlie', 'last_name' => 'Wilson'], (object)['first_name' => 'Diana', 'last_name' => 'Davis']],
                    'caseWorks' => [(object)['title' => 'Hacking Incident'], (object)['title' => 'Data Breach']]
                ],
                (object)[
                    'id' => 3,
                    'name' => 'Missing Persons Bureau',
                    'supervisor' => (object)['first_name' => 'Eve', 'last_name' => 'Miller'],
                    'investigateurs' => [(object)['first_name' => 'Frank', 'last_name' => 'Garcia'], (object)['first_name' => 'Grace', 'last_name' => 'Lee']],
                    'caseWorks' => [(object)['title' => 'Missing Teenager'], (object)['title' => 'Elderly Disappearance']]
                ]
            ];
            $teams = $mockTeams;
        }

        return $this->render('investigateur/teams.html.twig', [
            'teams' => $teams,
        ]);
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
        
        // TEMPORARY: Add test cases if no cases exist
        if (empty($cases)) {
            // Create mock cases for testing search functionality
            $mockCases = [
                (object)[
                    'id' => 1,
                    'title' => 'Bank Robbery Investigation',
                    'description' => 'Investigation of the recent bank robbery downtown',
                    'status' => 'open',
                    'priority' => 'high',
                    'createdAt' => new \DateTimeImmutable('2024-01-15'),
                    'assignedTeam' => (object)['name' => 'Crime Unit']
                ],
                (object)[
                    'id' => 2,
                    'title' => 'Cyber Fraud Case',
                    'description' => 'Online banking fraud investigation',
                    'status' => 'open',
                    'priority' => 'medium',
                    'createdAt' => new \DateTimeImmutable('2024-01-10'),
                    'assignedTeam' => (object)['name' => 'Cyber Unit']
                ],
                (object)[
                    'id' => 3,
                    'title' => 'Missing Person Report',
                    'description' => 'Search for missing teenager last seen at mall',
                    'status' => 'closed',
                    'priority' => 'critical',
                    'createdAt' => new \DateTimeImmutable('2024-01-05'),
                    'assignedTeam' => (object)['name' => 'Missing Persons']
                ]
            ];
            $cases = $mockCases;
        }
        
        // Sort by creation date (newest first)
        usort($cases, function($a, $b) {
            return $b->createdAt <=> $a->createdAt;
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
