<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Client;
use App\Entity\Service;
use App\Entity\Transaction;
use App\Form\ServiceType;
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
    #[Route('/', name: 'admin_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        $clientsCount      = $em->getRepository(Client::class)->count([]);
        $servicesCount     = $em->getRepository(Service::class)->count([]);
        $transactionsCount = $em->getRepository(Transaction::class)->count([]);

        $recentTransactions = $em->getRepository(Transaction::class)
            ->findBy([], ['createdAt' => 'DESC'], 5);

        return $this->render('admin/dashboard/index.html.twig', [
            'clientsCount'       => $clientsCount,
            'servicesCount'      => $servicesCount,
            'transactionsCount' => $transactionsCount,
            'recentTransactions' => $recentTransactions,
        ]);
    }

    #[Route('/services', name: 'admin_services')]
    public function services(EntityManagerInterface $em): Response
    {
        return $this->render('admin/services/index.html.twig', [
            'services' => $em->getRepository(Service::class)->findAll(),
        ]);
    }

    #[Route('/services/new', name: 'admin_service_new', methods: ['GET', 'POST'])]
    public function newService(Request $request, EntityManagerInterface $em): Response
    {
        $service = new Service();
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($service);
            $em->flush();

            $this->addFlash('success', 'Service créé avec succès !');
            return $this->redirectToRoute('admin_services');
        }

        return $this->render('admin/services/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/services/{id}/edit', name: 'admin_service_edit', methods: ['GET', 'POST'])]
    public function editService(Request $request, Service $service, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Service modifié avec succès !');
            return $this->redirectToRoute('admin_services');
        }

        return $this->render('admin/services/edit.html.twig', [
            'form'    => $form->createView(),
            'service' => $service,
        ]);
    }

    #[Route('/services/{id}', name: 'admin_service_delete', methods: ['POST'])]
    public function deleteService(Request $request, Service $service, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$service->getId(), $request->request->get('_token'))) {
            $em->remove($service);
            $em->flush();
            $this->addFlash('success', 'Service supprimé avec succès !');
        }

        return $this->redirectToRoute('admin_services');
    }

    #[Route('/admin/client/{id}', name: 'admin_dashboard_client_show')] 
    public function showClient(Client $client): Response
    {
        return $this->render('admin/clients/show.html.twig', ['client' => $client]);
    }
}