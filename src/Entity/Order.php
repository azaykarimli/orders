<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Uid\Uuid;
use App\Repository\OrderRepository;
use ApiPlatform\Metadata\Post;

use ApiPlatform\Metadata\GetCollection;

use App\State\OrderProcessor;
use App\State\OrderProvider;




#[ApiResource(
    operations: [
        new GetCollection(provider: OrderProvider::class),
        new Post(processor: OrderProcessor::class)
    ],
    order: ['id' => 'ASC']
)]

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "NONE")]
    #[ORM\Column(type: "uuid", unique: true, nullable: false)]
    private ?string $id = null;

    #[ORM\Column(type: "uuid")]
    #[Assert\NotBlank(message: "The product ID is required.")]
    private ?string $productId = null;

    #[ORM\Column(type: "integer")]
    #[Assert\NotBlank(message: "The quantity is required.")]
    #[Assert\Positive(message: "The quantity must be positive.")]
    private ?int $qty = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $amount = 0;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
    }

    // Getters and setters
    public function getId(): ?string
    {
        return $this->id;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): self
    {
        $this->productId = $productId;
        return $this;
    }

    public function getQty(): ?int
    {
        return $this->qty;
    }

    public function setQty(int $qty): self
    {
        $this->qty = $qty;
        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }
}
