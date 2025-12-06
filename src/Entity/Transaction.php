<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\Request;

/**
 * @ORM\Entity(repositoryClass=TransactionRepository::class)
 * @ORM\Table(name="bank_transaction")
 */
class Transaction
{
    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_WITHDRAWAL = 'withdrawal';
    public const TYPE_TRANSFER = 'transfer';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $type;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private $amount;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity=Account::class, inversedBy="transactions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $account;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="transactions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $client;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): self
    {
        $this->account = $account;
        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getTypeLabel(): string
    {
        $labels = [
            self::TYPE_DEPOSIT => 'Dépôt',
            self::TYPE_WITHDRAWAL => 'Retrait',
            self::TYPE_TRANSFER => 'Virement',
        ];
        return $labels[$this->type] ?? $this->type;
    }

    public function getFormattedAmount(): string
    {
        $sign = ($this->type === self::TYPE_DEPOSIT) ? '+' : '-';
        return $sign . $this->amount . ' €';
    }
}