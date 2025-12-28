<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use App\Entity\Evidence;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack
    ) {}

    /**
     * Log a successful login event
     */
    public function logLogin(User $user): void
    {
        $this->createAuditLog(
            eventType: 'login',
            description: sprintf('User "%s %s" logged in successfully', $user->getFirstName(), $user->getLastName()),
            user: $user,
            severity: 'info'
        );
    }

    /**
     * Log a logout event
     */
    public function logLogout(User $user): void
    {
        $this->createAuditLog(
            eventType: 'logout',
            description: sprintf('User "%s %s" logged out', $user->getFirstName(), $user->getLastName()),
            user: $user,
            severity: 'info'
        );
    }

    /**
     * Log profile changes
     */
    public function logProfileChange(User $actor, User $target, string $changes): void
    {
        $this->createAuditLog(
            eventType: 'profile_change',
            description: sprintf(
                'User "%s %s" modified profile of "%s %s": %s',
                $actor->getFirstName(),
                $actor->getLastName(),
                $target->getFirstName(),
                $target->getLastName(),
                $changes
            ),
            user: $actor,
            targetUser: $target,
            severity: 'warning',
            metadata: ['changes' => $changes]
        );
    }

    /**
     * Log evidence integrity check
     */
    public function logIntegrityCheck(User $user, Evidence $evidence, string $status, array $details = []): void
    {
        $severity = $status === 'tampered' ? 'critical' : 'info';
        
        $this->createAuditLog(
            eventType: 'integrity_check',
            description: sprintf(
                'User "%s %s" performed integrity check on evidence "%s" (Case: %s). status: %s',
                $user->getFirstName(),
                $user->getLastName(),
                $evidence->gettitle() ?? $evidence->getStoredFilename(),
                $evidence->getCaseWork()?->gettitle() ?? 'N/A',
                strtoupper($status)
            ),
            user: $user,
            severity: $severity,
            metadata: array_merge([
                'evidence_id' => $evidence->getId(),
                'evidence_title' => $evidence->gettitle(),
                'evidence_filename' => $evidence->getStoredFilename(),
                'status' => $status,
                'case_title' => $evidence->getCaseWork()?->gettitle()
            ], $details)
        );
    }

    /**
     * Log tampered evidence alert
     */
    public function logTamperedAlert(User $user, Evidence $evidence, array $details): void
    {
        $this->createAuditLog(
            eventType: 'tampered_alert',
            description: sprintf(
                '⚠️ CRITICAL: Evidence tampering detected! Evidence "%s" (Case: %s) has been compromised. Detected by: %s %s',
                $evidence->gettitle() ?? $evidence->getStoredFilename(),
                $evidence->getCaseWork()?->gettitle() ?? 'N/A',
                $user->getFirstName(),
                $user->getLastName()
            ),
            user: $user,
            severity: 'critical',
            metadata: array_merge([
                'evidence_id' => $evidence->getId(),
                'evidence_title' => $evidence->gettitle(),
                'evidence_filename' => $evidence->getStoredFilename(),
                'case_title' => $evidence->getCaseWork()?->gettitle()
            ], $details)
        );
    }

    /**
     * Log case status change
     */
    public function logCasestatusChange(User $user, string $caseNumber, string $oldstatus, string $newstatus): void
    {
        $this->createAuditLog(
            eventType: 'case_status_change',
            description: sprintf(
                'User "%s %s" changed case "%s" status from "%s" to "%s"',
                $user->getFirstName(),
                $user->getLastName(),
                $caseNumber,
                $oldstatus,
                $newstatus
            ),
            user: $user,
            severity: 'warning',
            metadata: [
                'case_number' => $caseNumber,
                'old_status' => $oldstatus,
                'new_status' => $newstatus
            ]
        );
    }

    /**
     * Log team membership changes
     */
    public function logTeamMembershipChange(User $supervisor, User $investigator, string $teamName, string $action): void
    {
        $this->createAuditLog(
            eventType: 'team_membership',
            description: sprintf(
                'Supervisor "%s %s" %s investigator "%s %s" %s team "%s"',
                $supervisor->getFirstName(),
                $supervisor->getLastName(),
                $action === 'added' ? 'added' : 'removed',
                $investigator->getFirstName(),
                $investigator->getLastName(),
                $action === 'added' ? 'to' : 'from',
                $teamName
            ),
            user: $supervisor,
            targetUser: $investigator,
            severity: 'info',
            metadata: [
                'team_name' => $teamName,
                'action' => $action
            ]
        );
    }

    /**
     * Log generic event
     */
    public function logGenericEvent(
        string $eventType,
        string $description,
        User $user,
        string $severity = 'info',
        array $metadata = []
    ): void {
        $this->createAuditLog(
            eventType: $eventType,
            description: $description,
            user: $user,
            severity: $severity,
            metadata: $metadata
        );
    }

    /**
     * Create and persist an audit log entry
     */
    private function createAuditLog(
        string $eventType,
        string $description,
        User $user,
        ?User $targetUser = null,
        string $severity = 'info',
        array $metadata = []
    ): void {
        $request = $this->requestStack->getCurrentRequest();
        
        $auditLog = new AuditLog();
        $auditLog->setEventType($eventType);
        $auditLog->setEventDescription($description);
        $auditLog->setUser($user);
        $auditLog->setTargetUser($targetUser);
        $auditLog->setSeverity($severity);
        $auditLog->setMetadata($metadata);
        
        if ($request) {
            $auditLog->setIpAddress($request->getClientIp());
            $auditLog->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->entityManager->persist($auditLog);
        $this->entityManager->flush();
    }
}
