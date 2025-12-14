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
    private EntityManagerInterface $em;
    private AccountRepository $accountRepository;
    
    public function __construct(EntityManagerInterface $em, AccountRepository $accountRepository)
    {
        $this->em = $em;
        $this->accountRepository = $accountRepository;
    }
    
    #[Route('/', name: 'admin_accounts', methods: ['GET'])]
    public function index(): Response
    {
        $accounts = $this->accountRepository->findAllWithClients();

        return $this->render('admin/accounts/index.html.twig', [
            'accounts' => $accounts,
        ]);
    }

    #[Route('/{id}', name: 'admin_account_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $account = $this->accountRepository->find($id);
        
        if (!$account) {
            $this->addFlash('error', 'Compte non trouvé');
            return $this->redirectToRoute('admin_accounts');
        }

        return $this->render('admin/accounts/show.html.twig', [
            'account' => $account,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_account_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $account = $this->accountRepository->find($id);
        
        if (!$account) {
            $this->addFlash('error', 'Compte non trouvé');
            return $this->redirectToRoute('admin_accounts');
        }

        // Pour l'instant, version simplifiée sans formulaire
        if ($request->isMethod('POST')) {
            $type = $request->request->get('type');
            $overdraftLimit = $request->request->get('overdraftLimit');
            
            if ($type && $overdraftLimit) {
                $account->setType($type);
                $account->setOverdraftLimit($overdraftLimit);
                
                $this->em->flush();
                
                $this->addFlash('success', 'Compte mis à jour avec succès.');
                return $this->redirectToRoute('admin_account_show', ['id' => $id]);
            }
        }

        return $this->render('admin/accounts/edit.html.twig', [
            'account' => $account,
        ]);
    }

    #[Route('/{id}/toggle-status', name: 'admin_account_toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request, int $id): Response
    {
        $account = $this->accountRepository->find($id);
        
        if (!$account) {
            $this->addFlash('error', 'Compte non trouvé');
            return $this->redirectToRoute('admin_accounts');
        }

        if ($this->isCsrfTokenValid('toggle-status'.$id, $request->request->get('_token'))) {
            $account->setIsActive(!$account->isActive());
            $this->em->flush();

            $status = $account->isActive() ? 'activé' : 'désactivé';
            $this->addFlash('success', "Compte {$status} avec succès.");
        } else {
            $this->addFlash('error', 'Token CSRF invalide');
        }

        return $this->redirectToRoute('admin_account_show', ['id' => $id]);
    }

    #[Route('/{id}/delete', name: 'admin_account_delete', methods: ['POST'])]
public function delete(Request $request, int $id): Response
{
    $account = $this->accountRepository->find($id);
    
    if (!$account) {
        $this->addFlash('error', 'Compte non trouvé');
        return $this->redirectToRoute('admin_accounts');
    }

    if ($this->isCsrfTokenValid('delete'.$id, $request->request->get('_token'))) {
        // Vérifier que le solde est à 0
        if ($account->getBalance() != 0) {
            $this->addFlash('error', 
                sprintf('Impossible de supprimer. Solde restant: %s €', 
                    number_format((float)$account->getBalance(), 2, ',', ' ')));
            return $this->redirectToRoute('admin_account_show', ['id' => $id]);
        }
        
        // Vérifier s'il y a des transactions
        $transactionsCount = $account->getTransactions()->count();
        if ($transactionsCount > 0) {
            $this->addFlash('warning', 
                sprintf('Ce compte a %d transaction(s). Supprimez-les d\'abord.', $transactionsCount));
            return $this->redirectToRoute('admin_account_transactions', ['id' => $id]);
        }
        
        // Supprimer le compte
        $this->em->remove($account);
        $this->em->flush();
        
        $this->addFlash('success', 'Compte supprimé avec succès.');
        return $this->redirectToRoute('admin_accounts');
    } else {
        $this->addFlash('error', 'Token CSRF invalide');
    }

    return $this->redirectToRoute('admin_account_show', ['id' => $id]);
}
    #[Route('/client/{clientId}/new', name: 'admin_account_new', methods: ['GET', 'POST'])]
    public function new(Request $request, int $clientId): Response
    {
        $client = $this->em->getRepository(Client::class)->find($clientId);
        
        if (!$client) {
            $this->addFlash('error', 'Client non trouvé.');
            return $this->redirectToRoute('admin_clients');
        }

        // Version simplifiée sans formulaire
        if ($request->isMethod('POST')) {
            $type = $request->request->get('type');
            $overdraftLimit = $request->request->get('overdraftLimit', 0);
            
            if ($type) {
                $account = new Account();
                $account->setClient($client);
                $account->setType($type);
                $account->setOverdraftLimit($overdraftLimit);
                
                $this->em->persist($account);
                $this->em->flush();

                $this->addFlash('success', 'Compte créé avec succès.');
                return $this->redirectToRoute('admin_client_show', ['id' => $clientId]);
            }
        }

        return $this->render('admin/accounts/new.html.twig', [
            'client' => $client,
        ]);
    }
    #[Route('/{id}/transactions', name: 'admin_account_transactions', methods: ['GET'])]
public function transactions(int $id): Response
{
    $account = $this->accountRepository->find($id);
    
    if (!$account) {
        $this->addFlash('error', 'Compte non trouvé');
        return $this->redirectToRoute('admin_accounts');
    }
    
    return $this->render('admin/accounts/transactions.html.twig', [
        'account' => $account,
        'transactions' => $account->getTransactions(),
    ]);
}

#[Route('/{id}/transactions/delete-all', name: 'admin_account_delete_transactions', methods: ['POST'])]
public function deleteAllTransactions(Request $request, int $id): Response
{
    $account = $this->accountRepository->find($id);
    
    if (!$account) {
        $this->addFlash('error', 'Compte non trouvé');
        return $this->redirectToRoute('admin_accounts');
    }

    if ($this->isCsrfTokenValid('delete-transactions'.$id, $request->request->get('_token'))) {
        $transactions = $account->getTransactions();
        $count = $transactions->count();
        
        if ($count === 0) {
            $this->addFlash('warning', 'Ce compte n\'a aucune transaction.');
            return $this->redirectToRoute('admin_account_transactions', ['id' => $id]);
        }
        
        // Supprimer toutes les transactions
        foreach ($transactions as $transaction) {
            $this->em->remove($transaction);
        }
        
        $this->em->flush();
        
        $this->addFlash('success', "{$count} transaction(s) supprimée(s) avec succès.");
        
        // Rediriger selon l'action demandée
        if ($request->request->has('redirect_to_delete')) {
            return $this->redirectToRoute('admin_account_show', ['id' => $id]);
        } else {
            return $this->redirectToRoute('admin_account_transactions', ['id' => $id]);
        }
    }

    return $this->redirectToRoute('admin_account_show', ['id' => $id]);
}

#[Route('/{accountId}/transaction/{transactionId}/delete', name: 'admin_transaction_delete', methods: ['POST'])]
public function deleteTransaction(Request $request, int $accountId, int $transactionId): Response
{
    $account = $this->accountRepository->find($accountId);
    
    if (!$account) {
        $this->addFlash('error', 'Compte non trouvé');
        return $this->redirectToRoute('admin_accounts');
    }
    
    $transaction = $this->em->getRepository(\App\Entity\Transaction::class)->find($transactionId);
    
    if (!$transaction || $transaction->getAccount()->getId() !== $account->getId()) {
        $this->addFlash('error', 'Transaction non trouvée');
        return $this->redirectToRoute('admin_account_transactions', ['id' => $accountId]);
    }

    if ($this->isCsrfTokenValid('delete-transaction'.$transactionId, $request->request->get('_token'))) {
        $this->em->remove($transaction);
        $this->em->flush();
        
        $this->addFlash('success', 'Transaction supprimée avec succès.');
    }

    return $this->redirectToRoute('admin_account_transactions', ['id' => $accountId]);
}
}