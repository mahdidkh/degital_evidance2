<?php

namespace App\Controller;

use App\Entity\Evidence;
use App\Entity\ChainOfCustody;
use App\Form\EvidenceType;
use App\Repository\EvidenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


use App\Entity\CaseWork;


#[Route('/evidence')]
#[IsGranted('ROLE_INVESTIGATEUR')]
final class EvidenceController extends AbstractController
{

    #[Route('/new', name: 'app_evidence_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, \App\Service\AuditService $auditService): Response
    {
        $evidence = new Evidence();
        // verifier si un case_id est passé en paramètre pour lier l'evidence au CaseWork
        $caseId = $request->query->get('case_id');
        if ($caseId) {
            $caseWork = $entityManager->getRepository(CaseWork::class)->find($caseId);
            if ($caseWork) {
                if ($caseWork->getstatus() !== 'open') {
                    $this->addFlash('error', 'This case is closed or archived. You cannot add new evidence.');
                    return $this->redirectToRoute('app_investigateur_case_show', ['id' => $caseWork->getId()]);
                }
               $evidence->setCaseWork($caseWork);
            }
        }

        $form = $this->createForm(EvidenceType::class, $evidence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($evidence);
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
                $evidence->setStoredFilename($newFilename);

                // calculer le hash (sha256) du fichier nouvellement déplacé
                $filePath = $destination . '/' . $newFilename;
                if (file_exists($filePath)) {
                    $hash = hash_file('sha256', $filePath);
                    $evidence->setFileHash($hash);
                }
            }

            // remarque déjà mappée via le form vers $evidence->remarque

            $evidence->setUploadedBy($this->getUser());
            $entityManager->persist($evidence);
            $entityManager->flush();

            // Automate first Chain of Custody entry
            $chainEntry = new ChainOfCustody();
            $chainEntry->setAction('Initial Seizure');
            $chainEntry->setDescription('Evidence secured and hashed upon upload');
            $chainEntry->setDateUpdate(new \DateTime());
            $chainEntry->setPreviousHash($evidence->getFileHash()); // Same for first entry
            $chainEntry->setEvidence($evidence);
            $chainEntry->setUser($this->getUser());

            $entityManager->persist($chainEntry);
            $entityManager->flush();

            // Log evidence addition to audit system
            $user = $this->getUser();
            $caseWork = $evidence->getCaseWork();
            $auditService->logGenericEvent(
                'evidence_added',
                sprintf(
                    'User "%s %s" added new evidence "%s" to case "%s"',
                    $user->getFirstName(),
                    $user->getLastName(),
                    $evidence->gettitle() ?? $evidence->getStoredFilename(),
                    $caseWork ? $caseWork->gettitle() : 'N/A'
                ),
                $user,
                'info',
                [
                    'evidence_id' => $evidence->getId(),
                    'evidence_title' => $evidence->gettitle(),
                    'evidence_filename' => $evidence->getStoredFilename(),
                    'case_title' => $caseWork?->gettitle(),
                    'file_hash' => $evidence->getFileHash()
                ]
            );

            // Redirect to Case Explore if associated with a case
            if ($evidence->getCaseWork()) {
                 return $this->redirectToRoute('app_investigateur_case_explore', ['id' => $evidence->getCaseWork()->getId()], Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('app_evidence_index', [], Response::HTTP_SEE_OTHER);
        }




        return $this->render('evidence/new.html.twig', [
            'evidence' => $evidence,
            'form' => $form,
        ]);
    }


    #[Route('/{id}', name: 'app_evidence_delete', methods: ['POST'])]
    public function delete(Request $request, Evidence $evidence, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$evidence->getId(), $request->request->get('_token'))) {
            $stored = $evidence->getStoredFilename();
        if ($stored) {
            $path = $this->getParameter('evidence_directory').'/'.$stored;
            if (file_exists($path)) {
                @unlink($path);
            }
        }

            $entityManager->remove($evidence);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_evidence_index', [], Response::HTTP_SEE_OTHER);
    }
}
