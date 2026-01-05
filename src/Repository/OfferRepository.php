<?php

namespace App\Repository;

use App\Entity\Offer;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Offer>
 */
class OfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offer::class);
    }

     public function search(string $searchTerm = '', string $skills = '', int $page = 1, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC');

        if (!empty($searchTerm)) {
            $qb->andWhere('o.title LIKE :searchTerm OR o.description LIKE :searchTerm')
               ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        if (!empty($skills)) {
            $skillsArray = explode(',', $skills);
            foreach ($skillsArray as $index => $skill) {
                $skill = trim($skill);
                if (!empty($skill)) {
                    $qb->andWhere('o.skills LIKE :skill' . $index)
                       ->setParameter('skill' . $index, '%' . $skill . '%');
                }
            }
        }

        // Pagination
        $total = count($qb->getQuery()->getResult());
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return [
            'offers' => $qb->getQuery()->getResult(),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit)
        ];
    }

    public function searchForRecruiter(User $recruiter, string $searchTerm = '', string $skills = '', int $page = 1, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.recruiter = :recruiter')
            ->setParameter('recruiter', $recruiter)
            ->orderBy('o.createdAt', 'DESC');

        if (!empty($searchTerm)) {
            $qb->andWhere('o.title LIKE :searchTerm OR o.description LIKE :searchTerm')
               ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        if (!empty($skills)) {
            $skillsArray = explode(',', $skills);
            foreach ($skillsArray as $index => $skill) {
                $skill = trim($skill);
                if (!empty($skill)) {
                    $qb->andWhere('o.skills LIKE :skill' . $index)
                       ->setParameter('skill' . $index, '%' . $skill . '%');
                }
            }
        }

        // Pagination
        $total = count($qb->getQuery()->getResult());
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return [
            'offers' => $qb->getQuery()->getResult(),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit)
        ];
    }


//    /**
//     * @return Offer[] Returns an array of Offer objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Offer
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
