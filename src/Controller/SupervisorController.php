<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Team;
use App\Entity\Supervisor;
use App\Entity\Investigateur;
use App\Entity\CaseWork;
use App\Entity\Evidence;
use App\Entity\ChainOfCustody;
use App\Entity\AuditLog;
use Symfony\Component\HttpFoundation\JsonResponse;

#[IsGranted('ROLE_SUPERVISOR')]
class SupervisorController extends AbstractController
{
    #[Route('/supervisor', name: 'app_supervisor_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();

        $teams = $entityManager->getRepository(Team::class)->findBy(['supervisor' => $supervisor]);
        $totalCases = $entityManager->getRepository(CaseWork::class)->count(['createdBy' => $supervisor]);
        $totalInvestigators = $entityManager->getRepository(Investigateur::class)->count(['supervisor' => $supervisor]);

        // Fetch recent activities for cases created by this supervisor
        $caseworks = $entityManager->getRepository(CaseWork::class)->findBy(['createdBy' => $supervisor]);
        $caseIds = array_map(fn($c) => $c->getId(), $caseworks);

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

        // Fetch recent team-related audit logs
        $auditLogs = $entityManager->getRepository(AuditLog::class)
            ->createQueryBuilder('a')
            ->where('a.user = :supervisor')
            ->andWhere('a.eventType IN (:teamEvents)')
            ->setParameter('supervisor', $supervisor)
            ->setParameter('teamEvents', ['team_created', 'team_deleted', 'team_member_added', 'team_member_removed', 'case_assigned_to_team', 'case_removed_from_team'])
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Combine and sort activities
        $allActivities = [];

        // Add ChainOfCustody activities
        foreach ($recentActivities as $activity) {
            $allActivities[] = [
                'action' => $activity->getAction(),
                'description' => $activity->getDescription(),
                'dateUpdate' => $activity->getDateUpdate(),
                'type' => 'evidence'
            ];
        }

        // Add AuditLog activities
        foreach ($auditLogs as $log) {
            $allActivities[] = [
                'action' => ucfirst(str_replace('_', ' ', $log->getEventType())),
                'description' => $log->getEventDescription(),
                'dateUpdate' => $log->getCreatedAt(),
                'type' => 'audit'
            ];
        }

        // Sort by date descending and take top 5
        usort($allActivities, function($a, $b) {
            return $b['dateUpdate'] <=> $a['dateUpdate'];
        });
        $allActivities = array_slice($allActivities, 0, 5);

