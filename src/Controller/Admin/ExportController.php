<?php

namespace App\Controller\Admin;

use App\Service\FinancialReportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/export')]
class ExportController extends AbstractController
{
    private FinancialReportService $financialReportService;

    public function __construct(FinancialReportService $financialReportService)
    {
        $this->financialReportService = $financialReportService;
    }

    #[Route('/financial-report', name: 'admin_export_financial_report', methods: ['GET'])]
    public function exportFinancialReport(Request $request): Response
    {
        $startDateStr = $request->query->get('start_date');
        $endDateStr = $request->query->get('end_date');
        $format = $request->query->get('format', 'csv');

        // Gestion des dates
        $startDate = $startDateStr ? new \DateTime($startDateStr) : new \DateTime('-1 month');
        $endDate = $endDateStr ? new \DateTime($endDateStr) : new \DateTime();
        $endDate->setTime(23, 59, 59);

        // Obtenir les données
        $financialStats = $this->financialReportService->getFinancialStats();
        $detailedReport = $this->financialReportService->getDetailedReport($startDate, $endDate);

        if ($format === 'csv') {
            return $this->exportToCsv($financialStats, $detailedReport, $startDate, $endDate);
        }

        // Par défaut, retourne au rapport
        return $this->redirectToRoute('admin_financial_reports');
    }

    private function exportToCsv(array $financialStats, array $detailedReport, \DateTimeInterface $startDate, \DateTimeInterface $endDate): Response
    {
        // Nom du fichier
        $filename = sprintf('rapport-financier-%s-%s.csv', 
            $startDate->format('Y-m-d'), 
            $endDate->format('Y-m-d')
        );

        // En-têtes CSV avec BOM UTF-8 pour Excel
        $csvContent = "\xEF\xBB\xBF"; // BOM UTF-8 pour Excel
        
        // En-tête du rapport
        $csvContent .= "RAPPORT FINANCIER\n";
        $csvContent .= sprintf("Période: %s - %s\n\n", 
            $startDate->format('d/m/Y'), 
            $endDate->format('d/m/Y')
        );

        // Section 1: Statistiques générales
        $csvContent .= "STATISTIQUES GÉNÉRALES\n";
        $csvContent .= "Métrique;Valeur;Description\n";
        $csvContent .= sprintf("Solde total;%s €;Solde combiné de tous les comptes actifs\n", 
            number_format($financialStats['total_balance'], 2, ',', ' ')
        );
        $csvContent .= sprintf("Dépôts totaux;%s €;Total des dépôts sur la période\n", 
            number_format($detailedReport['transactions']['deposits'], 2, ',', ' ')
        );
        $csvContent .= sprintf("Retraits totaux;%s €;Total des retraits sur la période\n", 
            number_format($detailedReport['transactions']['withdrawals'], 2, ',', ' ')
        );
        $csvContent .= sprintf("Flux net;%s €;Différence entre dépôts et retraits\n", 
            number_format($detailedReport['net_flow'], 2, ',', ' ')
        );
        $csvContent .= sprintf("Comptes actifs;%d;Nombre de comptes bancaires actifs\n", 
            $financialStats['active_accounts_count']
        );
        $csvContent .= sprintf("Nouveaux comptes;%d;Comptes créés sur la période\n", 
            $detailedReport['new_accounts']
        );
        $csvContent .= sprintf("Revenus services;%s €;Revenus mensuels des services actifs\n", 
            number_format($detailedReport['service_revenue'], 2, ',', ' ')
        );
        $csvContent .= sprintf("Transactions totales;%d;Nombre total de transactions sur la période\n\n", 
            $detailedReport['transactions']['total_transactions']
        );

        // Section 2: Détails des transactions
        $csvContent .= "ANALYSE DES TRANSACTIONS\n";
        $csvContent .= "Type;Montant;Pourcentage\n";
        
        $total = $detailedReport['transactions']['deposits'] + $detailedReport['transactions']['withdrawals'];
        $depositPercent = $total > 0 ? ($detailedReport['transactions']['deposits'] / $total) * 100 : 0;
        $withdrawalPercent = $total > 0 ? ($detailedReport['transactions']['withdrawals'] / $total) * 100 : 0;
        
        $csvContent .= sprintf("Dépôts;%s €;%.2f %%\n", 
            number_format($detailedReport['transactions']['deposits'], 2, ',', ' '),
            $depositPercent
        );
        $csvContent .= sprintf("Retraits;%s €;%.2f %%\n\n", 
            number_format($detailedReport['transactions']['withdrawals'], 2, ',', ' '),
            $withdrawalPercent
        );

        // Section 3: Recommandations
        $csvContent .= "RECOMMANDATIONS\n";
        if ($detailedReport['net_flow'] >= 0) {
            $csvContent .= "Flux net positif. Excellente santé financière.\n";
        } else {
            $csvContent .= "Flux net négatif. Surveiller les retraits.\n";
        }

        // Date de génération
        $csvContent .= sprintf("\nGénéré le: %s", date('d/m/Y H:i:s'));

        // Créer la réponse
        $response = new Response($csvContent);
        
        // Définir les en-têtes pour le téléchargement
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );
        
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}