<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function countActiveUsers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findForensicStaff(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.role IN (:roles)')
            ->setParameter('roles', ['ROLE_SUPERVISOR', 'ROLE_INVESTIGATEUR'])
            ->orderBy('u.last_name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
