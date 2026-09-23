<?php

namespace App\Entity;

use App\Repository\VaultEntryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un pari enregistré manuellement par le membre dans son NTS Vault (mise/gain
 * réalisés ailleurs, ex : Betclic, Winamax...). Aucun argent réel ne transite
 * par le site : c'est un outil de suivi de bankroll personnel uniquement.
 *
 * Supporte les paris simples, live, combinés et système (avec sélections
 * multiples et options boost/freebet/assurance/cashout).
 */
#[ORM\Entity(repositoryClass: VaultEntryRepository::class)]
class VaultEntry
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_WON = 'won';
    public const STATUS_LOST = 'lost';
    public const STATUS_VOID = 'void';
    public const STATUS_CASHOUT = 'cashout';

    public const TYPE_SIMPLE = 'simple';
    public const TYPE_LIVE = 'live';
    public const TYPE_COMBINE = 'combiné';
    public const TYPE_LIVE_COMBINE = 'live combiné';
    public const TYPE_SYSTEME = 'système';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'vaultEntries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Bankroll $bankroll = null;

    #[ORM\Column]
    private ?\DateTime $placedAt = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $bookmaker = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2)]
    private ?string $stake = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2)]
    private ?string $odds = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(length: 20, options: ['default' => self::TYPE_SIMPLE])]
    private string $betType = self::TYPE_SIMPLE;

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

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $profit = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isBoosted = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $isFreebet = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $isInsured = false;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $cashoutGain = null;

    #[ORM\ManyToOne(inversedBy: 'vaultEntries')]
    private ?SystemOption $systemOption = null;

    /**
     * @var Collection<int, VaultEntrySelection>
     */
    #[ORM\OneToMany(targetEntity: VaultEntrySelection::class, mappedBy: 'vaultEntry', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $selections;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->selections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBankroll(): ?Bankroll
    {
        return $this->bankroll;
    }

    public function setBankroll(?Bankroll $bankroll): static
    {
        $this->bankroll = $bankroll;

        return $this;
    }

    public function getPlacedAt(): ?\DateTime
    {
        return $this->placedAt;
    }

    public function setPlacedAt(\DateTime $placedAt): static
    {
        $this->placedAt = $placedAt;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getBookmaker(): ?string
    {
        return $this->bookmaker;
    }

    public function setBookmaker(?string $bookmaker): static
    {
        $this->bookmaker = $bookmaker;

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

    public function getOdds(): string
    {
        return $this->odds;
    }

    public function setOdds(string $odds): static
    {
        $this->odds = $odds;

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

    public function getBetType(): string
    {
        return $this->betType;
    }

    public function setBetType(string $betType): static
    {
        $this->betType = $betType;

        return $this;
    }

    public function isCombined(): bool
    {
        return in_array($this->betType, [self::TYPE_COMBINE, self::TYPE_LIVE_COMBINE], true);
    }

    public function isSysteme(): bool
    {
        return $this->betType === self::TYPE_SYSTEME;
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

    public function getProfit(): ?string
    {
        return $this->profit;
    }

    public function setProfit(?string $profit): static
    {
        $this->profit = $profit;

        return $this;
    }

    public function isBoosted(): bool
    {
        return $this->isBoosted;
    }

    public function setIsBoosted(bool $isBoosted): static
    {
        $this->isBoosted = $isBoosted;

        return $this;
    }

    public function isFreebet(): bool
    {
        return $this->isFreebet;
    }

    public function setIsFreebet(bool $isFreebet): static
    {
        $this->isFreebet = $isFreebet;

        return $this;
    }

    public function isInsured(): bool
    {
        return $this->isInsured;
    }

    public function setIsInsured(bool $isInsured): static
    {
        $this->isInsured = $isInsured;

        return $this;
    }

    public function getCashoutGain(): ?string
    {
        return $this->cashoutGain;
    }

    public function setCashoutGain(?string $cashoutGain): static
    {
        $this->cashoutGain = $cashoutGain;

        return $this;
    }

    public function getSystemOption(): ?SystemOption
    {
        return $this->systemOption;
    }

    public function setSystemOption(?SystemOption $systemOption): static
    {
        $this->systemOption = $systemOption;

        return $this;
    }

    /**
     * @return Collection<int, VaultEntrySelection>
     */
    public function getSelections(): Collection
    {
        return $this->selections;
    }

    public function addSelection(VaultEntrySelection $selection): static
    {
        if (!$this->selections->contains($selection)) {
            $this->selections->add($selection);
            $selection->setVaultEntry($this);
        }

        return $this;
    }

    public function removeSelection(VaultEntrySelection $selection): static
    {
        if ($this->selections->removeElement($selection)) {
            if ($selection->getVaultEntry() === $this) {
                $selection->setVaultEntry(null);
            }
        }

        return $this;
    }
}
