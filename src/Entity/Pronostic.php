<?php

namespace App\Entity;

use App\Repository\PronosticRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PronosticRepository::class)]
class Pronostic
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_WON = 'won';
    public const STATUS_LOST = 'lost';
    public const STATUS_VOID = 'void';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $sport = null;

    #[ORM\Column(length: 255)]
    private ?string $competition = null;

    #[ORM\Column(length: 255)]
    private ?string $teamHome = null;

    #[ORM\Column(length: 255)]
    private ?string $teamAway = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $teamHomeLogo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $teamAwayLogo = null;

    #[ORM\Column]
    private ?\DateTime $matchDate = null;

    #[ORM\Column(length: 100)]
    private ?string $betType = null;

    #[ORM\Column(length: 255)]
    private ?string $betValue = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $odds = null;

    /**
     * Mise nominale en euros, utilisée pour calculer les stats de la page Résultats
     * (gains/pertes, ROI...). Indicatif : ce n'est pas un solde ou de l'argent réel
     * détenu par le site (voir la contrainte NTS Vault).
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2)]
    private string $stake = '10.00';

    #[ORM\Column]
    private ?int $confidence = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $analysis = null;

    #[ORM\Column]
    private bool $isVip = false;

    #[ORM\Column]
    private bool $isFeatured = false;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

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

    public function getSport(): ?string
    {
        return $this->sport;
    }

    public function setSport(string $sport): static
    {
        $this->sport = $sport;

        return $this;
    }

    public function getCompetition(): ?string
    {
        return $this->competition;
    }

    public function setCompetition(string $competition): static
    {
        $this->competition = $competition;

        return $this;
    }

    public function getTeamHome(): ?string
    {
        return $this->teamHome;
    }

    public function setTeamHome(string $teamHome): static
    {
        $this->teamHome = $teamHome;

        return $this;
    }

    public function getTeamAway(): ?string
    {
        return $this->teamAway;
    }

    public function setTeamAway(string $teamAway): static
    {
        $this->teamAway = $teamAway;

        return $this;
    }

    public function getTeamHomeLogo(): ?string
    {
        return $this->teamHomeLogo;
    }

    public function setTeamHomeLogo(?string $teamHomeLogo): static
    {
        $this->teamHomeLogo = $teamHomeLogo;

        return $this;
    }

    public function getTeamAwayLogo(): ?string
    {
        return $this->teamAwayLogo;
    }

    public function setTeamAwayLogo(?string $teamAwayLogo): static
    {
        $this->teamAwayLogo = $teamAwayLogo;

        return $this;
    }

    public function getMatchDate(): ?\DateTime
    {
        return $this->matchDate;
    }

    public function setMatchDate(\DateTime $matchDate): static
    {
        $this->matchDate = $matchDate;

        return $this;
    }

    public function getBetType(): ?string
    {
        return $this->betType;
    }

    public function setBetType(string $betType): static
    {
        $this->betType = $betType;

        return $this;
    }

    public function getBetValue(): ?string
    {
        return $this->betValue;
    }

    public function setBetValue(string $betValue): static
    {
        $this->betValue = $betValue;

        return $this;
    }

    public function getOdds(): ?string
    {
        return $this->odds;
    }

    public function setOdds(string $odds): static
    {
        $this->odds = $odds;

        return $this;
    }

    public function getStake(): string
    {
        return $this->stake;
    }

    public function setStake(string $stake): static
    {
        $this->stake = $stake;

        return $this;
    }

    public function getConfidence(): ?int
    {
        return $this->confidence;
    }

    public function setConfidence(int $confidence): static
    {
        $this->confidence = max(1, min(5, $confidence));

        return $this;
    }

    public function getAnalysis(): ?string
    {
        return $this->analysis;
    }

    public function setAnalysis(?string $analysis): static
    {
        $this->analysis = $analysis;

        return $this;
    }

    public function isVip(): bool
    {
        return $this->isVip;
    }

    public function setIsVip(bool $isVip): static
    {
        $this->isVip = $isVip;

        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;

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

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
