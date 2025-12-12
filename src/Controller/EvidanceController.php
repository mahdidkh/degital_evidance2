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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

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
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $evidance = new Evidance();
        $form = $this->createForm(EvidanceType::class, $evidance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($evidance);
            $entityManager->flush();
            $uploadedFile = $form->get('evidenceFile')->getData();
               if ($uploadedFile) {
                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                // safe filename
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();

                // paramètre défini dans services.yaml
                $destination = $this->getParameter('evidence_directory');

                try {
                    $uploadedFile->move($destination, $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du fichier.');
                    // -> tu peux logger l'exception
                }

                // remplir l'entité
                $evidance->setStoredFilename($newFilename);

                // calculer le hash (sha256) du fichier nouvellement déplacé
                $filePath = $destination . '/' . $newFilename;
                if (file_exists($filePath)) {
                    $hash = hash_file('sha256', $filePath);
                    $evidance->setFileHash($hash);
                }
            }

            // remarque déjà mappée via le form vers $evidance->remarque

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
        if ($this->isCsrfTokenValid('delete'.$evidance->getId(), $request->request->get('_token'))) {
            $stored = $evidance->getStoredFilename();
        if ($stored) {
            $path = $this->getParameter('evidence_directory').'/'.$stored;
            if (file_exists($path)) {
                @unlink($path);
            }
        }

            $entityManager->remove($evidance);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_evidance_index', [], Response::HTTP_SEE_OTHER);
    }
}
