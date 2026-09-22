<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    public function __construct()
    {
        $this->communityPosts = new ArrayCollection();
        $this->communityComments = new ArrayCollection();
        $this->communityLikes = new ArrayCollection();
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
}