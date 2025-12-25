<?php

namespace App\Controller;

use App\Entity\CaseWork;
use App\Form\CaseWorkType;
use App\Repository\CaseWorkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;



#[Route('/case/work')]
final class CaseWorkController extends AbstractController
{
    #[Route(name: 'app_case_work_index', methods: ['GET'])]
    public function index(CaseWorkRepository $caseWorkRepository): Response
    {
        $user = $this->getUser();
        $cases = [];

        // Check user role and filter cases accordingly
        if ($this->isGranted('ROLE_ADMIN')) {
            // Admins can see all cases
            $cases = $caseWorkRepository->findAll();
        } elseif ($this->isGranted('ROLE_SUPERVISOR')) {
            // Supervisors see cases they created
            $cases = $caseWorkRepository->findBy(['createdBy' => $user], ['createdAt' => 'DESC']);
        } elseif ($this->isGranted('ROLE_INVESTIGATEUR')) {
            // Investigators only see cases assigned to their teams
            $teams = $user->getTeams();
            foreach ($teams as $team) {
                foreach ($team->getCaseWorks() as $casework) {
                    $cases[] = $casework;
                }
            }
            // Sort by creation date (newest first)
            usort($cases, function($a, $b) {
                return $b->getCreatedAt() <=> $a->getCreatedAt();
            });
        }

        return $this->render('case_work/index.html.twig', [
            'case_works' => $cases,
        ]);
    }

    #[Route('/new', name: 'app_case_work_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $caseWork = new CaseWork();
        $form = $this->createForm(CaseWorkType::class, $caseWork);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($caseWork);
            $entityManager->flush();

            return $this->redirectToRoute('app_case_work_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('case_work/new.html.twig', [
            'case_work' => $caseWork,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_case_work_show', methods: ['GET'])]
    public function show(CaseWork $caseWork): Response
    {
        return $this->render('case_work/show.html.twig', [
            'case_work' => $caseWork,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_case_work_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CaseWork $caseWork, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CaseWorkType::class, $caseWork);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_case_work_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('case_work/edit.html.twig', [
            'case_work' => $caseWork,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_case_work_delete', methods: ['POST'])]
    public function delete(Request $request, CaseWork $caseWork, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$caseWork->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($caseWork);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_case_work_index', [], Response::HTTP_SEE_OTHER);
    }


    
  
}
