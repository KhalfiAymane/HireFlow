<?php
// src/Repository/ApplicationRepository.php

namespace App\Repository;

use App\Entity\Application;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Application>
 */
class ApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Application::class);
    }

    // Find applications by recruiter (applications for their offers)
    public function findByRecruiter(User $recruiter): array
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.offer', 'o')
            ->where('o.recruiter = :recruiter')
            ->setParameter('recruiter', $recruiter)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Search applications for recruiter
    public function searchForRecruiter(
        User $recruiter,
        string $searchTerm = '',
        string $status = '',
        ?int $offerId = null,
        int $page = 1,
        int $limit = 10
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->innerJoin('a.offer', 'o')
            ->innerJoin('a.candidate', 'c')
            ->where('o.recruiter = :recruiter')
            ->setParameter('recruiter', $recruiter)
            ->orderBy('a.createdAt', 'DESC');

        if (!empty($searchTerm)) {
            $qb->andWhere('c.fullName LIKE :searchTerm OR c.email LIKE :searchTerm OR a.coverLetter LIKE :searchTerm')
               ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        if (!empty($status)) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        if ($offerId) {
            $qb->andWhere('o.id = :offerId')
               ->setParameter('offerId', $offerId);
        }

        // Get total count
        $totalQb = clone $qb;
        $total = count($totalQb->getQuery()->getResult());

        // Apply pagination
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return [
            'applications' => $qb->getQuery()->getResult(),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit)
        ];
    }

    // Search applications for candidate
    public function searchForCandidate(
        User $candidate,
        string $searchTerm = '',
        string $status = '',
        int $page = 1,
        int $limit = 10
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->innerJoin('a.offer', 'o')
            ->where('a.candidate = :candidate')
            ->setParameter('candidate', $candidate)
            ->orderBy('a.createdAt', 'DESC');

        if (!empty($searchTerm)) {
            $qb->andWhere('o.title LIKE :searchTerm OR o.description LIKE :searchTerm OR a.coverLetter LIKE :searchTerm')
               ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        if (!empty($status)) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        // Get total count
        $totalQb = clone $qb;
        $total = count($totalQb->getQuery()->getResult());

        // Apply pagination
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return [
            'applications' => $qb->getQuery()->getResult(),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit)
        ];
    }

    // Get recruiter statistics
    public function getRecruiterStats(User $recruiter): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select([
                'COUNT(a.id) as total',
                'SUM(CASE WHEN a.status = :pending THEN 1 ELSE 0 END) as pending',
                'SUM(CASE WHEN a.status = :accepted THEN 1 ELSE 0 END) as accepted',
                'SUM(CASE WHEN a.status = :rejected THEN 1 ELSE 0 END) as rejected',
            ])
            ->innerJoin('a.offer', 'o')
            ->where('o.recruiter = :recruiter')
            ->setParameters([
                'recruiter' => $recruiter,
                'pending' => Application::STATUS_PENDING,
                'accepted' => Application::STATUS_ACCEPTED,
                'rejected' => Application::STATUS_REJECTED,
            ]);

        $result = $qb->getQuery()->getSingleResult();

        return [
            'total' => (int) $result['total'],
            'pending' => (int) $result['pending'],
            'accepted' => (int) $result['accepted'],
            'rejected' => (int) $result['rejected'],
        ];
    }

    // Get candidate statistics
    public function getCandidateStats(User $candidate): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select([
                'COUNT(a.id) as total',
                'SUM(CASE WHEN a.status = :pending THEN 1 ELSE 0 END) as pending',
                'SUM(CASE WHEN a.status = :accepted THEN 1 ELSE 0 END) as accepted',
                'SUM(CASE WHEN a.status = :rejected THEN 1 ELSE 0 END) as rejected',
            ])
            ->where('a.candidate = :candidate')
            ->setParameters([
                'candidate' => $candidate,
                'pending' => Application::STATUS_PENDING,
                'accepted' => Application::STATUS_ACCEPTED,
                'rejected' => Application::STATUS_REJECTED,
            ]);

        $result = $qb->getQuery()->getSingleResult();

        return [
            'total' => (int) $result['total'],
            'pending' => (int) $result['pending'],
            'accepted' => (int) $result['accepted'],
            'rejected' => (int) $result['rejected'],
        ];
    }

    // Get recent applications for recruiter
    public function findRecentForRecruiter(User $recruiter, int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.offer', 'o')
            ->where('o.recruiter = :recruiter')
            ->setParameter('recruiter', $recruiter)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    // Get recent applications for candidate
    public function findRecentForCandidate(User $candidate, int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.candidate = :candidate')
            ->setParameter('candidate', $candidate)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
