<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Team;
use App\Entity\CaseWork;

#[IsGranted('ROLE_INVESTIGATEUR')]
class InvestigateurController extends AbstractController
{
    #[Route('/investigateur', name: 'app_investigateur_index')]
    public function index(): Response
    {
        return $this->render('investigateur/index.html.twig');
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
}
