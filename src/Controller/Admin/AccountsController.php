<?php

namespace App\Controller\Admin;

use App\Entity\Account;
use App\Entity\Client;
use App\Form\AccountType;
use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/accounts')]
#[IsGranted('ROLE_ADMIN')]
class AccountsController extends AbstractController
{
    #[Route('/', name: 'admin_accounts', methods: ['GET'])]
    public function index(AccountRepository $accountRepository): Response
    {
        $accounts = $accountRepository->findAllWithClients();

        return $this->render('admin/accounts/index.html.twig', [
            'accounts' => $accounts,
        ]);
    }

    #[Route('/{id}', name: 'admin_account_show', methods: ['GET'])]
    public function show(Account $account): Response
    {
        return $this->render('admin/accounts/show.html.twig', [
            'account' => $account,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_account_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Account $account, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AccountType::class, $account);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Compte mis à jour avec succès.');
            return $this->redirectToRoute('admin_account_show', ['id' => $account->getId()]);
        }

        return $this->render('admin/accounts/edit.html.twig', [
            'account' => $account,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/toggle-status', name: 'admin_account_toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request, Account $account, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle-status'.$account->getId(), $request->request->get('_token'))) {
            $account->setIsActive(!$account->isActive());
            $entityManager->flush();

            $status = $account->isActive() ? 'activé' : 'désactivé';
            $this->addFlash('success', "Compte {$status} avec succès.");
        }

        return $this->redirectToRoute('admin_account_show', ['id' => $account->getId()]);
    }

    #[Route('/{id}', name: 'admin_account_delete', methods: ['POST'])]
    public function delete(Request $request, Account $account, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$account->getId(), $request->request->get('_token'))) {
            
            if ($account->getBalance() != 0) {
                $this->addFlash('error', 'Impossible de supprimer un compte avec un solde non nul.');
                return $this->redirectToRoute('admin_account_show', ['id' => $account->getId()]);
            }

            $entityManager->remove($account);
            $entityManager->flush();
            
            $this->addFlash('success', 'Compte supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_accounts');
    }

    #[Route('/client/{clientId}/new', name: 'admin_account_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, int $clientId): Response
    {
        $client = $entityManager->getRepository(Client::class)->find($clientId);
        
        if (!$client) {
            $this->addFlash('error', 'Client non trouvé.');
            return $this->redirectToRoute('admin_clients');
        }

        $account = new Account();
        $account->setClient($client);
        
        $form = $this->createForm(AccountType::class, $account);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($account);
            $entityManager->flush();

            $this->addFlash('success', 'Compte créé avec succès.');
            return $this->redirectToRoute('admin_client_show', ['id' => $clientId]);
        }

        return $this->render('admin/accounts/new.html.twig', [
            'form' => $form->createView(),
            'client' => $client,
        ]);
    }
}