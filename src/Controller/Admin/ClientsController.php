<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use App\Entity\Account;
use App\Entity\Transaction;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


#[Route('/admin/clients')]
#[IsGranted('ROLE_ADMIN')]
class ClientsController extends AbstractController
{
    private EntityManagerInterface $em;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    #[Route('/', name: 'admin_clients', methods: ['GET'])]
    public function index(): Response
    {
        // FILTRER : Ne prendre que les Clients, pas les Admin
        $clients = $this->em->getRepository(Client::class)
            ->createQueryBuilder('c')
            ->where('c INSTANCE OF App\Entity\Client')  // <-- IMPORTANT
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        
        return $this->render('admin/clients/index.html.twig', [
            'clients' => $clients,
        ]);
    }

    #[Route('/{id}', name: 'admin_client_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $client = $this->em->getRepository(Client::class)->find($id);
        
        if (!$client) {
            $this->addFlash('error', 'Client non trouvé');
            return $this->redirectToRoute('admin_clients');
        }
        
        // Récupérer les comptes du client
        $accounts = $this->em->getRepository(Account::class)
            ->createQueryBuilder('a')
            ->where('a.client = :client')
            ->setParameter('client', $client)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        
        // Récupérer les transactions du client
        $transactions = $this->em->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->where('t.client = :client')
            ->setParameter('client', $client)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
        
