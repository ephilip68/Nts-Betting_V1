<?php

namespace App\Entity;

use App\Repository\SystemOptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Système de paris (Trixie, Patent, Yankee... ou "N/M" combinaisons standard),
 * utilisé par les paris de type "système" du NTS Vault.
 */
#[ORM\Entity(repositoryClass: SystemOptionRepository::class)]
class SystemOption
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $matches = 0;

    #[ORM\Column(length: 50)]
    private ?string $label = null;

    #[ORM\Column(length: 255)]
    private ?string $value = null;

    /**
     * @var Collection<int, VaultEntry>
     */
    #[ORM\OneToMany(targetEntity: VaultEntry::class, mappedBy: 'systemOption')]
    private Collection $vaultEntries;

    public function __construct()
    {
        $this->vaultEntries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMatches(): int
    {
        return $this->matches;
    }

    public function setMatches(int $matches): static
    {
        $this->matches = $matches;

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

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return Collection<int, VaultEntry>
     */
    public function getVaultEntries(): Collection
    {
        return $this->vaultEntries;
    }
}
