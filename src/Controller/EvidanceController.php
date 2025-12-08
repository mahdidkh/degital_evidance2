<?php

namespace App\Controller;

use App\Entity\Evidance;
use App\Form\EvidanceType;
use App\Repository\EvidanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/evidance')]
final class EvidanceController extends AbstractController
{
    #[Route(name: 'app_evidance_index', methods: ['GET'])]
    public function index(EvidanceRepository $evidanceRepository): Response
    {
        return $this->render('evidance/index.html.twig', [
            'evidances' => $evidanceRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_evidance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $evidance = new Evidance();
        $form = $this->createForm(EvidanceType::class, $evidance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($evidance);
            $entityManager->flush();

            return $this->redirectToRoute('app_evidance_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evidance/new.html.twig', [
            'evidance' => $evidance,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_evidance_show', methods: ['GET'])]
    public function show(Evidance $evidance): Response
    {
        return $this->render('evidance/show.html.twig', [
            'evidance' => $evidance,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_evidance_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evidance $evidance, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EvidanceType::class, $evidance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_evidance_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evidance/edit.html.twig', [
            'evidance' => $evidance,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_evidance_delete', methods: ['POST'])]
    public function delete(Request $request, Evidance $evidance, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$evidance->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($evidance);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_evidance_index', [], Response::HTTP_SEE_OTHER);
    }
}
