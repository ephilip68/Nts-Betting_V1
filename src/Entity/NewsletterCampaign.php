<?php

namespace App\Entity;

use App\Repository\NewsletterCampaignRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une campagne d'e-mail envoyée aux inscrits actifs de la newsletter
 * ("Restez informé"). Rédigée en Markdown, comme les articles du blog.
 */
#[ORM\Entity(repositoryClass: NewsletterCampaignRepository::class)]
class NewsletterCampaign
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $subject = null;

    #[ORM\Column(type: 'text')]
    private ?string $content = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $sentAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $recipientCount = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getSentAt(): ?\DateTime
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTime $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getRecipientCount(): ?int
    {
        return $this->recipientCount;
    }

    public function setRecipientCount(?int $recipientCount): static
    {
        $this->recipientCount = $recipientCount;

        return $this;
    }
}
