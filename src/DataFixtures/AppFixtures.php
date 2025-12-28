<?php

namespace App\DataFixtures;
use App\Entity\Application;
use App\Entity\User;
use App\Entity\Offer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const ROLE_CANDIDATE = 'ROLE_CANDIDATE';
    private const ROLE_RECRUITER = 'ROLE_RECRUITER';
    private $passwordHasher;
    public function __construct(UserPasswordHasherInterface $passwordHasher){
        $this->passwordHasher = $passwordHasher;
    }
    public function load(ObjectManager $manager): void
    {
        $recruiter1 = new User();
        $recruiter1->setEmail("recruiter1@company.com");
        $recruiter1->setPassword($this->passwordHasher->hashPassword($recruiter1,"password123"));
        $recruiter1->setRoles([self::ROLE_RECRUITER]);
        $recruiter1->setFullName('Ahmed Mounssif');
        $recruiter1->setPhone('+1234567890');
        $manager->persist($recruiter1);

        $recruiter2 = new User();
        $recruiter2->setEmail('recruiter2@company.com');
        $recruiter2->setPassword($this->passwordHasher->hashPassword($recruiter2, 'password123'));
        $recruiter2->setRoles([self::ROLE_RECRUITER]);
        $recruiter2->setFullName('Sara wajdi');
        $recruiter2->setPhone('+0987654321');
        $manager->persist($recruiter2);

         // Create 3 candidates
        $candidate1 = new User();
        $candidate1->setEmail('candidate1@email.com');
        $candidate1->setPassword($this->passwordHasher->hashPassword($candidate1, 'password123'));
        $candidate1->setRoles([self::ROLE_CANDIDATE]);
        $candidate1->setFullName('Roua wafik');
        $candidate1->setPhone('+1112223333');
        $manager->persist($candidate1);

        $candidate2 = new User();
        $candidate2->setEmail('candidate2@email.com');
        $candidate2->setPassword($this->passwordHasher->hashPassword($candidate2, 'password123'));
        $candidate2->setRoles([self::ROLE_CANDIDATE]);
        $candidate2->setFullName('Adam wassif');
        $candidate2->setPhone('+4445556666');
        $manager->persist($candidate2);

        $candidate3 = new User();
        $candidate3->setEmail('candidate3@email.com');
        $candidate3->setPassword($this->passwordHasher->hashPassword($candidate3, 'password123'));
        $candidate3->setRoles([self::ROLE_CANDIDATE]);
        $candidate3->setFullName('Ahmed Wasif');
        $candidate3->setPhone('+7778889999');
        $manager->persist($candidate3);

        // create offers
        $jobTitles = ['PHP Developer', 'Symfony Developer', 'Web Developer', 'Full Stack Developer'];
        $skills = ['PHP, Symfony, MySQL', 'JavaScript, React, Node.js', 'HTML, CSS, Bootstrap', 'Docker, Git, API'];

        for ($i = 0; $i < 8; $i++) {
            $offer = new Offer();
            $offer->setTitle($jobTitles[$i % 4]);
            $offer->setDescription('We are looking for a skilled ' . $jobTitles[$i % 4] . ' to join our team.');
            $offer->setSkills($skills[$i % 4]);
            $offer->setCreatedAt(new \DateTimeImmutable());
            $offer->setLocation($i % 2 == 0 ? 'Remote' : 'New York, NY');
            $offer->setSalary(($i + 5) . '0,000$');
            $offer->setContractType($i % 3 == 0 ? 'Full-time' : 'Contract');
            $offer->setRecruiter($i % 2 == 0 ? $recruiter1 : $recruiter2);
            $manager->persist($offer);
        }

        $manager->flush();
        // create applications
        $offers = $manager->getRepository(Offer::class)->findAll();
        $candidates = [$candidate1, $candidate2, $candidate3];

        foreach ($offers as $offer) {
            // Each offer gets 1-2 applications
            $numApps = rand(1, 2);
            $coverLetters = [
                'I am very interested in this position because...',
                'With my experience in web development, I believe I would be a great fit...',
                'I have been working with Symfony for 2 years and am excited about this opportunity...',
                'Your company\'s mission aligns with my career goals...',
            ];
            $statuses = [
                Application::STATUS_PENDING,
                Application::STATUS_ACCEPTED,
                Application::STATUS_REJECTED
            ];
            $selectedCandidates = [];
            for ($j = 0; $j < $numApps; $j++) {
                // Ensure unique candidate per offer
                do {
                    $candidate = $candidates[array_rand($candidates)];
                } while (in_array($candidate, $selectedCandidates, true));

                $selectedCandidates[] = $candidate;

                $application = new Application();
                $application->setResume('cv_' . uniqid() . '.pdf');
                $application->setCoverLetter($coverLetters[array_rand($coverLetters)]);
                $application->setStatus($statuses[array_rand($statuses)]);
                $application->setCreatedAt(new \DateTimeImmutable());
                $application->setCandidate($candidate);
                $application->setOffer($offer);
                if (rand(0, 1) === 1) {
                    $notes = [
                        'Strong candidate with relevant experience.',
                        'Needs improvement in Symfony knowledge.',
                        'Excellent communication skills.',
                        'Will schedule interview next week.'
                    ];
                    $application->setNotes($notes[array_rand($notes)]);
                }

                $manager->persist($application);
            }
                    }

        $manager->flush();
    }
}
