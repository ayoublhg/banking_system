<?php

namespace App\Controller\Admin;

use App\Service\FinancialReportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/financial-reports')]
class FinancialReportController extends AbstractController
{
    private FinancialReportService $financialReportService;

    public function __construct(FinancialReportService $financialReportService)
    {
        $this->financialReportService = $financialReportService;
    }

    #[Route('/', name: 'admin_financial_reports', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Récupérer les dates depuis la requête ou utiliser des valeurs par défaut
        $startDateStr = $request->query->get('start_date');
        $endDateStr = $request->query->get('end_date');

        // Gestion des dates
        $startDate = $startDateStr ? new \DateTime($startDateStr) : new \DateTime('-1 month');
        $endDate = $endDateStr ? new \DateTime($endDateStr) : new \DateTime();
        
        // S'assurer que la date de fin est à 23:59:59 pour inclure toute la journée
        $endDate->setTime(23, 59, 59);

        // Obtenir les statistiques
        $financialStats = $this->financialReportService->getFinancialStats();
        $detailedReport = $this->financialReportService->getDetailedReport($startDate, $endDate);

        return $this->render('admin/dashboard/financial_reports.html.twig', [
            'financialStats' => $financialStats,
            'detailedReport' => $detailedReport,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    #[Route('/export', name: 'admin_financial_reports_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        // Logique d'export (à implémenter)
        return $this->redirectToRoute('admin_financial_reports');
    }
}