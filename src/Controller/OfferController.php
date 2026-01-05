<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Entity\User;
use App\Form\OfferType;
use App\Repository\OfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

class OfferController extends AbstractController
{
    // MAIN SINGLE PAGE - Shows table with modals for all actions
    #[Route('/offers', name: 'offer_index')]
    public function index(OfferRepository $offerRepository): Response
    {
        $user = $this->getUser();

        // Role-based data filtering
        if ($this->isGranted('ROLE_RECRUITER')) {
            $offers = $offerRepository->findBy(['recruiter' => $user], ['createdAt' => 'DESC']);
        } else {
            $offers = $offerRepository->findBy([], ['createdAt' => 'DESC']);
        }

        // Create empty form for modal
        $form = $this->createForm(OfferType::class, new Offer());

        return $this->render('offer/index.html.twig', [
            'offers' => $offers,
            'form' => $form->createView(),
        ]);
    }

    // AJAX ENDPOINT: Create new offer (called from modal)
    #[Route('/offers/new', name: 'offer_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_RECRUITER');

        $offer = new Offer();
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $offer->setRecruiter($this->getUser());
            $entityManager->persist($offer);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Offer created successfully!',
                'offer' => [
                    'id' => $offer->getId(),
                    'title' => $offer->getTitle(),
                    'description' => $offer->getDescription(),
                    'skills' => $offer->getSkills(),
                    'location' => $offer->getLocation(),
                    'salary' => $offer->getSalary(),
                    'contractType' => $offer->getContractType(),
                    'createdAt' => $offer->getCreatedAt()->format('Y-m-d'),
                    'applicationsCount' => $offer->getApplications()->count(),
                ]
            ]);
        }

        // Return form errors
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        // Return field-specific errors if available
        if (empty($errors)) {
            foreach ($form as $child) {
                if (!$child->isValid()) {
                    foreach ($child->getErrors() as $error) {
                        $errors[] = $error->getMessage();
                    }
                }
            }
        }

        // failure response
        return new JsonResponse([
            'success' => false,
            'errors' => $errors ?: ['Form submission failed']
        ], 400);
    }

    // AJAX ENDPOINT: Get offer data for edit modal
    #[Route('/offers/{id}/edit', name: 'offer_edit', methods: ['GET'])]
    public function edit(Offer $offer): JsonResponse
    {
        // Security check
        if ($offer->getRecruiter() !== $this->getUser()) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Access denied'
            ], 403);
        }

        return new JsonResponse([
            'success' => true,
            'offer' => [
                'id' => $offer->getId(),
                'title' => $offer->getTitle(),
                'description' => $offer->getDescription(),
                'skills' => $offer->getSkills(),
                'location' => $offer->getLocation(),
                'salary' => $offer->getSalary(),
                'contractType' => $offer->getContractType(),
            ]
        ]);
    }

    // AJAX ENDPOINT: Update offer (called from edit modal)
    #[Route('/offers/{id}/update', name: 'offer_update', methods: ['PUT'])]
    public function update(Request $request, Offer $offer, EntityManagerInterface $entityManager): JsonResponse
    {
        // Security check
        if ($offer->getRecruiter() !== $this->getUser()) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Access denied'
            ], 403);
        }

        // Parse JSON data from AJAX request
        $data = json_decode($request->getContent(), true);

        // Validate required fields
        if (empty($data['title'])) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Title is required'
            ], 400);
        }

        if (empty($data['description'])) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Description is required'
            ], 400);
        }

        if (empty($data['skills'])) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Skills are required'
            ], 400);
        }

        // Manual update since we're not using form in AJAX
        $offer->setTitle($data['title']);
        $offer->setDescription($data['description']);
        $offer->setSkills($data['skills']);
        $offer->setLocation($data['location'] ?? $offer->getLocation());
        $offer->setSalary($data['salary'] ?? $offer->getSalary());
        $offer->setContractType($data['contractType'] ?? $offer->getContractType());

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Offer updated successfully!',
            'offer' => [
                'id' => $offer->getId(),
                'title' => $offer->getTitle(),
                'description' => $offer->getDescription(),
                'skills' => $offer->getSkills(),
                'location' => $offer->getLocation(),
                'salary' => $offer->getSalary(),
                'contractType' => $offer->getContractType(),
            ]
        ]);
    }

    // AJAX ENDPOINT: Delete offer with applications handling
    #[Route('/offers/{id}/delete', name: 'offer_delete', methods: ['DELETE'])]
    public function delete(Request $request, Offer $offer, EntityManagerInterface $entityManager, LoggerInterface $logger): JsonResponse
    {
        try {
            // Security check
            if ($offer->getRecruiter() !== $this->getUser()) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Access denied. You can only delete your own offers.'
                ], 403);
            }

            $hasApplications = $offer->getApplications()->count() > 0;
            $deleteMode = $request->query->get('deleteMode', 'offer_only');

            // Check if user wants to delete with applications
            if ($hasApplications && $deleteMode === 'offer_only') {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Cannot delete offer because it has ' . $offer->getApplications()->count() . ' application(s).',
                    'hasApplications' => true,
                    'applicationsCount' => $offer->getApplications()->count(),
                    'message' => 'Please delete applications first or use "Delete with Applications" option.'
                ], 400);
            }

            // Delete applications if requested
            if ($hasApplications && $deleteMode === 'with_applications') {
                $applications = $offer->getApplications();
                foreach ($applications as $application) {
                    $entityManager->remove($application);
                }
            }

            $entityManager->remove($offer);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => $hasApplications
                    ? 'Offer and all related applications deleted successfully!'
                    : 'Offer deleted successfully!'
            ]);

        } catch (\Exception $e) {
            $logger->error('Delete offer error: ' . $e->getMessage(), [
                'exception' => $e,
                'offer_id' => $offer->getId(),
                'user_id' => $this->getUser()->getId()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => 'An error occurred while deleting the offer: ' . $e->getMessage()
            ], 500);
        }
    }

    // AJAX ENDPOINT: Get offer details for show modal
    #[Route('/offers/{id}/show', name: 'offer_show', methods: ['GET'])]
    public function show(Offer $offer): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'offer' => [
                'id' => $offer->getId(),
                'title' => $offer->getTitle(),
                'description' => $offer->getDescription(),
                'skills' => $offer->getSkills(),
                'location' => $offer->getLocation() ?? 'Not specified',
                'salary' => $offer->getSalary() ?? 'Not specified',
                'contractType' => $offer->getContractType() ?? 'Full-time',
                'createdAt' => $offer->getCreatedAt()->format('F d, Y'),
                'recruiter' => $offer->getRecruiter()->getFullName(),
                'applicationsCount' => $offer->getApplications()->count(),
            ]
        ]);
    }

    // AJAX ENDPOINT: Search and filter offers
    #[Route('/offers/search', name: 'offer_search', methods: ['GET'])]
    public function search(Request $request, OfferRepository $offerRepository): JsonResponse
    {
        $searchTerm = $request->query->get('q', '');
        $skills = $request->query->get('skills', '');
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);

        $user = $this->getUser();

        if ($this->isGranted('ROLE_RECRUITER')) {
            $result = $offerRepository->searchForRecruiter($user, $searchTerm, $skills, $page, $limit);
        } else {
            $result = $offerRepository->search($searchTerm, $skills, $page, $limit);
        }

        $offersData = [];
        foreach ($result['offers'] as $offer) {
            $offersData[] = [
                'id' => $offer->getId(),
                'title' => $offer->getTitle(),
                'description' => $offer->getDescription(),
                'skills' => $offer->getSkills(),
                'location' => $offer->getLocation(),
                'salary' => $offer->getSalary(),
                'contractType' => $offer->getContractType(),
                'createdAt' => $offer->getCreatedAt()->format('M d, Y'),
                'applicationsCount' => $offer->getApplications()->count(),
            ];
        }

        return new JsonResponse([
            'success' => true,
            'offers' => $offersData,
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'totalPages' => $result['totalPages']
            ]
        ]);
    }
}
