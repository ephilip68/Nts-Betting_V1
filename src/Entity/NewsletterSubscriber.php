<?php

namespace App\Entity;

use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NewsletterSubscriberRepository::class)]
class NewsletterSubscriber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $unsubscribeToken = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private ?\DateTime $subscribedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $unsubscribedAt = null;

    public function __construct()
    {
        $this->subscribedAt = new \DateTime();
        $this->unsubscribeToken = bin2hex(random_bytes(24));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUnsubscribeToken(): ?string
    {
        return $this->unsubscribeToken;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getSubscribedAt(): ?\DateTime
    {
        return $this->subscribedAt;
    }

    public function getUnsubscribedAt(): ?\DateTime
    {
        return $this->unsubscribedAt;
    }

    public function setUnsubscribedAt(?\DateTime $unsubscribedAt): static
    {
        $this->unsubscribedAt = $unsubscribedAt;

        return $this;
    }
}
