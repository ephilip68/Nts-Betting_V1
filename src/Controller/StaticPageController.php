<?php

namespace App\Controller;

use App\Form\ContactFormType;
use App\Repository\PronosticRepository;
use App\Repository\SiteSettingRepository;
use App\Repository\UserRepository;
use App\Service\ResultsStatsService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;

final class StaticPageController extends AbstractController
{
    /**
     * @return array<int, array{category: string, question: string, answer: string}>
     */
    private function faqItems(): array
    {
        return [
            // Abonnements
            ['category' => 'Abonnements', 'question' => 'Quelles offres sont disponibles ?', 'answer' => 'Quatre paliers : Starter (14,99€/mois), Essentiel (29,99€/mois), Avancé (49,99€/mois, le plus populaire) et VIP (99,99€/mois). Chaque palier débloque plus de pronostics et d\'analyses ; le NTS Vault est inclus à partir de l\'offre Avancé.'],
            ['category' => 'Abonnements', 'question' => 'Comment gérer ou annuler mon abonnement ?', 'answer' => 'Depuis Mon profil > Mon abonnement, le bouton "Gérer mon abonnement" ouvre le portail de facturation Stripe : tu peux y changer de carte, changer d\'offre ou annuler à tout moment, sans nous contacter.'],
            ['category' => 'Abonnements', 'question' => 'Le NTS Vault est-il inclus dans tous les abonnements ?', 'answer' => 'Non, le NTS Vault (suivi de bankroll personnel) est réservé aux membres Avancé et VIP.'],

            // Pronostics
            ['category' => 'Pronostics', 'question' => 'Comment sont publiés les pronostics ?', 'answer' => 'Chaque pronostic (sport, compétition, cote, niveau de confiance, analyse) est publié par notre équipe depuis le back-office. Le résultat (gagné/perdu) est mis à jour dès la fin de l\'événement.'],
            ['category' => 'Pronostics', 'question' => 'Que signifie "Réservé VIP" sur un pronostic ?', 'answer' => 'Certains pronostics sont réservés aux membres avec un abonnement actif. Sans abonnement, le pronostic et la cote restent masqués jusqu\'à ce que tu passes à une offre payante.'],
            ['category' => 'Pronostics', 'question' => 'Que veut dire le "niveau de confiance" ?', 'answer' => 'Une note de 1 à 5 qui reflète le degré de conviction de l\'analyse sur ce pronostic précis — ce n\'est pas une garantie de résultat.'],
            ['category' => 'Pronostics', 'question' => 'Puis-je consulter l\'historique complet des résultats ?', 'answer' => 'Oui, la page Résultats affiche le taux de réussite, les gains cumulés et l\'historique détaillé de tous les pronostics déjà joués, avec des filtres par sport, compétition et période.'],

            // NTS Vault
            ['category' => 'NTS Vault', 'question' => 'À quoi sert le NTS Vault ?', 'answer' => 'C\'est un outil de suivi personnel : tu y enregistres tes propres mises et gains (réalisés sur les plateformes de paris de ton choix) pour suivre l\'évolution de ta bankroll, ton ROI et ton taux de réussite.'],
            ['category' => 'NTS Vault', 'question' => 'Le NTS Vault gère-t-il de l\'argent réel ?', 'answer' => 'Non. Aucun dépôt d\'argent réel n\'est effectué sur NTS Betting via le Vault : c\'est uniquement un tableau de bord de suivi, pas un portefeuille.'],

            // Compte & sécurité
            ['category' => 'Compte & sécurité', 'question' => 'Comment créer un compte ?', 'answer' => 'Depuis "S\'inscrire", avec ton e-mail et un mot de passe (ou via Google/Apple). Un e-mail de vérification est envoyé automatiquement ; ton compte est activé une fois le lien cliqué.'],
            ['category' => 'Compte & sécurité', 'question' => 'J\'ai oublié mon mot de passe, que faire ?', 'answer' => 'Sur la page de connexion, clique sur "Mot de passe oublié ?" et suis le lien reçu par e-mail (valable 1 heure).'],
            ['category' => 'Compte & sécurité', 'question' => 'Mes données sont-elles sécurisées ?', 'answer' => 'Les mots de passe sont chiffrés, les paiements passent exclusivement par Stripe (nous ne stockons aucune donnée bancaire), et les échanges sont protégés contre les attaques courantes (CSRF, etc.).'],
            ['category' => 'Compte & sécurité', 'question' => 'Comment supprimer mon compte ?', 'answer' => 'Depuis Mon profil > Supprimer mon compte. C\'est une suppression réelle et définitive : abonnement Stripe annulé, NTS Vault et données personnelles supprimés.'],

            // Paiements
            ['category' => 'Paiements', 'question' => 'Quels moyens de paiement sont acceptés ?', 'answer' => 'Le paiement par carte bancaire, géré par Stripe. Selon les réglages de paiement activés, d\'autres moyens (Apple Pay, etc.) peuvent être proposés automatiquement à la validation.'],
            ['category' => 'Paiements', 'question' => 'Le paiement est-il sécurisé ?', 'answer' => 'Oui : tous les paiements sont traités par Stripe, un prestataire certifié PCI-DSS. NTS Betting n\'a jamais accès à ton numéro de carte.'],
            ['category' => 'Paiements', 'question' => 'Puis-je me faire rembourser ?', 'answer' => 'Tu peux annuler ton abonnement à tout moment depuis le portail Stripe — l\'accès reste actif jusqu\'à la fin de la période déjà payée, sans reconduction. Pour un cas particulier, contacte-nous via le formulaire de contact.'],
        ];
    }

