<?php
namespace App\Controller\Client;

use App\Entity\Account;
use App\Entity\Transaction;
use App\Form\AccountRequestType;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[IsGranted('ROLE_USER')]
class AccountsController extends AbstractController
{
    #[Route('/client/accounts', name: 'client_accounts')]
    public function index(EntityManagerInterface $em): Response
    {
        $client = $this->getUser();
        
        if (!$client) {
            return $this->redirectToRoute('app_login');
        }
        
        $accounts = $em->getRepository(Account::class)
            ->findBy(['client' => $client, 'isActive' => true]);

        $totalBalance = array_sum(array_map(fn($a) => (float)$a->getBalance(), $accounts));

        return $this->render('client/accounts/index.html.twig', [
            'accounts'     => $accounts,
            'totalBalance' => $totalBalance,
        ]);
    }

    #[Route('/client/accounts/new', name: 'client_account_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\Client $client */
        $client = $this->getUser();
        
        if (!$client) {
            return $this->redirectToRoute('app_login');
        }
        
        $form = $this->createForm(AccountRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            // Crée le nouveau compte
            $account = new Account();
            $account->setClient($client);
            $account->setType($data['type']);
            $account->setBalance($data['initialDeposit'] ?? '0.00');
            $account->setOverdraftLimit('0.00');
            $account->setIsActive(true);
            $account->setCreatedAt(new \DateTime());
            $account->setAccountNumber($this->generateAccountNumber());
            
            $em->persist($account);
            $em->flush();

            // Transaction pour le dépôt initial
            if ($data['initialDeposit'] > 0) {
                $transaction = new Transaction();
                $transaction->setType(Transaction::TYPE_DEPOSIT);
                $transaction->setAmount((string)$data['initialDeposit']);
                $transaction->setDescription('Dépôt initial - Ouverture de compte');
                $transaction->setAccount($account);
                $transaction->setClient($client);
                $transaction->setCreatedAt(new \DateTime());
                
                $em->persist($transaction);
                $em->flush();
            }

            $this->addFlash('success', 'Votre nouveau compte a été créé avec succès !');
            return $this->redirectToRoute('client_accounts');
        }

        return $this->render('client/accounts/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/client/accounts/{id}', name: 'client_account_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $account = $em->getRepository(Account::class)->find($id);
        
        if (!$account) {
            throw $this->createNotFoundException('Compte non trouvé');
        }
        
        $user = $this->getUser();
        if ($account->getClient() !== $user && !in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $this->addFlash('error', 'Accès non autorisé à ce compte.');
            return $this->redirectToRoute('client_accounts');
        }
    
        $transactions = $em->getRepository(Transaction::class)
            ->findBy(['account' => $account], ['createdAt' => 'DESC'], 10);
    
        $balance = (float) $account->getBalance();
        $overdraft = (float) $account->getOverdraftLimit();
        $soldeDisponible = $balance + $overdraft;
    
        return $this->render('client/accounts/show.html.twig', [
            'account'        => $account,
            'transactions'   => $transactions,
            'soldeDisponible'=> $soldeDisponible,
        ]);
    }

    #[Route('/client/accounts/{id}/transactions', name: 'client_account_transactions', methods: ['GET'])]
    public function transactions(int $id, EntityManagerInterface $em): Response
    {
        $account = $em->getRepository(Account::class)->find($id);
        
        if (!$account) {
            throw $this->createNotFoundException('Compte non trouvé');
        }
        
        $user = $this->getUser();
        if ($account->getClient() !== $user && !in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $this->addFlash('error', 'Accès non autorisé à ce compte.');
            return $this->redirectToRoute('client_accounts');
        }

        $transactions = $em->getRepository(Transaction::class)
            ->findBy(['account' => $account], ['createdAt' => 'DESC']);

        return $this->render('client/accounts/transactions.html.twig', [
            'account'      => $account,
            'transactions' => $transactions,
        ]);
    }


    private function generateAccountNumber(): string
    {
        return 'FR76' . 
               str_pad((string)mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT) . 
               str_pad((string)mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT) . 
               str_pad((string)mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT) . 
               str_pad((string)mt_rand(10, 99), 2, '0', STR_PAD_LEFT);
    }
}