<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use App\Entity\Service;
use App\Entity\Transaction;
use App\Service\FinancialReportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    private FinancialReportService $financialReportService;

    public function __construct(FinancialReportService $financialReportService)
    {
        $this->financialReportService = $financialReportService;
    }

    #[Route('/', name: 'admin_dashboard')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // CORRECTION: Utilisez $entityManager, pas $em
        $clientsCount = $entityManager->getRepository(Client::class)->count([]);
        $servicesCount = $entityManager->getRepository(Service::class)->count([]);
        $transactionsCount = $entityManager->getRepository(Transaction::class)->count([]);
        
        // Récupérer les statistiques financières
        $financialStats = $this->financialReportService->getFinancialStats();
        
        // Transactions récentes
        $recentTransactions = $this->financialReportService->getRecentTransactions(5);
        
        // Services populaires (version simplifiée)
        $topServices = $entityManager->getRepository(Service::class)
            ->createQueryBuilder('s')
            ->where('s.isActive = true')
            ->orderBy('s.name', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('admin/dashboard/index.html.twig', [
            'clientsCount' => $clientsCount,
            'servicesCount' => $servicesCount,
            'transactionsCount' => $transactionsCount,
            'recentTransactions' => $recentTransactions,
            'financialStats' => $financialStats,
            'topServices' => $topServices,
        ]);
    }

    #[Route('/transactions', name: 'admin_transactions')]
    public function transactions(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 20;
        
        // Récupérer toutes les transactions
        $allTransactions = $this->financialReportService->getRecentTransactions(1000); // Grand nombre pour pagination
        
        // Pagination manuelle
        $totalTransactions = count($allTransactions);
        $offset = ($page - 1) * $limit;
        $transactions = array_slice($allTransactions, $offset, $limit);
        
        return $this->render('admin/dashboard/transactions.html.twig', [
            'transactions' => $transactions,
            'current_page' => $page,
            'total_pages' => ceil($totalTransactions / $limit),
            'total_transactions' => $totalTransactions,
        ]);
    }

    #[Route('/financial-reports', name: 'admin_financial_reports')]
    public function financialReports(Request $request): Response
    {
        // Paramètres de date (par défaut: dernier mois)
        $startDate = $request->query->get('start_date') 
            ? new \DateTime($request->query->get('start_date'))
            : new \DateTime('-1 month');
            
        $endDate = $request->query->get('end_date')
            ? new \DateTime($request->query->get('end_date'))
            : new \DateTime();

        try {
            // Rapport détaillé
            $detailedReport = $this->financialReportService->getDetailedReport($startDate, $endDate);
            
            // Statistiques générales
            $financialStats = $this->financialReportService->getFinancialStats();
            
            // Ajoutez des valeurs par défaut si nécessaire
            if (!isset($detailedReport['transactions']['deposits'])) {
                $detailedReport['transactions']['deposits'] = 0;
            }
            if (!isset($detailedReport['transactions']['withdrawals'])) {
                $detailedReport['transactions']['withdrawals'] = 0;
            }
            if (!isset($detailedReport['transactions']['total_transactions'])) {
                $detailedReport['transactions']['total_transactions'] = 0;
            }
            
            $detailedReport['net_flow'] = ($detailedReport['transactions']['deposits'] ?? 0) - ($detailedReport['transactions']['withdrawals'] ?? 0);
            
        } catch (\Exception $e) {
            // En cas d'erreur, retournez des valeurs par défaut
            $detailedReport = [
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate
                ],
                'transactions' => [
                    'total_transactions' => 0,
                    'deposits' => 0,
                    'withdrawals' => 0
                ],
                'new_accounts' => 0,
                'service_revenue' => 0,
                'net_flow' => 0
            ];
            
            $financialStats = [
                'total_balance' => 0,
                'total_deposits' => 0,
                'total_withdrawals' => 0,
                'active_accounts_count' => 0,
                'total_monthly_service_revenue' => 0
            ];
        }

        return $this->render('admin/dashboard/financial_reports.html.twig', [
            'detailedReport' => $detailedReport,
            'financialStats' => $financialStats,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    #[Route('/financial-reports/export', name: 'admin_financial_reports_export')]
    public function exportFinancialReports(Request $request): Response
    {
        $startDate = new \DateTime($request->query->get('start_date', '-1 month'));
        $endDate = new \DateTime($request->query->get('end_date', 'now'));
        
        try {
            $report = $this->financialReportService->getDetailedReport($startDate, $endDate);
            
            // Assurez-vous que toutes les clés existent
            $deposits = $report['transactions']['deposits'] ?? 0;
            $withdrawals = $report['transactions']['withdrawals'] ?? 0;
            $totalTransactions = $report['transactions']['total_transactions'] ?? 0;
            $newAccounts = $report['new_accounts'] ?? 0;
            $serviceRevenue = $report['service_revenue'] ?? 0;
            $netFlow = $deposits - $withdrawals;
            
            // Générer un CSV
            $csvData = "Rapport Financier du {$startDate->format('d/m/Y')} au {$endDate->format('d/m/Y')}\n\n";
            $csvData .= "Métrique,Valeur\n";
            $csvData .= "Période,{$startDate->format('d/m/Y')} - {$endDate->format('d/m/Y')}\n";
            $csvData .= "Transactions totales,{$totalTransactions}\n";
            $csvData .= "Dépôts,{$deposits} €\n";
            $csvData .= "Retraits,{$withdrawals} €\n";
            $csvData .= "Nouveaux comptes,{$newAccounts}\n";
            $csvData .= "Revenus services,{$serviceRevenue} €\n";
            $csvData .= "Flux net,{$netFlow} €\n";
            $csvData .= "\nGénéré le," . date('d/m/Y H:i:s');
            
        } catch (\Exception $e) {
            $csvData = "Rapport Financier\n\n";
            $csvData .= "Erreur : Impossible de générer le rapport.\n";
            $csvData .= "Message : " . $e->getMessage() . "\n";
        }
        
        $response = new Response($csvData);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="rapport-financier-' . date('Y-m-d') . '.csv"');
        
        return $response;
    }

    #[Route('/clients/{id}', name: 'admin_client_show', methods: ['GET'])] 
public function showClient(int $id, EntityManagerInterface $entityManager): Response
{
    // Récupère le client par son ID
    $client = $entityManager->getRepository(Client::class)->find($id);
    
    // Vérifie si le client existe
    if (!$client) {
        throw $this->createNotFoundException('Client non trouvé');
    }
    
    // Récupère les comptes du client avec la relation
    $accounts = $entityManager->getRepository(\App\Entity\Account::class)
        ->createQueryBuilder('a')
        ->where('a.client = :client')
        ->setParameter('client', $client)
        ->orderBy('a.createdAt', 'DESC')
        ->getQuery()
        ->getResult();
    
    // Récupère les transactions du client
    $transactions = $entityManager->getRepository(\App\Entity\Transaction::class)
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
}