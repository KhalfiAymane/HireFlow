<?php

namespace App\Controller;

use App\Repository\ApplicationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CandidateDashboardController extends AbstractController
{
    #[Route('/candidate/dashboard', name: 'candidate_dashboard')]
    public function index(ApplicationRepository $applicationRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CANDIDATE');
        $user = $this->getUser();
        $applications = $applicationRepository->findBy(
            ['candidate' => $user],
            ['createdAt' => "DESC"]
        );
        $stats = [
            "total"=>count($applications),
            "pending"=>0,
            "accepted"=>0,
            "rejected"=>0
        ];
        foreach ($applications as $app) {
            $status = strtolower($app->getStatus());
            if (isset($stats[$status])){
                $stats[$status]++;
            }
        }
        return $this->render('candidate_dashboard/index.html.twig', [
            'applications'=> $applications,
            'stats' => $stats,
        ]);
    }
}
