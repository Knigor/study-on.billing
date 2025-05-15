<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findByFilters(User $user, array $filters): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.owner = :user')
            ->setParameter('user', $user);

        if (!empty($filters['type'])) {
            $qb->andWhere('t.operationType = :type')
                ->setParameter('type', $filters['type'] === 'payment' ? 1 : 0);
        }

        if (!empty($filters['course_code'])) {
            $qb->join('t.course', 'c')
                ->andWhere('c.code = :code')
                ->setParameter('code', $filters['course_code']);
        }

        if (!empty($filters['skip_expired'])) {
            $qb->andWhere('t.validUntil IS NULL OR t.validUntil > :now')
                ->setParameter('now', new \DateTime());
        }

        return $qb->orderBy('t.transactionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }


}
