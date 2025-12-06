<?php
// src/Controller/Client/TransactionController.php - NOUVEAU FICHIER

namespace App\Controller\Client;

use App\Entity\Account;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/transactions')]
#[IsGranted('ROLE_USER')]
class TransactionController extends AbstractController
{
    #[Route('/', name: 'client_transactions_index')]
    public function index(): Response
    {
        return $this->render('client/transaction/index.html.twig');
    }

    #[Route('/deposit', name: 'client_transaction_deposit', methods: ['GET', 'POST'])]
    public function deposit(Request $request, EntityManagerInterface $em): Response
    {
        $client = $this->getUser();
        $accounts = $em->getRepository(Account::class)
            ->findBy(['client' => $client, 'isActive' => true]);

        if ($request->isMethod('POST')) {
            $accountId = $request->request->get('account');
            $amount = $request->request->get('amount');
            $description = $request->request->get('description', 'Dépôt');

            if (!$accountId || !is_numeric($amount) || $amount <= 0) {
                $this->addFlash('error', 'Données invalides.');
                return $this->redirectToRoute('client_transaction_deposit');
            }

            $account = $em->getRepository(Account::class)->find($accountId);
            
            if (!$account || $account->getClient() !== $client) {
                $this->addFlash('error', 'Compte invalide.');
                return $this->redirectToRoute('client_transaction_deposit');
            }

            // Créer la transaction
            $transaction = new Transaction();
            $transaction->setType(Transaction::TYPE_DEPOSIT);
            $transaction->setAmount($amount);
            $transaction->setDescription($description);
            $transaction->setAccount($account);
            $transaction->setClient($client);

            // Mettre à jour le solde
            $newBalance = bcadd($account->getBalance(), $amount, 2);
            $account->setBalance($newBalance);

            $em->persist($transaction);
            $em->flush();

            $this->addFlash('success', "Dépôt de {$amount}€ effectué avec succès !");
            return $this->redirectToRoute('client_account_show', ['id' => $account->getId()]);
        }

        return $this->render('client/transaction/deposit.html.twig', [
            'accounts' => $accounts,
        ]);
    }

    #[Route('/withdraw', name: 'client_transaction_withdraw', methods: ['GET', 'POST'])]
    public function withdraw(Request $request, EntityManagerInterface $em): Response
    {
        $client = $this->getUser();
        $accounts = $em->getRepository(Account::class)
            ->findBy(['client' => $client, 'isActive' => true]);

        if ($request->isMethod('POST')) {
            $accountId = $request->request->get('account');
            $amount = $request->request->get('amount');
            $description = $request->request->get('description', 'Retrait');

            if (!$accountId || !is_numeric($amount) || $amount <= 0) {
                $this->addFlash('error', 'Données invalides.');
                return $this->redirectToRoute('client_transaction_withdraw');
            }

            $account = $em->getRepository(Account::class)->find($accountId);
            
            if (!$account || $account->getClient() !== $client) {
                $this->addFlash('error', 'Compte invalide.');
                return $this->redirectToRoute('client_transaction_withdraw');
            }

            // Vérifier le solde disponible (solde + découvert)
            $balance = (float) $account->getBalance();
            $overdraft = (float) $account->getOverdraftLimit();
            $available = $balance + $overdraft;

            if ($amount > $available) {
                $this->addFlash('error', 'Fonds insuffisants. Solde disponible : ' . $available . '€');
                return $this->redirectToRoute('client_transaction_withdraw');
            }

            // Créer la transaction
            $transaction = new Transaction();
            $transaction->setType(Transaction::TYPE_WITHDRAWAL);
            $transaction->setAmount($amount);
            $transaction->setDescription($description);
            $transaction->setAccount($account);
            $transaction->setClient($client);

            // Mettre à jour le solde
            $newBalance = bcsub($account->getBalance(), $amount, 2);
            $account->setBalance($newBalance);

            $em->persist($transaction);
            $em->flush();

            $this->addFlash('success', "Retrait de {$amount}€ effectué avec succès !");
            return $this->redirectToRoute('client_account_show', ['id' => $account->getId()]);
        }

        return $this->render('client/transaction/withdraw.html.twig', [
            'accounts' => $accounts,
        ]);
    }

    #[Route('/transfer', name: 'client_transaction_transfer', methods: ['GET', 'POST'])]
    public function transfer(Request $request, EntityManagerInterface $em): Response
    {
        $client = $this->getUser();
        $accounts = $em->getRepository(Account::class)
            ->findBy(['client' => $client, 'isActive' => true]);

        if ($request->isMethod('POST')) {
            $fromAccountId = $request->request->get('from_account');
            $toAccountNumber = $request->request->get('to_account');
            $amount = $request->request->get('amount');
            $description = $request->request->get('description', 'Virement');

            if (!$fromAccountId || !$toAccountNumber || !is_numeric($amount) || $amount <= 0) {
                $this->addFlash('error', 'Données invalides.');
                return $this->redirectToRoute('client_transaction_transfer');
            }

            // Compte source
            $fromAccount = $em->getRepository(Account::class)->find($fromAccountId);
            if (!$fromAccount || $fromAccount->getClient() !== $client) {
                $this->addFlash('error', 'Compte source invalide.');
                return $this->redirectToRoute('client_transaction_transfer');
            }

            // Compte destination
            $toAccount = $em->getRepository(Account::class)->findOneBy([
                'accountNumber' => $toAccountNumber,
                'isActive' => true
            ]);
            
            if (!$toAccount) {
                $this->addFlash('error', 'Compte destination non trouvé.');
                return $this->redirectToRoute('client_transaction_transfer');
            }

            // Vérifier le solde disponible
            $balance = (float) $fromAccount->getBalance();
            $overdraft = (float) $fromAccount->getOverdraftLimit();
            $available = $balance + $overdraft;

            if ($amount > $available) {
                $this->addFlash('error', 'Fonds insuffisants. Solde disponible : ' . $available . '€');
                return $this->redirectToRoute('client_transaction_transfer');
            }

            // Transaction de retrait (compte source)
            $withdrawal = new Transaction();
            $withdrawal->setType(Transaction::TYPE_TRANSFER);
            $withdrawal->setAmount($amount);
            $withdrawal->setDescription($description . ' → Vers: ' . $toAccount->getAccountNumber());
            $withdrawal->setAccount($fromAccount);
            $withdrawal->setClient($client);

            // Transaction de dépôt (compte destination)
            $deposit = new Transaction();
            $deposit->setType(Transaction::TYPE_TRANSFER);
            $deposit->setAmount($amount);
            $deposit->setDescription($description . ' ← De: ' . $fromAccount->getAccountNumber());
            $deposit->setAccount($toAccount);
            $deposit->setClient($toAccount->getClient());

            // Mettre à jour les soldes
            $fromAccount->setBalance(bcsub($fromAccount->getBalance(), $amount, 2));
            $toAccount->setBalance(bcadd($toAccount->getBalance(), $amount, 2));

            $em->persist($withdrawal);
            $em->persist($deposit);
            $em->flush();

            $this->addFlash('success', "Virement de {$amount}€ effectué avec succès !");
            return $this->redirectToRoute('client_account_show', ['id' => $fromAccount->getId()]);
        }

        return $this->render('client/transaction/transfer.html.twig', [
            'accounts' => $accounts,
        ]);
    }
}