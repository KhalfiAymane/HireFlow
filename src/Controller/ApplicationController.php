<?php
// src/Controller/ApplicationController.php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\Offer;
use App\Repository\ApplicationRepository;
use App\Repository\OfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/applications')]
class ApplicationController extends AbstractController
{
    // MAIN PAGE - Shows applications table
    #[Route('/', name: 'application_index')]
    public function index(ApplicationRepository $applicationRepository, OfferRepository $offerRepository): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_RECRUITER')) {
            // Recruiters see applications for their offers
            $applications = $applicationRepository->findByRecruiter($user);
            $offers = $offerRepository->findBy(['recruiter' => $user], ['createdAt' => 'DESC']);
        } else {
            // Candidates see their own applications
            $applications = $applicationRepository->findBy(['candidate' => $user], ['createdAt' => 'DESC']);
            $offers = [];
        }

        return $this->render('application/index.html.twig', [
            'applications' => $applications,
            'offers' => $offers,
            'user' => $user,
        ]);
    }

    // AJAX ENDPOINT: Get application details for view modal
    #[Route('/{id}/show', name: 'application_show', methods: ['GET'])]
    public function show(Application $application): JsonResponse
    {
        // Security check
        if ($this->isGranted('ROLE_RECRUITER')) {
            if ($application->getOffer()->getRecruiter() !== $this->getUser()) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Access denied'
                ], 403);
            }
        } else {
            if ($application->getCandidate() !== $this->getUser()) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Access denied'
                ], 403);
            }
        }

        return new JsonResponse([
            'success' => true,
            'application' => [
                'id' => $application->getId(),
                'candidateName' => $application->getCandidate()->getFullName(),
                'candidateEmail' => $application->getCandidate()->getEmail(),
                'candidatePhone' => $application->getCandidate()->getPhone(),
                'offerTitle' => $application->getOffer()->getTitle(),
                'coverLetter' => $application->getCoverLetter(),
                'resume' => $application->getResume(),
                'resumePath' => $application->getResumePath(),
                'status' => $application->getStatus(),
                'statusText' => $application->getStatusText(),
                'createdAt' => $application->getCreatedAt()->format('F d, Y H:i'),
                'notes' => $application->getNotes(),
            ]
        ]);
    }

    // AJAX ENDPOINT: Create new application (candidate applies to offer)
    #[Route('/new', name: 'application_new', methods: ['POST'])]
    #[IsGranted('ROLE_CANDIDATE')]
    public function new(Request $request, EntityManagerInterface $entityManager, OfferRepository $offerRepository): JsonResponse
    {
        $user = $this->getUser();

        try {
            // DEBUG: Log what we're receiving
            error_log('Received application submission:');
            error_log('Offer ID: ' . $request->request->get('offerId'));
            error_log('Cover letter length: ' . strlen($request->request->get('coverLetter', '')));
            error_log('Has file: ' . ($request->files->get('resume') ? 'YES' : 'NO'));

            if ($request->files->get('resume')) {
                error_log('File name: ' . $request->files->get('resume')->getClientOriginalName());
                error_log('File size: ' . $request->files->get('resume')->getSize());
                error_log('File type: ' . $request->files->get('resume')->getMimeType());
            }

            $offerId = $request->request->get('offerId');
            $coverLetter = $request->request->get('coverLetter');
            $resumeFile = $request->files->get('resume');

            // Validate required fields
            if (!$offerId) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Offer ID is required'
                ], 400);
            }

            if (!$coverLetter || trim($coverLetter) === '') {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Cover letter is required'
                ], 400);
            }

            if (!$resumeFile) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Resume file is required'
                ], 400);
            }

            // Validate file
            $allowedMimeTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
            if (!in_array($resumeFile->getMimeType(), $allowedMimeTypes)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Invalid file type. Allowed: PDF, DOC, DOCX, TXT'
                ], 400);
            }

            if ($resumeFile->getSize() > 5 * 1024 * 1024) { // 5MB
                return new JsonResponse([
                    'success' => false,
                    'error' => 'File size must be less than 5MB'
                ], 400);
            }

            // Find offer
            $offer = $offerRepository->find($offerId);
            if (!$offer) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Offer not found'
                ], 404);
            }

            // Check if already applied
            $existingApplication = $entityManager->getRepository(Application::class)
                ->findOneBy(['candidate' => $user, 'offer' => $offer]);

            if ($existingApplication) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'You have already applied to this offer'
                ], 400);
            }

            // Generate unique filename (SIMPLIFIED VERSION - no transliterator)
            $originalFilename = pathinfo($resumeFile->getClientOriginalName(), PATHINFO_FILENAME);

            // Simple sanitization without transliterator
            $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalFilename);
            $safeFilename = strtolower($safeFilename);

            // Add timestamp and unique ID for uniqueness
            $newFilename = $safeFilename . '_' . time() . '_' . uniqid() . '.' . $resumeFile->guessExtension();

            // Get uploads directory
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/resumes';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            // Move the file to uploads directory
            $resumeFile->move($uploadsDir, $newFilename);

            // Create application
            $application = new Application();
            $application->setCandidate($user);
            $application->setOffer($offer);
            $application->setCoverLetter($coverLetter);
            $application->setResume($newFilename);
            $application->setStatus(Application::STATUS_PENDING);
            $application->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($application);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Application submitted successfully!',
                'application' => [
                    'id' => $application->getId(),
                    'offerTitle' => $application->getOffer()->getTitle(),
                    'createdAt' => $application->getCreatedAt()->format('Y-m-d H:i:s'),
                ]
            ]);

        } catch (\Exception $e) {
            // Log the full error
            error_log('Application submission error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            return new JsonResponse([
                'success' => false,
                'error' => 'An error occurred: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString() // Remove this in production
            ], 500);
        }
    }
    // AJAX ENDPOINT: Get application data for edit modal (candidates only)
    #[Route('/{id}/edit', name: 'application_edit', methods: ['GET'])]
    #[IsGranted('ROLE_CANDIDATE')]
    public function edit(Application $application): JsonResponse
    {
        // Security check - only candidate who owns the application can edit
        if ($application->getCandidate() !== $this->getUser()) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Access denied'
            ], 403);
        }

        // Can only edit pending applications
        if ($application->getStatus() !== Application::STATUS_PENDING) {
            return new JsonResponse([
                'success' => false,
                'error' => 'You can only edit pending applications'
            ], 400);
        }

        return new JsonResponse([
            'success' => true,
            'application' => [
                'id' => $application->getId(),
                'offerTitle' => $application->getOffer()->getTitle(),
                'coverLetter' => $application->getCoverLetter(),
                'resume' => $application->getResume(),
            ]
        ]);
    }

    // AJAX ENDPOINT: Update application (candidates only)
    #[Route('/{id}/update', name: 'application_update', methods: ['PUT'])]
    #[IsGranted('ROLE_CANDIDATE')]
    public function update(Request $request, Application $application, EntityManagerInterface $entityManager): JsonResponse
    {
        // Security check - only candidate who owns the application can update
        if ($application->getCandidate() !== $this->getUser()) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Access denied'
            ], 403);
        }

        // Can only update pending applications
        if ($application->getStatus() !== Application::STATUS_PENDING) {
            return new JsonResponse([
                'success' => false,
                'error' => 'You can only update pending applications'
            ], 400);
        }

        try {
            $coverLetter = $request->request->get('coverLetter');
            $resumeFile = $request->files->get('resume');

            // Validate required fields
            if (!$coverLetter || trim($coverLetter) === '') {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Cover letter is required'
                ], 400);
            }

            // Update cover letter
            $application->setCoverLetter($coverLetter);

            // Update resume file if provided
            if ($resumeFile) {
                // Validate file
                $allowedMimeTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
                if (!in_array($resumeFile->getMimeType(), $allowedMimeTypes)) {
                    return new JsonResponse([
                        'success' => false,
                        'error' => 'Invalid file type. Allowed: PDF, DOC, DOCX, TXT'
                    ], 400);
                }

                if ($resumeFile->getSize() > 5 * 1024 * 1024) { // 5MB
                    return new JsonResponse([
                        'success' => false,
                        'error' => 'File size must be less than 5MB'
                    ], 400);
                }

                // Delete old resume file if exists
                $oldResume = $application->getResume();
                if ($oldResume) {
                    $oldFilePath = $this->getParameter('kernel.project_dir') . '/public/uploads/resumes/' . $oldResume;
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
                // Generate unique filename
                $originalFilename = pathinfo($resumeFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalFilename);
                $safeFilename = strtolower($safeFilename);
                $newFilename = $safeFilename . '_' . time() . '_' . uniqid() . '.' . $resumeFile->guessExtension();
                // Get uploads directory
                $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/resumes';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }

                // Move the file to uploads directory
                $resumeFile->move($uploadsDir, $newFilename);
                $application->setResume($newFilename);
            }

            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Application updated successfully!',
                'application' => [
                    'id' => $application->getId(),
                    'coverLetter' => $application->getCoverLetter(),
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    // AJAX ENDPOINT: Delete application (candidates only)
    #[Route('/{id}/delete', name: 'application_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_CANDIDATE')]
    public function delete(Application $application, EntityManagerInterface $entityManager): JsonResponse
    {
        // Security check - only candidate who owns the application can delete
        if ($application->getCandidate() !== $this->getUser()) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Access denied'
            ], 403);
        }

        // Can only delete pending applications
        if ($application->getStatus() !== Application::STATUS_PENDING) {
            return new JsonResponse([
                'success' => false,
                'error' => 'You can only delete pending applications'
            ], 400);
        }

        try {
            // Delete resume file if exists
            $resumeFile = $application->getResume();
            if ($resumeFile) {
                $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/resumes/' . $resumeFile;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            // Remove application
            $entityManager->remove($application);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Application deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
    // AJAX ENDPOINT: Update application status (Accept/Reject)
    #[Route('/{id}/status', name: 'application_status', methods: ['PUT'])]
    #[IsGranted('ROLE_RECRUITER')]
    public function updateStatus(Request $request, Application $application, EntityManagerInterface $entityManager): JsonResponse
    {
        // Security check - only recruiter who owns the offer can update
        if ($application->getOffer()->getRecruiter() !== $this->getUser()) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Access denied. You can only update applications for your offers.'
            ], 403);
        }

        $data = json_decode($request->getContent(), true);
        $status = $data['status'] ?? null;

        if (!in_array($status, [Application::STATUS_ACCEPTED, Application::STATUS_REJECTED, Application::STATUS_PENDING])) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Invalid status'
            ], 400);
        }

        $application->setStatus($status);
        $entityManager->flush();

        $message = $status === Application::STATUS_ACCEPTED
            ? 'Application accepted successfully!'
            : ($status === Application::STATUS_REJECTED
                ? 'Application rejected successfully!'
                : 'Application status reset to pending.');

        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'application' => [
                'id' => $application->getId(),
                'status' => $application->getStatus(),
                'statusText' => $application->getStatusText(),
                'statusBadgeClass' => $application->getStatusBadgeClass(),
            ]
        ]);
    }

    // AJAX ENDPOINT: Search and filter applications
    #[Route('/search', name: 'application_search', methods: ['GET'])]
    public function search(Request $request, ApplicationRepository $applicationRepository, OfferRepository $offerRepository): JsonResponse
    {
        $searchTerm = $request->query->get('q', '');
        $status = $request->query->get('status', '');
        $offerId = $request->query->get('offer', '');
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);

        $user = $this->getUser();

        if ($this->isGranted('ROLE_RECRUITER')) {
            $result = $applicationRepository->searchForRecruiter(
                $user,
                $searchTerm,
                $status,
                $offerId,
                $page,
                $limit
            );
        } else {
            $result = $applicationRepository->searchForCandidate(
                $user,
                $searchTerm,
                $status,
                $page,
                $limit
            );
        }

        $applicationsData = [];
        foreach ($result['applications'] as $application) {
            $applicationsData[] = [
                'id' => $application->getId(),
                'candidateName' => $application->getCandidate()->getFullName(),
                'candidateEmail' => $application->getCandidate()->getEmail(),
                'offerTitle' => $application->getOffer()->getTitle(),
                'offerId' => $application->getOffer()->getId(),
                'coverLetter' => $application->getCoverLetter(),
                'resume' => $application->getResume(),
                'status' => $application->getStatus(),
                'statusText' => $application->getStatusText(),
                'statusBadgeClass' => $application->getStatusBadgeClass(),
                'createdAt' => $application->getCreatedAt()->format('M d, Y'),
            ];
        }

        // Get offers for filter dropdown (recruiters only)
        $offersData = [];
        if ($this->isGranted('ROLE_RECRUITER')) {
            $offers = $offerRepository->findBy(['recruiter' => $user], ['title' => 'ASC']);
            foreach ($offers as $offer) {
                $offersData[] = [
                    'id' => $offer->getId(),
                    'title' => $offer->getTitle(),
                    'applicationsCount' => $offer->getApplications()->count(),
                ];
            }
        }

        return new JsonResponse([
            'success' => true,
            'applications' => $applicationsData,
            'offers' => $offersData,
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'totalPages' => $result['totalPages']
            ]
        ]);
    }

    // AJAX ENDPOINT: Get statistics for dashboard
    #[Route('/stats', name: 'application_stats', methods: ['GET'])]
    public function stats(ApplicationRepository $applicationRepository): JsonResponse
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_RECRUITER')) {
            $stats = $applicationRepository->getRecruiterStats($user);
        } else {
            $stats = $applicationRepository->getCandidateStats($user);
        }

        return new JsonResponse([
            'success' => true,
            'stats' => $stats
        ]);
    }
}
