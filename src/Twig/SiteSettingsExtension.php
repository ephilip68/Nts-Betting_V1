<?php

namespace App\Twig;

use App\Entity\SiteSetting;
use App\Repository\SiteSettingRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SiteSettingsExtension extends AbstractExtension
{
    public function __construct(private readonly SiteSettingRepository $siteSettingRepository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('site_settings', [$this, 'getSettings']),
        ];
    }

    public function getSettings(): SiteSetting
    {
        return $this->siteSettingRepository->getSettings();
    }
}
