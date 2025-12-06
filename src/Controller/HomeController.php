<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EntityManagerInterface $em): Response
    {
        $stats = [];
        
        if ($this->getUser()) {
            $user = $this->getUser();
            $accounts = $em->getRepository(Account::class)->findBy(['client' => $user]);
            
            $stats = [
                'accountCount' => count($accounts),
                'totalBalance' => array_sum(array_map(fn($a) => (float)$a->getBalance(), $accounts)),
                'activeAccounts' => count(array_filter($accounts, fn($a) => $a->isActive())),
            ];
        }
        
        $services = $em->getRepository(Service::class)->findBy(['isActive' => true], [], 3);
        
        return $this->render('home/index.html.twig', [
            'stats' => $stats,
            'services' => $services,
            'user' => $this->getUser(),
        ]);
    }
    
    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }
    
    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('home/contact.html.twig');
    }
}