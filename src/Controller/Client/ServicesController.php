<?php

namespace App\Controller\Client;

use App\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/services')]
#[IsGranted('ROLE_USER')]
class ServicesController extends AbstractController
{
    #[Route('/', name: 'client_services')]
    public function index(EntityManagerInterface $em): Response
    {
        $client = $this->getUser();
        $services = $em->getRepository(Service::class)
            ->findBy(['isActive' => true]);

        return $this->render('client/services/index.html.twig', [
            'services' => $services,
            'client' => $client,
        ]);
    }

  
}