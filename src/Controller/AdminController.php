<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use App\Service\AnalyticsService;
use App\Service\AuditService;
use App\Entity\User;
use App\Entity\CaseWork;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(AnalyticsService $analyticsService): Response
    {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");

        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isVerified()) {
            return $this->render("admin/please-verify-email.html.twig");
        }

        return $this->render("admin/index.html.twig", [
            'stats' => $analyticsService->getSystemStats(),
        ]);
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function users(\App\Repository\UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");

        return $this->render("admin/users.html.twig", [
            'users' => $userRepository->findForensicStaff(),
        ]);
    }

    #[Route('/admin/user/edit/{id}', name: 'app_admin_user_edit')]
    public function editUser(
        int $id,
        \App\Repository\UserRepository $userRepository,
        \Symfony\Component\HttpFoundation\Request $request,
        \Doctrine\ORM\EntityManagerInterface $entityManager,
        AuditService $auditService
    ): Response {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");

        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        // Store original values for audit logging
        $originalRole = $user->getRole();
        $originalActive = $user->isActive();

        $form = $this->createForm(\App\Form\UserEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush(); //execute the update 

            // Log profile changes
            $changes = [];
            if ($originalRole !== $user->getRole()) {
                $changes[] = sprintf('role changed from "%s" to "%s"', $originalRole, $user->getRole());
            }
            if ($originalActive !== $user->isActive()) {
                $changes[] = sprintf('active status changed to "%s"', $user->isActive() ? 'active' : 'inactive');
            }

            if (!empty($changes)) {
                /** @var User $currentUser */
                $currentUser = $this->getUser();
                $auditService->logProfileChange($currentUser, $user, implode(', ', $changes));
            }

            $this->addFlash('success', 'User updated successfully.');
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/user_edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/admin/cases', name: 'app_admin_cases')]
    public function cases(\App\Repository\CaseWorkRepository $caseWorkRepository): Response
    {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");

        return $this->render("admin/cases.html.twig", [
            'cases' => $caseWorkRepository->findAll(),
        ]);
    }

    #[Route('/admin/case/{id}/explore', name: 'app_admin_case_explore')]
    public function explore(CaseWork $casework): Response
    {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");

        return $this->render("admin/case_explore.html.twig", [
            'casework' => $casework,
        ]);
    }

    #[Route('/admin/audit', name: 'app_admin_audit')]
    public function audit(\App\Repository\AuditLogRepository $auditLogRepository): Response
    {
        $this->denyAccessUnlessGranted("IS_AUTHENTICATED_FULLY");

        return $this->render("admin/audit.html.twig", [
            'logs' => $auditLogRepository->findRecent(200),
        ]);
    }
}
