<?php

namespace App\Entity;

use App\Repository\AccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AccountRepository::class)
 */
class Account
{
    public const TYPE_CHECKING = 'checking';
    public const TYPE_SAVINGS = 'savings';
    public const TYPE_BUSINESS = 'business';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=34, unique=true, nullable=true)
     */
    private $accountNumber;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $type;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private $balance = '0.00';

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private $overdraftLimit = '0.00';

    /**
     * @ORM\Column(type="boolean")
     */
    private $isActive = true;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="accounts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $client;

    /**
     * @ORM\OneToMany(mappedBy="account", targetEntity=Transaction::class)
     */
    private $transactions;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->transactions = new ArrayCollection();
        
        $this->accountNumber = $this->generateAccountNumber();
    }

    private function generateAccountNumber(): string
    {
        
        return 'FR76' . 
               str_pad((string)mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT) . 
               str_pad((string)mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT) . 
               str_pad((string)mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT) . 
               str_pad((string)mt_rand(10, 99), 2, '0', STR_PAD_LEFT);
    }

    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccountNumber(): ?string
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(string $accountNumber): self
    {
        $this->accountNumber = $accountNumber;
        return $this;
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

    public function getBalance(): string
    {
        return $this->balance;
    }

    public function setBalance(string $balance): self
    {
        $this->balance = $balance;
        return $this;
    }

    public function getOverdraftLimit(): string
    {
        return $this->overdraftLimit;
    }

    public function setOverdraftLimit(string $overdraftLimit): self
    {
        $this->overdraftLimit = $overdraftLimit;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
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

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions[] = $transaction;
            $transaction->setAccount($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        if ($this->transactions->removeElement($transaction)) {
            if ($transaction->getAccount() === $this) {
                $transaction->setAccount(null);
            }
        }

        return $this;
    }

    public function getTypeLabel(): string
    {
        $labels = [
            self::TYPE_CHECKING => 'Compte Courant',
            self::TYPE_SAVINGS => 'Compte Épargne',
            self::TYPE_BUSINESS => 'Compte Professionnel',
        ];
        return $labels[$this->type] ?? $this->type;
    }
}