<?php

namespace App\Entity;

use App\Repository\SiteSettingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Réglages globaux du site — une seule ligne en base (singleton), éditée
 * depuis l'admin (Paramètres).
 */
#[ORM\Entity(repositoryClass: SiteSettingRepository::class)]
class SiteSetting
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telegramUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $twitterUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $instagramUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $youtubeUrl = null;

    #[ORM\Column]
    private bool $maintenanceMode = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $maintenanceMessage = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    public function getTelegramUrl(): ?string
    {
        return $this->telegramUrl;
    }

    public function setTelegramUrl(?string $telegramUrl): static
    {
        $this->telegramUrl = $telegramUrl;

        return $this;
    }

    public function getTwitterUrl(): ?string
    {
        return $this->twitterUrl;
    }

    public function setTwitterUrl(?string $twitterUrl): static
    {
        $this->twitterUrl = $twitterUrl;

        return $this;
    }

    public function getInstagramUrl(): ?string
    {
        return $this->instagramUrl;
    }

    public function setInstagramUrl(?string $instagramUrl): static
    {
        $this->instagramUrl = $instagramUrl;

        return $this;
    }

    public function getYoutubeUrl(): ?string
    {
        return $this->youtubeUrl;
    }

    public function setYoutubeUrl(?string $youtubeUrl): static
    {
        $this->youtubeUrl = $youtubeUrl;

        return $this;
    }

    public function isMaintenanceMode(): bool
    {
        return $this->maintenanceMode;
    }

    public function setMaintenanceMode(bool $maintenanceMode): static
    {
        $this->maintenanceMode = $maintenanceMode;

        return $this;
    }

    public function getMaintenanceMessage(): ?string
    {
        return $this->maintenanceMessage;
    }

    public function setMaintenanceMessage(?string $maintenanceMessage): static
    {
        $this->maintenanceMessage = $maintenanceMessage;

        return $this;
    }
}
