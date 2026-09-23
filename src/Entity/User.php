<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $nickname = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column]
    private ?\DateTime $dateInscription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column]
    private ?int $points = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $vipUntil = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeCustomerId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeSubscriptionId = null;

    /**
     * Slug de l'offre active : starter | essentiel | avance | vip
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $subscriptionPlan = null;

    /**
     * Statut Stripe : active | trialing | past_due | canceled | incomplete...
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $subscriptionStatus = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $subscriptionCurrentPeriodEnd = null;

    /**
     * @var Collection<int, Bankroll>
     */
    #[ORM\OneToMany(targetEntity: Bankroll::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $bankrolls;

    /**
     * Format d'affichage des cotes préféré du membre : decimal | fractional
     */
    #[ORM\Column(length: 20)]
    private string $oddsFormat = 'decimal';

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $googleId = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $appleId = null;

    /**
     * @var Collection<int, CommunityPost>
     */
    #[ORM\OneToMany(targetEntity: CommunityPost::class, mappedBy: 'user')]
    private Collection $communityPosts;

    /**
     * @var Collection<int, CommunityComment>
     */
    #[ORM\OneToMany(targetEntity: CommunityComment::class, mappedBy: 'user')]
    private Collection $communityComments;

    /**
     * @var Collection<int, CommunityLike>
     */
    #[ORM\OneToMany(targetEntity: CommunityLike::class, mappedBy: 'user')]
    private Collection $communityLikes;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $birthDate = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(length: 10, options: ['default' => 'fr'])]
    private string $language = 'fr';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $favoriteSport = null;

    #[ORM\Column(length: 100, options: ['default' => 'Europe/Paris'])]
    private string $timezone = 'Europe/Paris';

    /**
     * Préférence d'affichage : dark | light | system.
     * Le site n'a pour l'instant qu'un thème sombre — cette préférence est
     * stockée pour un futur vrai mode clair, mais n'a pas d'effet visuel.
     */
    #[ORM\Column(length: 20, options: ['default' => 'dark'])]
    private string $theme = 'dark';

    #[ORM\Column(options: ['default' => true])]
    private bool $notifyNewPronostics = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $notifyResultsAnalysis = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $notifyOffers = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $notifySiteNews = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $notifySubscriptionReminders = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $notifyTelegramMessages = true;

    public function __construct()
    {
        $this->communityPosts = new ArrayCollection();
        $this->communityComments = new ArrayCollection();
        $this->communityLikes = new ArrayCollection();
        $this->bankrolls = new ArrayCollection();
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

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): static
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getDateInscription(): ?\DateTime
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTime $dateInscription): static
    {
        $this->dateInscription = $dateInscription;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;

        return $this;
    }

    public function getVipUntil(): ?\DateTime
    {
        return $this->vipUntil;
    }

    public function setVipUntil(?\DateTime $vipUntil): static
    {
        $this->vipUntil = $vipUntil;

        return $this;
    }

    /**
     * True si l'utilisateur a un abonnement payant actif (peu importe le palier).
     * Utilisé pour le verrouillage VIP des pronostics et du contenu premium.
     */
    public function isVip(): bool
    {
        if ($this->vipUntil !== null && $this->vipUntil > new \DateTime()) {
            return true;
        }

        return $this->subscriptionStatus === 'active' && $this->subscriptionPlan !== null;
    }

    /**
     * Le NTS Vault (suivi de bankroll perso) est accessible à tous les paliers payants,
     * avec un nombre de bankrolls autorisés qui varie selon le palier (voir getMaxBankrolls()).
     */
    public function hasVaultAccess(): bool
    {
        return $this->isVip();
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId(?string $stripeCustomerId): static
    {
        $this->stripeCustomerId = $stripeCustomerId;

        return $this;
    }

    public function getStripeSubscriptionId(): ?string
    {
        return $this->stripeSubscriptionId;
    }

    public function setStripeSubscriptionId(?string $stripeSubscriptionId): static
    {
        $this->stripeSubscriptionId = $stripeSubscriptionId;

        return $this;
    }

    public function getSubscriptionPlan(): ?string
    {
        return $this->subscriptionPlan;
    }

    public function setSubscriptionPlan(?string $subscriptionPlan): static
    {
        $this->subscriptionPlan = $subscriptionPlan;

        return $this;
    }

    public function getSubscriptionStatus(): ?string
    {
        return $this->subscriptionStatus;
    }

    public function setSubscriptionStatus(?string $subscriptionStatus): static
    {
        $this->subscriptionStatus = $subscriptionStatus;

        return $this;
    }

    public function getSubscriptionCurrentPeriodEnd(): ?\DateTime
    {
        return $this->subscriptionCurrentPeriodEnd;
    }

    public function setSubscriptionCurrentPeriodEnd(?\DateTime $subscriptionCurrentPeriodEnd): static
    {
        $this->subscriptionCurrentPeriodEnd = $subscriptionCurrentPeriodEnd;

        return $this;
    }

    /**
     * @return Collection<int, Bankroll>
     */
    public function getBankrolls(): Collection
    {
        return $this->bankrolls;
    }

    public function addBankroll(Bankroll $bankroll): static
    {
        if (!$this->bankrolls->contains($bankroll)) {
            $this->bankrolls->add($bankroll);
            $bankroll->setUser($this);
        }

        return $this;
    }

    public function removeBankroll(Bankroll $bankroll): static
    {
        if ($this->bankrolls->removeElement($bankroll)) {
            if ($bankroll->getUser() === $this) {
                $bankroll->setUser(null);
            }
        }

        return $this;
    }

    /**
     * Nombre de bankrolls NTS Vault autorisés selon le palier d'abonnement actif.
     */
    public function getMaxBankrolls(): int
    {
        if (!$this->hasVaultAccess()) {
            return 0;
        }

        if ($this->vipUntil !== null && $this->vipUntil > new \DateTime()) {
            return PHP_INT_MAX;
        }

        return match ($this->subscriptionPlan) {
            'starter' => 1,
            'essentiel' => 5,
            'avance' => 10,
            'vip' => PHP_INT_MAX,
            default => 0,
        };
    }

    public function getOddsFormat(): string
    {
        return $this->oddsFormat;
    }

    public function setOddsFormat(string $oddsFormat): static
    {
        $this->oddsFormat = $oddsFormat;

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getAppleId(): ?string
    {
        return $this->appleId;
    }

    public function setAppleId(?string $appleId): static
    {
        $this->appleId = $appleId;

        return $this;
    }

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    /**
     * Symfony Security
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function eraseCredentials(): void
    {
        // Rien à effacer pour le moment.
    }

    /**
     * @return Collection<int, CommunityPost>
     */
    public function getCommunityPosts(): Collection
    {
        return $this->communityPosts;
    }

    public function addCommunityPost(CommunityPost $communityPost): static
    {
        if (!$this->communityPosts->contains($communityPost)) {
            $this->communityPosts->add($communityPost);
            $communityPost->setUser($this);
        }

        return $this;
    }

    public function removeCommunityPost(CommunityPost $communityPost): static
    {
        if ($this->communityPosts->removeElement($communityPost)) {
            if ($communityPost->getUser() === $this) {
                $communityPost->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CommunityComment>
     */
    public function getCommunityComments(): Collection
    {
        return $this->communityComments;
    }

    public function addCommunityComment(CommunityComment $communityComment): static
    {
        if (!$this->communityComments->contains($communityComment)) {
            $this->communityComments->add($communityComment);
            $communityComment->setUser($this);
        }

        return $this;
    }

    public function removeCommunityComment(CommunityComment $communityComment): static
    {
        if ($this->communityComments->removeElement($communityComment)) {
            if ($communityComment->getUser() === $this) {
                $communityComment->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CommunityLike>
     */
    public function getCommunityLikes(): Collection
    {
        return $this->communityLikes;
    }

    public function addCommunityLike(CommunityLike $communityLike): static
    {
        if (!$this->communityLikes->contains($communityLike)) {
            $this->communityLikes->add($communityLike);
            $communityLike->setUser($this);
        }

        return $this;
    }

    public function removeCommunityLike(CommunityLike $communityLike): static
    {
        if ($this->communityLikes->removeElement($communityLike)) {
            if ($communityLike->getUser() === $this) {
                $communityLike->setUser(null);
            }
        }

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getBirthDate(): ?\DateTime
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTime $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getFavoriteSport(): ?string
    {
        return $this->favoriteSport;
    }

    public function setFavoriteSport(?string $favoriteSport): static
    {
        $this->favoriteSport = $favoriteSport;

        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function isNotifyNewPronostics(): bool
    {
        return $this->notifyNewPronostics;
    }

    public function setNotifyNewPronostics(bool $notifyNewPronostics): static
    {
        $this->notifyNewPronostics = $notifyNewPronostics;

        return $this;
    }

    public function isNotifyResultsAnalysis(): bool
    {
        return $this->notifyResultsAnalysis;
    }

    public function setNotifyResultsAnalysis(bool $notifyResultsAnalysis): static
    {
        $this->notifyResultsAnalysis = $notifyResultsAnalysis;

        return $this;
    }

    public function isNotifyOffers(): bool
    {
        return $this->notifyOffers;
    }

    public function setNotifyOffers(bool $notifyOffers): static
    {
        $this->notifyOffers = $notifyOffers;

        return $this;
    }

    public function isNotifySiteNews(): bool
    {
        return $this->notifySiteNews;
    }

    public function setNotifySiteNews(bool $notifySiteNews): static
    {
        $this->notifySiteNews = $notifySiteNews;

        return $this;
    }

    public function isNotifySubscriptionReminders(): bool
    {
        return $this->notifySubscriptionReminders;
    }

    public function setNotifySubscriptionReminders(bool $notifySubscriptionReminders): static
    {
        $this->notifySubscriptionReminders = $notifySubscriptionReminders;

        return $this;
    }

    public function isNotifyTelegramMessages(): bool
    {
        return $this->notifyTelegramMessages;
    }

    public function setNotifyTelegramMessages(bool $notifyTelegramMessages): static
    {
        $this->notifyTelegramMessages = $notifyTelegramMessages;

        return $this;
    }
}