    #[Route('/a-propos', name: 'app_about')]
    public function about(
        PronosticRepository $pronosticRepository,
        UserRepository $userRepository,
        ResultsStatsService $resultsStatsService
    ): Response {
        $stats = $resultsStatsService->getGlobalStats($pronosticRepository->findAllSettled());

        return $this->render('public/about.html.twig', [
            'winRate' => $stats['winRate'],
            'settledCount' => $stats['settledCount'],
            'activeMembersCount' => $userRepository->count(['subscriptionStatus' => 'active']),
        ]);
    }

    #[Route('/faq', name: 'app_faq')]
    public function faq(): Response
    {
        $items = $this->faqItems();
        $categories = array_values(array_unique(array_column($items, 'category')));

        $countByCategory = [];
        foreach ($items as $item) {
            $countByCategory[$item['category']] = ($countByCategory[$item['category']] ?? 0) + 1;
        }

        return $this->render('public/faq.html.twig', [
            'items' => $items,
            'categories' => $categories,
            'countByCategory' => $countByCategory,
        ]);
    }

    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(
        Request $request,
        MailerInterface $mailer,
        SiteSettingRepository $siteSettingRepository
    ): Response {
        $form = $this->createForm(ContactFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $settings = $siteSettingRepository->getSettings();
            $recipient = $settings->getContactEmail();

            if (!$recipient) {
                $this->addFlash('error', "Le formulaire de contact n'est pas encore configuré (aucune adresse e-mail de contact définie). Réessaie plus tard.");

                return $this->redirectToRoute('app_contact');
            }

            $subjectLabels = array_flip($form->get('subject')->getConfig()->getOption('choices'));
            $subjectLabel = $subjectLabels[$data['subject']] ?? $data['subject'];

            $email = (new TemplatedEmail())
                ->from(new Address('no-reply@nts-betting.com', 'NTS Betting'))
                ->to($recipient)
                ->replyTo(new Address($data['email'], $data['name']))
                ->subject('[Contact NTS Betting] ' . $subjectLabel)
                ->htmlTemplate('emails/contact.html.twig')
                ->context([
                    'name' => $data['name'],
                    'senderEmail' => $data['email'],
                    'subject' => $subjectLabel,
                    'messageContent' => $data['message'],
                ]);

            $mailer->send($email);

            $this->addFlash('success', 'Ton message a bien été envoyé, notre équipe te répond sous 24h.');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('public/contact.html.twig', [
            'form' => $form,
            'faqPreview' => array_slice($this->faqItems(), 0, 5),
        ]);
    }

    #[Route('/jeu-responsable', name: 'app_responsible_gaming')]
    public function responsibleGaming(): Response
    {
        return $this->render('public/legal/responsible_gaming.html.twig');
    }

    #[Route('/mentions-legales', name: 'app_legal_notice')]
    public function legalNotice(): Response
    {
        return $this->render('public/legal/legal_notice.html.twig');
    }

    #[Route('/confidentialite', name: 'app_privacy_policy')]
    public function privacyPolicy(): Response
    {
        return $this->render('public/legal/privacy_policy.html.twig');
    }

    #[Route('/conditions-utilisation', name: 'app_terms')]
    public function terms(): Response
    {
        return $this->render('public/legal/terms.html.twig');
    }
}
