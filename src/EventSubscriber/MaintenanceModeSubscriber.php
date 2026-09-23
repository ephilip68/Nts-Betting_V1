<?php

namespace App\EventSubscriber;

use App\Repository\SiteSettingRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/**
 * Bloque l'accès au site public (pas l'admin, pas la connexion) quand le
 * mode maintenance est activé dans Paramètres, sauf pour les administrateurs.
 */
class MaintenanceModeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SiteSettingRepository $siteSettingRepository,
        private readonly Security $security,
        private readonly Environment $twig,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 8]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();

        if (str_starts_with($path, '/admin') || str_starts_with($path, '/login') || str_starts_with($path, '/logout')) {
            return;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $settings = $this->siteSettingRepository->getSettings();

        if (!$settings->isMaintenanceMode()) {
            return;
        }

        $html = $this->twig->render('public/maintenance.html.twig', [
            'message' => $settings->getMaintenanceMessage(),
        ]);

        $event->setResponse(new Response($html, 503));
    }
}