        return $this->render('supervisor/index.html.twig', [
            'teams' => $teams,
            'stats' => [
                'total_teams' => count($teams),
                'total_cases' => $totalCases,
                'total_investigators' => $totalInvestigators,
            ],
            'recent_activities' => $allActivities,
        ]);
    }

    #[Route('/supervisor/team/new', name: 'app_supervisor_team_new', methods: ['GET', 'POST'])]
    public function newTeam(Request $request, EntityManagerInterface $entityManager, \App\Service\AuditService $auditService): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();

        if ($request->isMethod('POST')) {
            $teamName = $request->request->get('name');
            if (!$teamName) {
                $this->addFlash('error', 'Team name is required.');
                return $this->redirectToRoute('app_supervisor_team_new');
            }

            $team = new Team();
            $team->setName($teamName);
            $team->setSupervisor($supervisor);
           
            $entityManager->persist($team);
            $entityManager->flush();

            // Log team creation
            $auditService->logGenericEvent(
                'team_created',
                sprintf('Supervisor "%s %s" created new team "%s"', $supervisor->getFirstName(), $supervisor->getLastName(), $teamName),
                $supervisor,
                'info',
                ['team_name' => $teamName, 'team_id' => $team->getId()]
            );

            $this->addFlash('success', 'Team created successfully.');

            return $this->redirectToRoute('app_supervisor_index');
        }

        $teams = $entityManager->getRepository(Team::class)->findBy(['supervisor' => $supervisor]);

        return $this->render('supervisor/team/new.html.twig', [
            'teams' => $teams,
        ]);
    }

    #[Route('/supervisor/team/{id}/manage', name: 'app_supervisor_team_manage', methods: ['GET'])]
    public function manageTeam(Team $team, EntityManagerInterface $entityManager): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();

        
       
        $availableInvestigators = $entityManager->getRepository(Investigateur::class)->createQueryBuilder('i')
            ->where('i.supervisor = :supervisor')
            ->orWhere('i.supervisor IS NULL')
            ->setParameter('supervisor', $supervisor)
            ->getQuery()
            ->getResult();
        
        
        $availableInvestigators = array_filter($availableInvestigators, function($inv) use ($team) {
            return !$inv->getTeams()->contains($team);
        });

        
        $availableCases = $entityManager->getRepository(CaseWork::class)->createQueryBuilder('c')
            ->where('c.createdBy = :supervisor')
            ->andWhere('c.assignedTeam != :team OR c.assignedTeam IS NULL')
            ->andWhere('c.status != :archived')
            ->setParameter('supervisor', $supervisor)
            ->setParameter('team', $team)
            ->setParameter('archived', 'archived')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('supervisor/team/manage.html.twig', [
            'team' => $team,
            'availableInvestigators' => $availableInvestigators,
            'availableCases' => $availableCases,
        ]);
    }

    #[Route('/supervisor/team/{id}/add-member', name: 'app_supervisor_team_add_member', methods: ['POST'])]
    public function addMember(Team $team, Request $request, EntityManagerInterface $entityManager, \App\Service\AuditService $auditService): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();


        $investigatorId = $request->request->get('investigatorId');
        $investigator = $entityManager->getRepository(Investigateur::class)->find($investigatorId);

        if (!$investigator) {
            $this->addFlash('error', 'Investigator not found.');
            return $this->redirectToRoute('app_supervisor_team_manage', ['id' => $team->getId()]);
        }

        
        if ($investigator->getSupervisor() !== null && $investigator->getSupervisor() !== $supervisor) {
            $this->addFlash('error', 'This investigator is managed by another supervisor.');
            return $this->redirectToRoute('app_supervisor_team_manage', ['id' => $team->getId()]);
        }

    
        if ($investigator->getSupervisor() === null) {
            $investigator->setSupervisor($supervisor);
        }

        $investigator->addTeam($team);
        $entityManager->flush();

       
        $auditService->logTeamMembershipChange($supervisor, $investigator, $team->getName(), 'added');

        $this->addFlash('success', sprintf('%s added to team %s.', $investigator->getEmail(), $team->getName()));

        return $this->redirectToRoute('app_supervisor_team_manage', ['id' => $team->getId()]);
    }

    #[Route('/supervisor/team/{id}/remove-member/{investigatorId}', name: 'app_supervisor_team_remove_member', methods: ['POST'])]
    public function removeMember(Team $team, int $investigatorId, EntityManagerInterface $entityManager, \App\Service\AuditService $auditService): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();

        if ($team->getSupervisor() !== $supervisor) {
            throw $this->createAccessDeniedException('You are not authorized to manage this team.');
        }

        $investigator = $entityManager->getRepository(Investigateur::class)->find($investigatorId);

        if (!$investigator || !$investigator->getTeams()->contains($team)) {
            $this->addFlash('error', 'Investigator not found in this team.');
            return $this->redirectToRoute('app_supervisor_team_manage', ['id' => $team->getId()]);
        }

        $investigator->removeTeam($team);
        $entityManager->flush();

        
        $auditService->logTeamMembershipChange($supervisor, $investigator, $team->getName(), 'removed');

        $this->addFlash('success', sprintf('%s removed from team %s.', $investigator->getEmail(), $team->getName()));

        return $this->redirectToRoute('app_supervisor_team_manage', ['id' => $team->getId()]);
    }

    #[Route('/supervisor/team/{id}/assign-case', name: 'app_supervisor_team_assign_case', methods: ['POST'])]
    public function assignCase(Team $team, Request $request, EntityManagerInterface $entityManager, \App\Service\AuditService $auditService): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();

        if ($team->getSupervisor() !== $supervisor) {
            throw $this->createAccessDeniedException('You are not authorized to manage this team.');
        }

        $caseId = $request->request->get('caseId');
        $casework = $entityManager->getRepository(CaseWork::class)->find($caseId);

        if (!$casework || $casework->getCreatedBy() !== $supervisor) {
            $this->addFlash('error', 'Case not found or access denied.');
            return $this->redirectToRoute('app_supervisor_team_manage', ['id' => $team->getId()]);
        }

        $oldTeam = $casework->getAssignedTeam();
        $casework->setAssignedTeam($team);
        $entityManager->flush();

        
        $auditService->logGenericEvent(
            'case_assigned_to_team',
            sprintf('Supervisor "%s %s" assigned case "%s" to team "%s"', 
                $supervisor->getFirstName(), $supervisor->getLastName(), 
                $casework->gettitle(), $team->getName()),
            $supervisor,
            'info',
            [
                'case_id' => $casework->getId(),
                'case_title' => $casework->gettitle(),
                'team_name' => $team->getName(),
                'old_team' => $oldTeam ? $oldTeam->getName() : null
            ]
        );

        $this->addFlash('success', sprintf('Case "%s" assigned to team %s.', $casework->gettitle(), $team->getName()));

        return $this->redirectToRoute('app_supervisor_team_manage', ['id' => $team->getId()]);
    }

    #[Route('/supervisor/team/{id}/remove-case/{caseId}', name: 'app_supervisor_team_remove_case', methods: ['POST'])]
    public function removeCaseFromTeam(Team $team, int $caseId, EntityManagerInterface $entityManager, \App\Service\AuditService $auditService): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();

        if ($team->getSupervisor() !== $supervisor) {
            throw $this->createAccessDeniedException('You are not authorized to manage this team.');
        }

        $casework = $entityManager->getRepository(CaseWork::class)->find($caseId);

        if (!$casework || $casework->getAssignedTeam() !== $team) {
            $this->addFlash('error', 'Case not found in this team.');
            return $this->redirectToRoute('app_supervisor_team_manage', ['id' => $team->getId()]);
        }

        $casework->setAssignedTeam(null);
        $entityManager->flush();

        // Log case removal
        $auditService->logGenericEvent(
            'case_removed_from_team',
            sprintf('Supervisor "%s %s" removed case "%s" from team "%s"', 
                $supervisor->getFirstName(), $supervisor->getLastName(), 
                $casework->gettitle(), $team->getName()),
            $supervisor,
            'info',
            [
                'case_id' => $casework->getId(),
                'case_title' => $casework->gettitle(),
                'team_name' => $team->getName()
            ]
        );

        $this->addFlash('success', sprintf('Case "%s" removed from team %s.', $casework->gettitle(), $team->getName()));

        return $this->redirectToRoute('app_supervisor_team_manage', ['id' => $team->getId()]);
    }

    #[Route('/supervisor/team/{id}/delete', name: 'app_supervisor_team_delete', methods: ['POST'])]
    public function deleteTeam(Team $team, EntityManagerInterface $entityManager, \App\Service\AuditService $auditService): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();

        if ($team->getSupervisor() !== $supervisor) {
            throw $this->createAccessDeniedException('You are not authorized to delete this team.');
        }

        
        foreach ($team->getCaseWorks() as $casework) {
            $casework->setAssignedTeam(null);
        }

        $teamName = $team->getName();
        $entityManager->remove($team);
        $entityManager->flush();

        
        $auditService->logGenericEvent(
            'team_deleted',
            sprintf('Supervisor "%s %s" deleted team "%s"', $supervisor->getFirstName(), $supervisor->getLastName(), $teamName),
            $supervisor,
            'warning',
            ['team_name' => $teamName]
        );

        $this->addFlash('success', sprintf('Team "%s" deleted successfully.', $teamName));

        return $this->redirectToRoute('app_supervisor_index');
    }

    #[Route('/supervisor/casework', name: 'app_supervisor_casework_index', methods: ['GET'])]
    public function caseworkIndex(EntityManagerInterface $entityManager): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();
        
        if (!$supervisor instanceof Supervisor) {
            throw $this->createAccessDeniedException('You are not a valid supervisor.');
        }
        
        
        $caseworks = $entityManager->getRepository(CaseWork::class)->createQueryBuilder('c')
            ->where('c.createdBy = :supervisor')
            ->andWhere('c.status != :status')
            ->setParameter('supervisor', $supervisor)
            ->setParameter('status', 'archived')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

       

        return $this->render('supervisor/casework/index.html.twig', [
            'caseworks' => $caseworks,
        ]);
    }

    #[Route('/supervisor/archive', name: 'app_supervisor_archive_index', methods: ['GET'])]
    public function archiveIndex(EntityManagerInterface $entityManager): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();

        
        $caseworks = $entityManager->getRepository(CaseWork::class)->findBy(
            ['createdBy' => $supervisor, 'status' => 'archived'],
            ['updatedAt' => 'DESC']
        );

        return $this->render('supervisor/casework/archive.html.twig', [
            'caseworks' => $caseworks,
        ]);
    }

    #[Route('/supervisor/casework/new', name: 'app_supervisor_casework_new', methods: ['GET', 'POST'])]
    public function newCasework(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();
        
        $teams = $entityManager->getRepository(Team::class)->findBy(['supervisor' => $supervisor]);

        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $priority = $request->request->get('priority');
            $teamId = $request->request->get('team_id');

            if (!$title || !$description || !$priority) {
                $this->addFlash('error', 'All fields are required.');
                return $this->redirectToRoute('app_supervisor_casework_new');
            }

            $casework = new CaseWork();
            $casework->settitle($title);
            $casework->setdescription($description);
            $casework->setPriority($priority);
            $casework->setstatus('open');
            $casework->setCreatedBy($supervisor);

            if ($teamId) {
                $team = $entityManager->getRepository(Team::class)->find($teamId);
                if ($team && $team->getSupervisor() === $supervisor) {
                    $casework->setAssignedTeam($team);
                }
            }

            $entityManager->persist($casework);
            $entityManager->flush();

            $this->addFlash('success', 'Case created successfully.');

            return $this->redirectToRoute('app_supervisor_casework_index');
        }

        return $this->render('supervisor/casework/new.html.twig', [
            'teams' => $teams,
        ]);
    }

    #[Route('/supervisor/casework/{id}', name: 'app_supervisor_casework_show', methods: ['GET'])]
    public function showCasework(CaseWork $casework): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();
        
    

        return $this->render('supervisor/casework/show.html.twig', [
            'casework' => $casework,
        ]);
    }

    #[Route('/supervisor/casework/{id}/status', name: 'app_supervisor_casework_status', methods: ['POST'])]
    public function changestatus(CaseWork $casework, Request $request, EntityManagerInterface $entityManager, \App\Service\AuditService $auditService): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();


        $oldstatus = $casework->getstatus();
        $newstatus = $request->request->get('status');
        $validstatuses = ['open', 'closed', 'archived'];

        if (in_array($newstatus, $validstatuses)) {
            $casework->setstatus($newstatus);
            $casework->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            
            $auditService->logCasestatusChange($supervisor, $casework->gettitle(), $oldstatus, $newstatus);

            $this->addFlash('success', sprintf('Case status updated to %s.', $newstatus));
        } else {
            $this->addFlash('error', 'Invalid status provided.');
        }

        return $this->redirectToRoute('app_supervisor_casework_show', ['id' => $casework->getId()]);
    }

    #[Route('/supervisor/casework/{id}/explore', name: 'app_supervisor_casework_explore', methods: ['GET'])]
    public function exploreCase(CaseWork $casework): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();
        
       
        return $this->render('supervisor/casework/explore.html.twig', [
            'casework' => $casework,
        ]);
    }

    #[Route('/supervisor/evidence/{id}/verify', name: 'app_supervisor_evidence_verify', methods: ['POST'])]
    public function verifyIntegrity(Evidence $evidence, \App\Service\IntegrityService $integrityService): JsonResponse
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();
        
        $casework = $evidence->getCaseWork();
        if ($casework?->getCreatedBy() !== $supervisor) {
             return new JsonResponse(['status' => 'error', 'message' => 'Access denied.'], 403);
        }

        $result = $integrityService->verifyEvidenceIntegrity($evidence, $supervisor, 'Supervisor');

        return new JsonResponse($result);
    }

    #[Route('/supervisor/casework/{id}/report', name: 'app_supervisor_casework_report', methods: ['GET'])]
    public function generateReport(CaseWork $casework): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();
        
        if ($casework->getCreatedBy() !== $supervisor) {
            throw $this->createAccessDeniedException('You are not authorized to view this case.');
        }

        return $this->render('supervisor/casework/report.html.twig', [
            'casework' => $casework,
            'generatedAt' => new \DateTime(),
        ]);
    }
}
