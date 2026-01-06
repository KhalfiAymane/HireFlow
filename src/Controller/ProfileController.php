<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', '✅ Your profile has been updated successfully!');

            // Redirect to avoid form resubmission
            return $this->redirectToRoute('profile_edit');
        }

        return $this->render('profile/index.html.twig', [
            'profileForm' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/profile/delete', name: 'profile_delete', methods: ['POST'])]
    public function deleteAccount(
        Request $request,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Verify CSRF token for security
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-account', $submittedToken)) {
            $this->addFlash('error', 'Invalid security token. Please try again.');
            return $this->redirectToRoute('profile_edit');
        }

        try {
            // First, log out the user
            $tokenStorage->setToken(null);
            $request->getSession()->invalidate();

            // Then delete the user
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Your account has been deleted successfully. We hope to see you again!');
            return $this->redirectToRoute('app_home');
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while deleting your account. Please try again.');
            return $this->redirectToRoute('profile_edit');
        }
    }
}
