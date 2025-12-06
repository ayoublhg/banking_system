<?php

namespace App\Controller\Client;

use App\Entity\Account;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client')]
#[IsGranted('ROLE_CLIENT')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'client_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        $client = $this->getUser();

        $accounts = $em->getRepository(Account::class)
            ->findBy(['client' => $client, 'isActive' => true]);

        $totalBalance = array_sum(array_map(fn($a) => (float)$a->getBalance(), $accounts));

        $recentTransactions = $em->getRepository(Transaction::class)
            ->findBy(['client' => $client], ['createdAt' => 'DESC'], 5);

        return $this->render('client/dashboard/index.html.twig', [
            'accounts'          => $accounts,
            'totalBalance'      => $totalBalance,
            'recentTransactions' => $recentTransactions,
        ]);
    }

    #[Route('/services', name: 'client_services')]
    public function services(EntityManagerInterface $em): Response
    {
        return $this->render('client/services/index.html.twig', [
            'services' => $em->getRepository(\App\Entity\Service::class)
                ->findBy(['isActive' => true]),
        ]);
    }

    #[Route('/profile', name: 'client_profile')]
    public function profile(): Response
    {
        return $this->render('client/profile/index.html.twig');
    }
}