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
        /** @var \App\Entity\Investigateur $user */
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
        
        
        usort($cases, function($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });
        
        $recentCases = array_slice($cases, 0, 5);
        
       
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
        /** @var \App\Entity\Investigateur $user */
         $user = $this->getUser();

        
        $teams = $user->getTeams();
        
        
        return $this->render('investigateur/teams.html.twig', [
            'teams' => $teams,
        ]);
    }

    #[Route('/investigateur/team/{id}/members', name: 'app_investigateur_team_members')]
    public function teamMembers(Team $team): Response
    {
        
        /** @var \App\Entity\Investigateur $user */
        $user = $this->getUser();
        
       

        return $this->render('investigateur/team_members.html.twig', [
            'team' => $team,
        ]);
    }

    #[Route('/investigateur/cases', name: 'app_investigateur_cases')]
    public function cases(): Response
    {
        /** @var \App\Entity\Investigateur $user */
        $user = $this->getUser();
        
       
        $teams = $user->getTeams();
        
       
        $cases = [];
        foreach ($teams as $team) {
            foreach ($team->getCaseWorks() as $casework) {
                $cases[] = $casework;
            }
        }
        
        usort($cases, function($a, $b) {
            $aDate = $a instanceof CaseWork ? $a->getCreatedAt() : $a->createdAt;
            $bDate = $b instanceof CaseWork ? $b->getCreatedAt() : $b->createdAt;
            return $bDate <=> $aDate;
        });

        return $this->render('investigateur/cases.html.twig', [
            'cases' => $cases,
        ]);
    }

    #[Route('/investigateur/case/{id}', name: 'app_investigateur_case_show')]
    public function showCase(CaseWork $casework): Response
    {
        /** @var \App\Entity\Investigateur $user */
        $user = $this->getUser();
        
        
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
        /** @var \App\Entity\Investigateur $user */
        $user = $this->getUser();
        
      
        $assignedTeam = $casework->getAssignedTeam();
        if (!$assignedTeam || !$user->getTeams()->contains($assignedTeam)) {
            throw $this->createAccessDeniedException('You do not have access to this case.');
        }

        return $this->render('investigateur/explore.html.twig', [
            'casework' => $casework,
        ]);
    }

    #[Route('/investigateur/evidence/{id}/verify', name: 'app_investigateur_evidence_verify', methods: ['POST'])]
    public function verifyIntegrity(Evidence $evidence, \App\Service\IntegrityService $integrityService): JsonResponse
    {
        /** @var \App\Entity\Investigateur $user */
        $user = $this->getUser();
        
        $casework = $evidence->getCaseWork();
        $assignedTeam = $casework?->getAssignedTeam();
        
        if (!$assignedTeam || !$user->getTeams()->contains($assignedTeam)) {
             return new JsonResponse(['status' => 'error', 'message' => 'Access denied.'], 403);
        }

        $result = $integrityService->verifyEvidenceIntegrity($evidence, $user, 'Investigator');

        return new JsonResponse($result);
    }
}
