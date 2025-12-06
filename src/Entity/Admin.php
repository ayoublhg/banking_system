<?php

namespace App\Entity;

use App\Repository\AdminRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AdminRepository::class)
 */
class Admin extends User
{
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $department;

    public function __construct()
    {
        parent::__construct();
        $this->roles = ['ROLE_ADMIN'];

    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): self
    {
        $this->department = $department;
        return $this;
    }

    
    public function getNom(): string
    {
        return $this->getLastName();
    }

    public function setNom(string $nom): self
    {
        $this->setLastName($nom);
        return $this;
    }

    public function getPrenom(): string
    {
        return $this->getFirstName();
    }

    public function setPrenom(string $prenom): self
    {
        $this->setFirstName($prenom);
        return $this;
    }

    
    public function __toString(): string
    {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }
}