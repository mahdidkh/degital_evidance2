<?php

namespace App\Controller;

use App\Entity\ChainOfCoady;
use App\Form\ChainOfCoadyType;
use App\Repository\ChainOfCoadyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/chain/of/coady')]
final class ChainOfCoadyController extends AbstractController
{
    #[Route(name: 'app_chain_of_coady_index', methods: ['GET'])]
    public function index(ChainOfCoadyRepository $chainOfCoadyRepository): Response
    {
        return $this->render('chain_of_coady/index.html.twig', [
            'chain_of_coadies' => $chainOfCoadyRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_chain_of_coady_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $chainOfCoady = new ChainOfCoady();
        $form = $this->createForm(ChainOfCoadyType::class, $chainOfCoady);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($chainOfCoady);
            $entityManager->flush();

            return $this->redirectToRoute('app_chain_of_coady_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('chain_of_coady/new.html.twig', [
            'chain_of_coady' => $chainOfCoady,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_chain_of_coady_show', methods: ['GET'])]
    public function show(ChainOfCoady $chainOfCoady): Response
    {
        return $this->render('chain_of_coady/show.html.twig', [
            'chain_of_coady' => $chainOfCoady,
        ]);
    }

   
   
}
