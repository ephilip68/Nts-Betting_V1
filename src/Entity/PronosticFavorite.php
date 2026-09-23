<?php

namespace App\Entity;

use App\Repository\PronosticFavoriteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un pronostic ajouté aux favoris ("Mes favoris") par un membre.
 */
#[ORM\Entity(repositoryClass: PronosticFavoriteRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_user_pronostic_favorite', columns: ['user_id', 'pronostic_id'])]
class PronosticFavorite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Pronostic $pronostic = null;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPronostic(): ?Pronostic
    {
        return $this->pronostic;
    }

    public function setPronostic(?Pronostic $pronostic): static
    {
        $this->pronostic = $pronostic;

        return $this;
    }
}
