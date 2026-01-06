
```
HireFlow
├─ .editorconfig
├─ bin
│  └─ console
├─ composer.json
├─ composer.lock
├─ config
│  ├─ bundles.php
│  ├─ packages
│  │  ├─ cache.yaml
│  │  ├─ csrf.yaml
│  │  ├─ doctrine.yaml
│  │  ├─ doctrine_migrations.yaml
│  │  ├─ framework.yaml
│  │  ├─ property_info.yaml
│  │  ├─ routing.yaml
│  │  ├─ security.yaml
│  │  ├─ twig.yaml
│  │  └─ validator.yaml
│  ├─ preload.php
│  ├─ reference.php
│  ├─ routes
│  │  ├─ attributes.yaml
│  │  ├─ framework.yaml
│  │  └─ security.yaml
│  ├─ routes.yaml
│  ├─ secrets
│  │  └─ dev
│  │     ├─ dev.decrypt.private.php
│  │     └─ dev.encrypt.public.php
│  └─ services.yaml
├─ migrations
│  └─ Version20251225215813.php
├─ public
│  ├─ images
│  │  ├─ curriculum-vitae.png
│  │  ├─ donnees.png
│  │  ├─ door.png
│  │  ├─ entreprise.png
│  │  ├─ job-promotion.png
│  │  ├─ pie-chart.png
│  │  └─ utilisateur.png
│  └─ index.php
├─ README.md
├─ src
│  ├─ Controller
│  │  ├─ ApplicationController.php
│  │  ├─ CandidateDashboardController.php
│  │  ├─ HomeController.php
│  │  ├─ OfferController.php
│  │  ├─ ProfileController.php
│  │  ├─ RecruiterDashboardController.php
│  │  ├─ RegistrationController.php
│  │  └─ SecurityController.php
│  ├─ DataFixtures
│  │  └─ AppFixtures.php
│  ├─ Entity
│  │  ├─ Application.php
│  │  ├─ Offer.php
│  │  └─ User.php
│  ├─ Form
│  │  ├─ OfferType.php
│  │  ├─ ProfileFormType.php
│  │  └─ RegistrationFormType.php
│  ├─ Kernel.php
│  ├─ Repository
│  │  ├─ ApplicationRepository.php
│  │  ├─ OfferRepository.php
│  │  └─ UserRepository.php
│  └─ Security
│     └─ AppCustomAuthenticator.php
├─ symfony.lock
└─ templates
   ├─ application
   │  └─ index.html.twig
   ├─ base.html.twig
   ├─ candidate_dashboard
   │  └─ index.html.twig
   ├─ components
   │  ├─ _sidebar_candidate.html.twig
   │  └─ _sidebar_recruiter.html.twig
   ├─ home
   │  └─ index.html.twig
   ├─ offer
   │  └─ index.html.twig
   ├─ profile
   │  └─ index.html.twig
   ├─ recruiter_dashboard
   │  └─ index.html.twig
   ├─ registration
   │  └─ register.html.twig
   └─ security
      └─ login.html.twig

```