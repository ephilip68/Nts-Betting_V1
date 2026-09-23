<?php

namespace App\Entity;

use App\Repository\VaultEntrySelectionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une sélection (un match) au sein d'un pari combiné ou système du NTS Vault.
 */
#[ORM\Entity(repositoryClass: VaultEntrySelectionRepository::class)]
class VaultEntrySelection
{
    public const FIELD_WIN = 'win';
    public const FIELD_LOSE = 'lose';
    public const FIELD_PENDING = 'pending';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'selections')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?VaultEntry $vaultEntry = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $homeTeam = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $awayTeam = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $competition = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $winner = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $winnerLabel = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2)]
    private ?string $odds = null;

    #[ORM\Column(length: 20)]
    private string $field = self::FIELD_PENDING;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVaultEntry(): ?VaultEntry
    {
        return $this->vaultEntry;
    }

    public function setVaultEntry(?VaultEntry $vaultEntry): static
    {
        $this->vaultEntry = $vaultEntry;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getHomeTeam(): ?string
    {
        return $this->homeTeam;
    }

    public function setHomeTeam(?string $homeTeam): static
    {
        $this->homeTeam = $homeTeam;

        return $this;
    }

    public function getAwayTeam(): ?string
    {
        return $this->awayTeam;
    }

    public function setAwayTeam(?string $awayTeam): static
    {
        $this->awayTeam = $awayTeam;

        return $this;
    }

    public function getCompetition(): ?string
    {
        return $this->competition;
    }

    public function setCompetition(?string $competition): static
    {
        $this->competition = $competition;

        return $this;
    }

    public function getWinner(): ?string
    {
        return $this->winner;
    }

    public function setWinner(?string $winner): static
    {
        $this->winner = $winner;

        return $this;
    }

    public function getWinnerLabel(): ?string
    {
        return $this->winnerLabel;
    }

    public function setWinnerLabel(?string $winnerLabel): static
    {
        $this->winnerLabel = $winnerLabel;

        return $this;
    }

    public function getOdds(): string
    {
        return $this->odds;
    }

    public function setOdds(string $odds): static
    {
        $this->odds = $odds;

        return $this;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function setField(string $field): static
    {
        $this->field = $field;

        return $this;
    }
}
