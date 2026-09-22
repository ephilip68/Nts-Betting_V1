<?php

namespace App\Entity;

use App\Repository\CommunityPostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommunityPostRepository::class)]
class CommunityPost
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'communityPosts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(length: 255)]
    private ?string $sport = null;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $analysisTitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $vipMatch = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $vipPrediction = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $vipOdds = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $vipStake = null;

    #[ORM\Column(nullable: true)]
    private ?int $vipConfidence = null;

    /**
     * @var Collection<int, CommunityComment>
     */
    #[ORM\OneToMany(targetEntity: CommunityComment::class, mappedBy: 'communityPost')]
    private Collection $communityComments;

    /**
     * @var Collection<int, CommunityLike>
     */
    #[ORM\OneToMany(targetEntity: CommunityLike::class, mappedBy: 'communityPost')]
    private Collection $communityLikes;

    public function __construct()
    {
        $this->communityComments = new ArrayCollection();
        $this->communityLikes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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

    public function getSport(): ?string
    {
        return $this->sport;
    }

    public function setSport(string $sport): static
    {
        $this->sport = $sport;

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

    public function getAnalysisTitle(): ?string
    {
        return $this->analysisTitle;
    }

    public function setAnalysisTitle(?string $analysisTitle): static
    {
        $this->analysisTitle = $analysisTitle;

        return $this;
    }

    public function getVipMatch(): ?string
    {
        return $this->vipMatch;
    }

    public function setVipMatch(?string $vipMatch): static
    {
        $this->vipMatch = $vipMatch;

        return $this;
    }

    public function getVipPrediction(): ?string
    {
        return $this->vipPrediction;
    }

    public function setVipPrediction(?string $vipPrediction): static
    {
        $this->vipPrediction = $vipPrediction;

        return $this;
    }

    public function getVipOdds(): ?string
    {
        return $this->vipOdds;
    }

    public function setVipOdds(?string $vipOdds): static
    {
        $this->vipOdds = $vipOdds;

        return $this;
    }

    public function getVipStake(): ?string
    {
        return $this->vipStake;
    }

    public function setVipStake(?string $vipStake): static
    {
        $this->vipStake = $vipStake;

        return $this;
    }

    public function getVipConfidence(): ?int
    {
        return $this->vipConfidence;
    }

    public function setVipConfidence(?int $vipConfidence): static
    {
        $this->vipConfidence = $vipConfidence;

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
            $communityComment->setCommunityPost($this);
        }

        return $this;
    }

    public function removeCommunityComment(CommunityComment $communityComment): static
    {
        if ($this->communityComments->removeElement($communityComment)) {
            // set the owning side to null (unless already changed)
            if ($communityComment->getCommunityPost() === $this) {
                $communityComment->setCommunityPost(null);
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
            $communityLike->setCommunityPost($this);
        }

        return $this;
    }

    public function removeCommunityLike(CommunityLike $communityLike): static
    {
        if ($this->communityLikes->removeElement($communityLike)) {
            // set the owning side to null (unless already changed)
            if ($communityLike->getCommunityPost() === $this) {
                $communityLike->setCommunityPost(null);
            }
        }

        return $this;
    }
}
