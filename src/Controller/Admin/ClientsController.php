<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
        
        return $this->render('admin/clients/show.html.twig', [
            'client' => $client,
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
        
        // Note: Tu auras besoin d'un ClientType pour que ça fonctionne complètement
        // Pour l'instant, on fait une version simplifiée
        if ($request->isMethod('POST')) {
            $firstName = $request->request->get('firstName');
            $lastName = $request->request->get('lastName');
            $email = $request->request->get('email');
            
            if ($firstName && $lastName && $email) {
                $client->setFirstName($firstName);
                $client->setLastName($lastName);
                $client->setEmail($email);
                
                $this->em->flush();
                
                $this->addFlash('success', 'Client mis à jour avec succès.');
                return $this->redirectToRoute('admin_client_show', ['id' => $id]);
            }
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
            // Ici tu pourrais ajouter un champ "isActive" dans l'entité Client
            // Pour l'instant, on va juste ajouter un message
            $this->addFlash('warning', 'Fonctionnalité de blocage à implémenter (ajouter un champ isActive dans Client)');
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