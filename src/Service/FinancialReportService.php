<?php

namespace App\Service;

use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;

class FinancialReportService
{
    private EntityManagerInterface $entityManager;
    private AccountRepository $accountRepository;
    private TransactionRepository $transactionRepository;
    private ServiceRepository $serviceRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        AccountRepository $accountRepository,
        TransactionRepository $transactionRepository,
        ServiceRepository $serviceRepository
    ) {
        $this->entityManager = $entityManager;
        $this->accountRepository = $accountRepository;
        $this->transactionRepository = $transactionRepository;
        $this->serviceRepository = $serviceRepository;
    }

    /**
     * Récupère les statistiques financières générales
     */
    public function getFinancialStats(): array
    {
        try {
            return [
                'total_balance' => $this->getTotalBalance(),
                'total_deposits' => $this->getTotalDeposits(),
                'total_withdrawals' => $this->getTotalWithdrawals(),
                'active_accounts_count' => $this->getActiveAccountsCount(),
                'total_monthly_service_revenue' => $this->getTotalMonthlyServiceRevenue(),
            ];
        } catch (\Exception $e) {
            // En cas d'erreur, retourne des valeurs par défaut
            return [
                'total_balance' => 0.0,
                'total_deposits' => 0.0,
                'total_withdrawals' => 0.0,
                'active_accounts_count' => 0,
                'total_monthly_service_revenue' => 0.0,
            ];
        }
    }

    /**
     * Solde total de tous les comptes actifs
     */
    public function getTotalBalance(): float
    {
        try {
            $result = $this->accountRepository->createQueryBuilder('a')
                ->select('COALESCE(SUM(a.balance), 0) as total')
                ->where('a.isActive = true')
                ->getQuery()
                ->getSingleScalarResult();

            return (float) $result;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Total des dépôts
     */
    public function getTotalDeposits(): float
    {
        try {
            $result = $this->transactionRepository->createQueryBuilder('t')
                ->select('COALESCE(SUM(t.amount), 0) as total')
                ->where('t.type = :type')
                ->setParameter('type', 'deposit') // Utilisez la chaîne directement
                ->getQuery()
                ->getSingleScalarResult();

            return (float) $result;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Total des retraits
     */
    public function getTotalWithdrawals(): float
    {
        try {
            $result = $this->transactionRepository->createQueryBuilder('t')
                ->select('COALESCE(SUM(t.amount), 0) as total')
                ->where('t.type = :type')
                ->setParameter('type', 'withdrawal') // Utilisez la chaîne directement
                ->getQuery()
                ->getSingleScalarResult();

            return (float) $result;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Nombre de comptes actifs
     */
    public function getActiveAccountsCount(): int
    {
        try {
            return $this->accountRepository->count(['isActive' => true]);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Revenu mensuel total des services
     */
    public function getTotalMonthlyServiceRevenue(): float
    {
        try {
            $result = $this->serviceRepository->createQueryBuilder('s')
                ->select('COALESCE(SUM(s.price), 0) as total')
                ->where('s.isActive = true')
                ->getQuery()
                ->getSingleScalarResult();

            return (float) $result;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Transactions récentes
     */
    public function getRecentTransactions(int $limit = 10): array
    {
        try {
            return $this->transactionRepository->createQueryBuilder('t')
                ->leftJoin('t.account', 'a')
                ->leftJoin('t.client', 'c')
                ->addSelect('a', 'c')
                ->orderBy('t.createdAt', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Services les plus populaires
     */
    public function getTopServices(int $limit = 5): array
    {
        try {
            return $this->serviceRepository->createQueryBuilder('s')
                ->where('s.isActive = true')
                ->orderBy('s.name', 'ASC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Rapport financier détaillé
     */
    public function getDetailedReport(\DateTimeInterface $startDate = null, \DateTimeInterface $endDate = null): array
    {
        if (!$startDate) {
            $startDate = new \DateTime('-1 month');
        }
        if (!$endDate) {
            $endDate = new \DateTime();
        }

        try {
            // Transactions par période
            $transactionsQuery = $this->transactionRepository->createQueryBuilder('t')
                ->select([
                    'COUNT(t.id) as total_transactions',
                    'SUM(CASE WHEN t.type = :deposit THEN t.amount ELSE 0 END) as deposits',
                    'SUM(CASE WHEN t.type = :withdrawal THEN t.amount ELSE 0 END) as withdrawals',
                ])
                ->where('t.createdAt BETWEEN :start AND :end')
                ->setParameters([
                    'deposit' => 'deposit', // Chaîne directement
                    'withdrawal' => 'withdrawal', // Chaîne directement
                    'start' => $startDate,
                    'end' => $endDate
                ])
                ->getQuery()
                ->getSingleResult();

            // Assurez-vous que les clés existent
            $deposits = isset($transactionsQuery['deposits']) ? (float) $transactionsQuery['deposits'] : 0.0;
            $withdrawals = isset($transactionsQuery['withdrawals']) ? (float) $transactionsQuery['withdrawals'] : 0.0;
            $totalTransactions = isset($transactionsQuery['total_transactions']) ? (int) $transactionsQuery['total_transactions'] : 0;

            // Nouveaux comptes
            $newAccounts = (int) $this->accountRepository->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.createdAt BETWEEN :start AND :end')
                ->setParameter('start', $startDate)
                ->setParameter('end', $endDate)
                ->getQuery()
                ->getSingleScalarResult();

            // Revenus des services
            $serviceRevenue = $this->getTotalMonthlyServiceRevenue();

            return [
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate
                ],
                'transactions' => [
                    'total_transactions' => $totalTransactions,
                    'deposits' => $deposits,
                    'withdrawals' => $withdrawals,
                ],
                'new_accounts' => $newAccounts,
                'service_revenue' => $serviceRevenue,
                'net_flow' => $deposits - $withdrawals
            ];
            
        } catch (\Exception $e) {
            // Retourne un rapport vide en cas d'erreur
            return [
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate
                ],
                'transactions' => [
                    'total_transactions' => 0,
                    'deposits' => 0.0,
                    'withdrawals' => 0.0,
                ],
                'new_accounts' => 0,
                'service_revenue' => 0.0,
                'net_flow' => 0.0
            ];
        }
    }

    /**
     * Statistiques quotidiennes des transactions (optionnel)
     */
    public function getDailyTransactionStats(int $days = 30): array
    {
        try {
            $startDate = new \DateTime("-{$days} days");
            
            $query = $this->entityManager->createQuery(
                'SELECT 
                    DATE(t.createdAt) as date,
                    COUNT(t.id) as transaction_count,
                    SUM(CASE WHEN t.type = :deposit THEN t.amount ELSE 0 END) as total_deposits,
                    SUM(CASE WHEN t.type = :withdrawal THEN t.amount ELSE 0 END) as total_withdrawals
                 FROM App\Entity\Transaction t
                 WHERE t.createdAt >= :startDate
                 GROUP BY DATE(t.createdAt)
                 ORDER BY date DESC'
            );
            
            $query->setParameters([
                'deposit' => 'deposit',
                'withdrawal' => 'withdrawal',
                'startDate' => $startDate
            ]);
            
            return $query->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }
}