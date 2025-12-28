<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile_edit')]
    public function edit(): Response
    {
        return $this->render('profile/index.html.twig', [
            'message'=> 'profile page will be implemented soon'
        ]);
    }
}
