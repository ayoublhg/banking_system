<?php
// src/DataFixtures/AppFixtures.php

namespace App\DataFixtures;

use App\Entity\Account;
use App\Entity\Client;
use App\Entity\Service;
use App\Entity\Admin;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Créer un administrateur
        $admin = new Admin();
        $admin->setEmail('admin@bank.com');
        $admin->setNom('Admin');
        $admin->setPrenom('System');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);

        // 2. Créer 5 clients avec comptes
        $clientsData = [
            ['Jean', 'Dupont', 'jean.dupont@email.com'],
            ['Marie', 'Martin', 'marie.martin@email.com'],
            ['Pierre', 'Durand', 'pierre.durand@email.com'],
            ['Sophie', 'Leroy', 'sophie.leroy@email.com'],
            ['Thomas', 'Moreau', 'thomas.moreau@email.com']
        ];

        foreach ($clientsData as $index => $clientData) {
            $client = new Client();
            $client->setNom($clientData[1]);
            $client->setPrenom($clientData[0]);
            $client->setEmail($clientData[2]);
            $client->setPassword($this->passwordHasher->hashPassword($client, 'client123'));
            $client->setRoles(['ROLE_USER']);
            $manager->persist($client);

            // Créer 2-3 comptes par client
            $accountTypes = [Account::TYPE_CHECKING, Account::TYPE_SAVINGS];
            
            for ($i = 0; $i < rand(2, 3); $i++) {
                $account = new Account();
                $account->setType($accountTypes[array_rand($accountTypes)]);
                $account->setBalance(strval(rand(500, 10000)));
                $account->setOverdraftLimit($account->getType() === Account::TYPE_CHECKING ? '500.00' : '0.00');
                $account->setClient($client);
                $account->setIsActive(true);
                $manager->persist($account);
            }
        }

        // 3. Créer les services bancaires
        $servicesData = [
            [
                'name' => 'Compte Courant Standard',
                'description' => 'Compte courant avec carte de débit, virements et prélèvements.',
                'price' => '0.00',
                'active' => true
            ],
            [
                'name' => 'Compte Premium',
                'description' => 'Compte avec assurance, découvert gratuit et conseiller dédié.',
                'price' => '9.99',
                'active' => true
            ],
            [
                'name' => 'Compte Épargne',
                'description' => 'Épargnez avec un taux d\'intérêt avantageux.',
                'price' => '0.00',
                'active' => true
            ],
            [
                'name' => 'Assurance Habitation',
                'description' => 'Protection complète pour votre logement.',
                'price' => '19.99',
                'active' => true
            ],
            [
                'name' => 'Carte Gold',
                'description' => 'Carte de crédit avec assurance voyage et cashback.',
                'price' => '12.50',
                'active' => true
            ],
            [
                'name' => 'Placements Boursiers',
                'description' => 'Accès aux marchés financiers avec conseils experts.',
                'price' => '29.99',
                'active' => true
            ]
        ];

        foreach ($servicesData as $serviceData) {
            $service = new Service();
            $service->setName($serviceData['name']);
            $service->setDescription($serviceData['description']);
            $service->setPrice($serviceData['price']);
            $service->setIsActive($serviceData['active']);
            $manager->persist($service);
        }

        $manager->flush();
    }
}