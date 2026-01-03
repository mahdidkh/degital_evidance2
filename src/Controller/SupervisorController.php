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
use Symfony\Component\HttpFoundation\JsonResponse;

#[IsGranted('ROLE_SUPERVISOR')]
class SupervisorController extends AbstractController
{
    #[Route('/supervisor', name: 'app_supervisor_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();
        
        if (!$supervisor instanceof Supervisor) {
            throw $this->createAccessDeniedException('You are not a valid supervisor.');
        }

        $teams = $entityManager->getRepository(Team::class)->findBy(['supervisor' => $supervisor]);

        return $this->render('supervisor/index.html.twig', [
            'teams' => $teams,
        ]);
    }

    #[Route('/supervisor/team/new', name: 'app_supervisor_team_new', methods: ['GET', 'POST'])]
    public function newTeam(Request $request, EntityManagerInterface $entityManager, \App\Service\AuditService $auditService): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();

        if (!$supervisor instanceof Supervisor) {
            throw $this->createAccessDeniedException('You are not a valid supervisor.');
        }

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

        return $this->render('supervisor/team/new.html.twig');
    }

    #[Route('/supervisor/team/{id}/manage', name: 'app_supervisor_team_manage', methods: ['GET'])]
    public function manageTeam(Team $team, EntityManagerInterface $entityManager): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();

        if ($team->getSupervisor() !== $supervisor) {
            throw $this->createAccessDeniedException('You are not authorized to manage this team.');
        }

        // Get investigators assigned to this supervisor OR those with no supervisor
        $availableInvestigators = $entityManager->getRepository(Investigateur::class)->createQueryBuilder('i')
            ->where('i.supervisor = :supervisor')
            ->orWhere('i.supervisor IS NULL')
            ->setParameter('supervisor', $supervisor)
            ->getQuery()
            ->getResult();
        
        // Filter out those already in the current team
        $availableInvestigators = array_filter($availableInvestigators, function($inv) use ($team) {
            return !$inv->getTeams()->contains($team);
        });

        return $this->render('supervisor/team/manage.html.twig', [
            'team' => $team,
            'availableInvestigators' => $availableInvestigators,
        ]);
    }

    #[Route('/supervisor/team/{id}/add-member', name: 'app_supervisor_team_add_member', methods: ['POST'])]
    public function addMember(Team $team, Request $request, EntityManagerInterface $entityManager, \App\Service\AuditService $auditService): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();

        if ($team->getSupervisor() !== $supervisor) {
            throw $this->createAccessDeniedException('You are not authorized to manage this team.');
        }

        $investigatorId = $request->request->get('investigatorId');
        $investigator = $entityManager->getRepository(Investigateur::class)->find($investigatorId);

        if (!$investigator) {
            $this->addFlash('error', 'Investigator not found.');
            return $this->redirectToRoute('app_supervisor_team_manage', ['id' => $team->getId()]);
        }

        // Check if investigator is already assigned to ANOTHER supervisor
        if ($investigator->getSupervisor() !== null && $investigator->getSupervisor() !== $supervisor) {
            $this->addFlash('error', 'This investigator is managed by another supervisor.');
            return $this->redirectToRoute('app_supervisor_team_manage', ['id' => $team->getId()]);
        }

        // Assign to supervisor if unassigned
        if ($investigator->getSupervisor() === null) {
            $investigator->setSupervisor($supervisor);
        }

        $investigator->addTeam($team);
        $entityManager->flush();

        // Log team membership change
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

        // Log team membership change
        $auditService->logTeamMembershipChange($supervisor, $investigator, $team->getName(), 'removed');

        $this->addFlash('success', sprintf('%s removed from team %s.', $investigator->getEmail(), $team->getName()));

        return $this->redirectToRoute('app_supervisor_team_manage', ['id' => $team->getId()]);
    }

    #[Route('/supervisor/casework', name: 'app_supervisor_casework_index', methods: ['GET'])]
    public function caseworkIndex(EntityManagerInterface $entityManager): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();
        
        if (!$supervisor instanceof Supervisor) {
            throw $this->createAccessDeniedException('You are not a valid supervisor.');
        }
        
        // Only fetch cases created by this supervisor that are NOT archived
        $caseworks = $entityManager->getRepository(CaseWork::class)->createQueryBuilder('c')
            ->where('c.createdBy = :supervisor')
            ->andWhere('c.status != :status')
            ->setParameter('supervisor', $supervisor)
            ->setParameter('status', 'archived')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        // TEMPORARY: Add test cases if no cases exist
        if (empty($caseworks)) {
            $mockCases = [
                (object)[
                    'id' => 1,
                    'title' => 'Corporate Espionage Investigation',
                    'description' => 'Investigation into industrial secrets theft at TechCorp',
                    'status' => 'open',
                    'priority' => 'critical',
                    'createdAt' => new \DateTimeImmutable('2024-01-20'),
                    'assignedTeam' => (object)['name' => 'Special Investigations']
                ],
                (object)[
                    'id' => 2,
                    'title' => 'Financial Fraud Scheme',
                    'description' => 'Complex Ponzi scheme affecting multiple investors',
                    'status' => 'in_progress',
                    'priority' => 'high',
                    'createdAt' => new \DateTimeImmutable('2024-01-18'),
                    'assignedTeam' => (object)['name' => 'Financial Crimes Unit']
                ],
                (object)[
                    'id' => 3,
                    'title' => 'Drug Trafficking Network',
                    'description' => 'Major drug distribution ring operating across state lines',
                    'status' => 'open',
                    'priority' => 'critical',
                    'createdAt' => new \DateTimeImmutable('2024-01-15'),
                    'assignedTeam' => (object)['name' => 'Drug Enforcement']
                ]
            ];
            $caseworks = $mockCases;
        }

        return $this->render('supervisor/casework/index.html.twig', [
            'caseworks' => $caseworks,
        ]);
    }

    #[Route('/supervisor/archive', name: 'app_supervisor_archive_index', methods: ['GET'])]
    public function archiveIndex(EntityManagerInterface $entityManager): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();
        
        if (!$supervisor instanceof Supervisor) {
            throw $this->createAccessDeniedException('You are not a valid supervisor.');
        }

        // Only fetch cases created by this supervisor that ARE archived
        $caseworks = $entityManager->getRepository(CaseWork::class)->findBy(
            ['createdBy' => $supervisor, 'status' => 'archived'],
            ['updatedAt' => 'DESC']
        );

        // TEMPORARY: Add test cases if no cases exist
        if (empty($caseworks)) {
            $mockCases = [
                (object)[
                    'id' => 1,
                    'title' => 'Stolen Phone Investigation',
                    'description' => 'Investigation of stolen mobile device recovered with evidence',
                    'status' => 'archived',
                    'updatedAt' => new \DateTimeImmutable('2024-01-10'),
                    'assignedTeam' => (object)['name' => 'Digital Forensics']
                ],
                (object)[
                    'id' => 2,
                    'title' => 'Cold Case Homicide',
                    'description' => 'Archived cold case reopened and solved using new DNA evidence',
                    'status' => 'archived',
                    'updatedAt' => new \DateTimeImmutable('2024-01-08'),
                    'assignedTeam' => (object)['name' => 'Homicide Division']
                ]
            ];
            $caseworks = $mockCases;
        }

        return $this->render('supervisor/casework/archive.html.twig', [
            'caseworks' => $caseworks,
        ]);
    }

    #[Route('/supervisor/casework/new', name: 'app_supervisor_casework_new', methods: ['GET', 'POST'])]
    public function newCasework(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();
        
        if (!$supervisor instanceof Supervisor) {
            throw $this->createAccessDeniedException('You are not a valid supervisor.');
        }

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
        
        if ($casework->getCreatedBy() !== $supervisor) {
            throw $this->createAccessDeniedException('You are not authorized to view this case.');
        }

        return $this->render('supervisor/casework/show.html.twig', [
            'casework' => $casework,
        ]);
    }

    #[Route('/supervisor/casework/{id}/status', name: 'app_supervisor_casework_status', methods: ['POST'])]
    public function changestatus(CaseWork $casework, Request $request, EntityManagerInterface $entityManager, \App\Service\AuditService $auditService): Response
    {
        /** @var Supervisor $supervisor */
        $supervisor = $this->getUser();

        if ($casework->getCreatedBy() !== $supervisor) {
            throw $this->createAccessDeniedException('You are not authorized to change the status of this case.');
        }

        $oldstatus = $casework->getstatus();
        $newstatus = $request->request->get('status');
        $validstatuses = ['open', 'closed', 'archived'];

        if (in_array($newstatus, $validstatuses)) {
            $casework->setstatus($newstatus);
            $casework->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            // Log case status change
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
        
        if ($casework->getCreatedBy() !== $supervisor) {
            throw $this->createAccessDeniedException('You are not authorized to view this case.');
        }

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
