<?php

namespace App\Entity;

use App\Repository\BankrollRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un bankroll NTS Vault : un suivi de paris/bankroll indépendant appartenant à un
 * membre. Un membre peut en avoir plusieurs selon son palier d'abonnement
 * (voir User::getMaxBankrolls()). Aucun solde réel n'est stocké ici : tous les
 * chiffres affichés (solde, ROI...) sont calculés à partir des VaultEntry qui lui
 * appartiennent.
 */
#[ORM\Entity(repositoryClass: BankrollRepository::class)]
class Bankroll
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bankrolls')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $vaultGoalMonthlyProfit = null;

    #[ORM\Column(nullable: true)]
    private ?int $vaultGoalWinRate = null;

    #[ORM\Column(nullable: true)]
    private ?int $vaultGoalBetCount = null;

    /**
     * @var Collection<int, VaultEntry>
     */
    #[ORM\OneToMany(targetEntity: VaultEntry::class, mappedBy: 'bankroll', orphanRemoval: true)]
    private Collection $vaultEntries;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->vaultEntries = new ArrayCollection();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getVaultGoalMonthlyProfit(): ?string
    {
        return $this->vaultGoalMonthlyProfit;
    }

    public function setVaultGoalMonthlyProfit(?string $vaultGoalMonthlyProfit): static
    {
        $this->vaultGoalMonthlyProfit = $vaultGoalMonthlyProfit;

        return $this;
    }

    public function getVaultGoalWinRate(): ?int
    {
        return $this->vaultGoalWinRate;
    }

    public function setVaultGoalWinRate(?int $vaultGoalWinRate): static
    {
        $this->vaultGoalWinRate = $vaultGoalWinRate;

        return $this;
    }

    public function getVaultGoalBetCount(): ?int
    {
        return $this->vaultGoalBetCount;
    }

    public function setVaultGoalBetCount(?int $vaultGoalBetCount): static
    {
        $this->vaultGoalBetCount = $vaultGoalBetCount;

        return $this;
    }

    /**
     * @return Collection<int, VaultEntry>
     */
    public function getVaultEntries(): Collection
    {
        return $this->vaultEntries;
    }

    public function addVaultEntry(VaultEntry $vaultEntry): static
    {
        if (!$this->vaultEntries->contains($vaultEntry)) {
            $this->vaultEntries->add($vaultEntry);
            $vaultEntry->setBankroll($this);
        }

        return $this;
    }

    public function removeVaultEntry(VaultEntry $vaultEntry): static
    {
        if ($this->vaultEntries->removeElement($vaultEntry)) {
            if ($vaultEntry->getBankroll() === $this) {
                $vaultEntry->setBankroll(null);
            }
        }

        return $this;
    }
}
