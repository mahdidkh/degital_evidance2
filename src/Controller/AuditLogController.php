<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuditLogController extends AbstractController
{
    #[Route('/audit/log', name: 'app_audit_log')]
    public function index(): Response
    {
        return $this->render('audit_log/index.html.twig', [
            'controller_name' => 'AuditLogController',
        ]);
    }
}
