<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[UniqueEntity(fields: ["email"], message: "Un compte existe déjà avec cet email.")]
class Client extends User
{
    #[ORM\OneToMany(mappedBy: "client", targetEntity: Account::class)]
    private $accounts;

    #[ORM\OneToMany(mappedBy: "client", targetEntity: Transaction::class)]
    private $transactions;

    #[ORM\ManyToMany(targetEntity: Service::class, inversedBy: 'clients')]
    #[ORM\JoinTable(name: "client_services")]
    #[ORM\JoinColumn(name: "client_id", referencedColumnName: "id")]
    #[ORM\InverseJoinColumn(name: "service_id", referencedColumnName: "id")]
    private $subscribedServices;

    public function __construct()
    {
        parent::__construct();
        $this->accounts = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->subscribedServices = new ArrayCollection();
        $this->roles = ['ROLE_USER'];
    }

    /**
     * @return Collection<int, Service>
     */
    public function getSubscribedServices(): Collection
    {
        return $this->subscribedServices;
    }

    public function addSubscribedService(Service $service): self
    {
        if (!$this->subscribedServices->contains($service)) {
            $this->subscribedServices[] = $service;
            $service->addClient($this);
        }
        return $this;
    }

    public function removeSubscribedService(Service $service): self
    {
        if ($this->subscribedServices->removeElement($service)) {
            $service->removeClient($this);
        }
        return $this;
    }

    public function isSubscribedToService(Service $service): bool
    {
        return $this->subscribedServices->contains($service);
    }

    /**
     * @return Collection<int, Account>
     */
    public function getAccounts(): Collection
    {
        return $this->accounts;
    }

    public function addAccount(Account $account): self
    {
        if (!$this->accounts->contains($account)) {
            $this->accounts[] = $account;
            $account->setClient($this);
        }
        return $this;
    }

    public function removeAccount(Account $account): self
    {
        if ($this->accounts->removeElement($account)) {
            if ($account->getClient() === $this) {
                $account->setClient(null);
            }
        }
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
            $transaction->setClient($this);
        }
        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        if ($this->transactions->removeElement($transaction)) {
            if ($transaction->getClient() === $this) {
                $transaction->setClient(null);
            }
        }
        return $this;
    }

    public function getPrenom(): string
    {
        return $this->getFirstName();
    }

    public function getNom(): string
    {
        return $this->getLastName();
    }

    public function setPrenom(string $prenom): self
    {
        $this->setFirstName($prenom);
        return $this;
    }

    public function setNom(string $nom): self
    {
        $this->setLastName($nom);
        return $this;
    }

    public function __toString(): string
    {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }
}