        return $this->render('admin/clients/show.html.twig', [
            'client' => $client,
            'accounts' => $accounts,
            'transactions' => $transactions,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_client_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, int $id): Response
{
    $client = $this->em->getRepository(Client::class)->find($id);
    
    if (!$client) {
        $this->addFlash('error', 'Client non trouvé');
        return $this->redirectToRoute('admin_clients');
    }
    
    if ($request->isMethod('POST')) {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('edit-client'.$id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('admin_client_edit', ['id' => $id]);
        }
        
        $firstName = $request->request->get('firstName');
        $lastName = $request->request->get('lastName');
        $email = $request->request->get('email');
        $phone = $request->request->get('phone');
        $isActive = $request->request->get('isActive');
        
        // Validation basique
        if (!$firstName || !$lastName || !$email) {
            $this->addFlash('error', 'Tous les champs obligatoires doivent être remplis');
            return $this->redirectToRoute('admin_client_edit', ['id' => $id]);
        }
        
        // Vérifier si l'email existe déjà (sauf pour ce client)
        $existingClient = $this->em->getRepository(Client::class)
            ->findOneBy(['email' => $email]);
        
        if ($existingClient && $existingClient->getId() !== $client->getId()) {
            $this->addFlash('error', 'Cet email est déjà utilisé par un autre client');
            return $this->redirectToRoute('admin_client_edit', ['id' => $id]);
        }
        
        // Mettre à jour les informations
        $client->setFirstName($firstName);
        $client->setLastName($lastName);
        $client->setEmail($email);
        
        // Mettre à jour le téléphone si la propriété existe
        if (method_exists($client, 'setPhone')) {
            $client->setPhone($phone);
        }
        
        // Mettre à jour le statut si la propriété existe
        if (method_exists($client, 'setIsActive')) {
            $client->setIsActive((bool)$isActive);
        }
        
        $this->em->flush();
        
        $this->addFlash('success', 'Client mis à jour avec succès');
        return $this->redirectToRoute('admin_client_show', ['id' => $id]);
    }
    
    return $this->render('admin/clients/edit.html.twig', [
        'client' => $client,
    ]);
}

    #[Route('/{id}/toggle-status', name: 'admin_client_toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request, int $id): Response
    {
        $client = $this->em->getRepository(Client::class)->find($id);
        
        if (!$client) {
            $this->addFlash('error', 'Client non trouvé');
            return $this->redirectToRoute('admin_clients');
        }
        
        if ($this->isCsrfTokenValid('toggle-status'.$id, $request->request->get('_token'))) {
            // Vérifie si le client a un champ isActive, sinon ajoute-le
            if (method_exists($client, 'setIsActive')) {
                $client->setIsActive(!$client->isActive());
                $status = $client->isActive() ? 'activé' : 'désactivé';
                $this->em->flush();
                
                $this->addFlash('success', "Client {$status} avec succès.");
            } else {
                $this->addFlash('warning', 'Ajoutez un champ isActive dans l\'entité Client pour utiliser cette fonctionnalité');
            }
        }

        return $this->redirectToRoute('admin_client_show', ['id' => $id]);
    }



#[Route('/{id}/reset-password', name: 'admin_client_reset_password', methods: ['POST'])]
public function resetPassword(
    Request $request, 
    int $id, 
    UserPasswordHasherInterface $passwordHasher
): Response
{
    $client = $this->em->getRepository(Client::class)->find($id);
    
    if (!$client) {
        $this->addFlash('error', 'Client non trouvé');
        return $this->redirectToRoute('admin_clients');
    }
    
    if ($this->isCsrfTokenValid('reset-password'.$id, $request->request->get('_token'))) {
        try {
            // 1. Générer un mot de passe temporaire (8 caractères)
            $temporaryPassword = bin2hex(random_bytes(4)); // Ex: "a1b2c3d4"
            
            // 2. Vérifier si le client a une méthode setPassword()
            if (method_exists($client, 'setPassword')) {
                // 3. Hasher le nouveau mot de passe
                $hashedPassword = $passwordHasher->hashPassword($client, $temporaryPassword);
                $client->setPassword($hashedPassword);
                
                // 4. Optionnel: forcer la modification au prochain login
                if (method_exists($client, 'setPasswordChanged')) {
                    $client->setPasswordChanged(false);
                }
                
                // 5. Sauvegarder
                $this->em->flush();
                
                // 6. Afficher le mot de passe (EN DÉVELOPPEMENT SEULEMENT)
                // EN PRODUCTION, ENVOYER UN EMAIL
                $this->addFlash('success', 
                    "Mot de passe réinitialisé. " .
                    "Mot de passe temporaire : <strong>{$temporaryPassword}</strong> " .
                    "(À communiquer au client)"
                );
            } else {
                $this->addFlash('warning', 
                    "L'entité Client n'a pas de méthode setPassword(). " .
                    "Vérifiez votre configuration."
                );
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la réinitialisation : ' . $e->getMessage());
        }
    } else {
        $this->addFlash('error', 'Token CSRF invalide');
    }

    return $this->redirectToRoute('admin_client_show', ['id' => $id]);
}

    #[Route('/{id}', name: 'admin_client_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $client = $this->em->getRepository(Client::class)->find($id);
        
        if (!$client) {
            $this->addFlash('error', 'Client non trouvé');
            return $this->redirectToRoute('admin_clients');
        }
        
        // Empêcher de supprimer son propre compte
        if ($client->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte');
            return $this->redirectToRoute('admin_client_show', ['id' => $id]);
        }
        
        if ($this->isCsrfTokenValid('delete'.$id, $request->request->get('_token'))) {
            // Vérifier si le client a des comptes avec solde non nul
            $totalBalance = 0;
            foreach ($client->getAccounts() as $account) {
                $totalBalance += (float) $account->getBalance();
            }
            
            if ($totalBalance > 0) {
                $this->addFlash('error', 'Impossible de supprimer un client avec des comptes ayant un solde non nul');
                return $this->redirectToRoute('admin_client_show', ['id' => $id]);
            }
            
            $this->em->remove($client);
            $this->em->flush();
            
            $this->addFlash('success', 'Client supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_clients');
    }
    
    #[Route('/{id}/create-account', name: 'admin_client_create_account', methods: ['GET', 'POST'])]
    public function createAccount(Request $request, int $id): Response
    {
        $client = $this->em->getRepository(Client::class)->find($id);
        
        if (!$client) {
            $this->addFlash('error', 'Client non trouvé');
            return $this->redirectToRoute('admin_clients');
        }
        
        // Rediriger vers la page de création de compte
        return $this->redirectToRoute('admin_account_new', ['clientId' => $id]);
    }
}