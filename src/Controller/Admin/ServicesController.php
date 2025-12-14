<?php

namespace App\Controller\Admin;

use App\Entity\Service;
use App\Form\ServiceType;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/services')]
#[IsGranted('ROLE_ADMIN')]
class ServicesController extends AbstractController
{
    private EntityManagerInterface $em;
    private ServiceRepository $serviceRepository;
    
    public function __construct(EntityManagerInterface $em, ServiceRepository $serviceRepository)
    {
        $this->em = $em;
        $this->serviceRepository = $serviceRepository;
    }
    
    #[Route('/', name: 'admin_services', methods: ['GET'])]
    public function index(): Response
    {
        $services = $this->serviceRepository->findAll();
        
        return $this->render('admin/services/index.html.twig', [
            'services' => $services,
        ]);
    }
    
    #[Route('/new', name: 'admin_service_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $service = new Service();
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($service);
            $this->em->flush();

            $this->addFlash('success', 'Service créé avec succès !');
            return $this->redirectToRoute('admin_services');
        }

        return $this->render('admin/services/new.html.twig', [
            'service' => $service,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id}', name: 'admin_service_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $service = $this->serviceRepository->find($id);
        
        if (!$service) {
            throw $this->createNotFoundException('Service non trouvé');
        }
        
        return $this->render('admin/services/show.html.twig', [
            'service' => $service,
        ]);
    }
    
    #[Route('/{id}/edit', name: 'admin_service_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $service = $this->serviceRepository->find($id);
        
        if (!$service) {
            throw $this->createNotFoundException('Service non trouvé');
        }
        
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Service modifié avec succès !');
            return $this->redirectToRoute('admin_services');
        }

        return $this->render('admin/services/edit.html.twig', [
            'service' => $service,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id}/delete', name: 'admin_service_delete', methods: ['POST'])] 
    public function delete(Request $request, int $id): Response 
    {
        $service = $this->serviceRepository->find($id);
        
        if (!$service) {
            throw $this->createNotFoundException('Service non trouvé');
        }

        if ($this->isCsrfTokenValid('delete'.$service->getId(), $request->request->get('_token'))) {
            try {
                // 1. Supprimer les relations avec les clients
                foreach ($service->getClients() as $client) {
                    $client->removeSubscribedService($service);
                }
                
                // 2. Supprimer le service
                $this->em->remove($service);
                $this->em->flush();
                
                $this->addFlash('success', 'Service supprimé avec succès');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('admin_services', [], Response::HTTP_SEE_OTHER);
    }
}