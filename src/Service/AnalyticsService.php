<?php

namespace App\Service;

use App\Repository\CaseWorkRepository;
use App\Repository\EvidenceRepository;
use App\Repository\UserRepository;
use App\Repository\ChainOfCustodyRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AnalyticsService
{
    public function __construct(
        private CaseWorkRepository $caseWorkRepository,
        private EvidenceRepository $evidenceRepository,
        private UserRepository $userRepository,
        private ChainOfCustodyRepository $chainOfCustodyRepository,
        private ParameterBagInterface $parameterBag
    ) {}

    public function getSystemStats(): array
    {
        return [
            'total_cases' => $this->caseWorkRepository->count([]),
            'total_evidence' => $this->evidenceRepository->count([]),
            'active_users' => $this->userRepository->countActiveUsers(),
            'storage_usage' => $this->getStorageUsage(),
            'recent_activities' => $this->chainOfCustodyRepository->getRecentActivities(8),
        ];
    }

    private function getStorageUsage(): string
    {
        $directory = $this->parameterBag->get('evidence_directory');
        if (!is_dir($directory)) {
            return '0 B';
        }

        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $this->formatBytes($size);
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
