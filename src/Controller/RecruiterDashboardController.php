<?php

namespace App\Controller;

use App\Repository\ApplicationRepository;
use App\Repository\OfferRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RecruiterDashboardController extends AbstractController
{
    #[Route('/recruiter/dashboard', name: 'recruiter_dashboard')]
    public function index(OfferRepository $offerRepository,ApplicationRepository $applicationRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RECRUITER');

        $user = $this->getUser();

        $offers = $offerRepository->findBy(
            ['recruiter' => $user],
            ['createdAt' => 'DESC'],
        );
        $allApplications = [];
        $stats = [
            'total_offers' => count($offers),
            'total_applications' => 0,
            'pending' => 0,
            'accepted' => 0,
            'rejected' => 0,
        ];
        foreach($offers as $offer){
            $applications = $applicationRepository->findBy(
                ['offer' => $offer],
                ['createdAt' => 'DESC'],
            );
            $allApplications = array_merge($allApplications,$applications);
            foreach($applications as $app){
                $stats['total_applications']++;
                $status = strtolower($app->getStatus());
                        if (isset($stats[$status])) {
                    $stats[$status]++;
                }
            }
        }
        return $this->render('recruiter_dashboard/index.html.twig', [
            'offers' => $offers,
            'applications' => $allApplications,
            'stats' => $stats,
        ]);
    }
}
