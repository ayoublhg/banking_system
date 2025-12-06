<?php

namespace App\Controller\Client;

use App\Entity\Service;
use App\Entity\Transaction;
use App\Entity\Account;
use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/services')]
#[IsGranted('ROLE_USER')]
class ServiceSubscriptionController extends AbstractController
{
    #[Route('/{id}/subscribe', name: 'client_service_subscribe', methods: ['POST'])]
    public function subscribe(int $id, Request $request, EntityManagerInterface $em): Response
    {
        /** @var Client $client */
        $client = $this->getUser();
        
        // Récupérer le service via son ID
        $service = $em->getRepository(Service::class)->find($id);
        
        if (!$service) {
            throw $this->createNotFoundException('Service non trouvé');
        }
        
        // Vérifier si le service est actif
        if (!$service->getIsActive()) {
            $this->addFlash('error', 'Ce service n\'est pas disponible pour le moment.');
            return $this->redirectToRoute('client_services');
        }
        
        // Vérifier si déjà abonné
        if ($client->isSubscribedToService($service)) {
            $this->addFlash('info', 'Vous êtes déjà abonné à ce service.');
            return $this->redirectToRoute('client_services');
        }
        
        // Vérifier le token CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('subscribe' . $service->getId(), $submittedToken)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('client_services');
        }
        
        // Si le service est payant, vérifier le solde
        if ($service->getPrice() > 0) {
            // Trouver un compte courant avec du solde
            $checkingAccount = $em->getRepository(Account::class)
                ->findOneBy([
                    'client' => $client,
                    'type' => Account::TYPE_CHECKING,
                    'isActive' => true
                ]);
            
            if (!$checkingAccount) {
                $this->addFlash('error', 'Vous n\'avez pas de compte courant actif.');
                return $this->redirectToRoute('client_services');
            }
            
            // Vérifier le solde disponible
            $availableBalance = (float) $checkingAccount->getBalance() + (float) $checkingAccount->getOverdraftLimit();
            $servicePrice = (float) $service->getPrice();
            
            if ($servicePrice > $availableBalance) {
                $this->addFlash('error', sprintf(
                    'Solde insuffisant. Disponible: %s€, Service: %s€',
                    number_format($availableBalance, 2, ',', ' '),
                    number_format($servicePrice, 2, ',', ' ')
                ));
                return $this->redirectToRoute('client_services');
            }
            
            // Débiter le compte
            $newBalance = (float) $checkingAccount->getBalance() - $servicePrice;
            $checkingAccount->setBalance((string) $newBalance);
            
            // Créer une transaction
            $transaction = new Transaction();
            $transaction->setType(Transaction::TYPE_WITHDRAWAL);
            $transaction->setAmount((string) $servicePrice);
            $transaction->setDescription('Abonnement: ' . $service->getName());
            $transaction->setAccount($checkingAccount);
            $transaction->setClient($client);
            $transaction->setCreatedAt(new \DateTime());
            $em->persist($transaction);
        }
        
        // Ajouter l'abonnement
        $client->addSubscribedService($service);
        $em->flush();
        
        $message = $service->getPrice() > 0 
            ? sprintf('Vous vous êtes abonné à "%s" pour %s€/mois.', $service->getName(), $service->getPrice())
            : sprintf('Vous vous êtes abonné gratuitement à "%s".', $service->getName());
        
        $this->addFlash('success', $message);
        return $this->redirectToRoute('client_services');
    }
    
    #[Route('/{id}/unsubscribe', name: 'client_service_unsubscribe', methods: ['POST'])]
    public function unsubscribe(int $id, Request $request, EntityManagerInterface $em): Response
    {
        /** @var Client $client */
        $client = $this->getUser();
        
        // Récupérer le service via son ID
        $service = $em->getRepository(Service::class)->find($id);
        
        if (!$service) {
            throw $this->createNotFoundException('Service non trouvé');
        }
        
        // Vérifier le token CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('unsubscribe' . $service->getId(), $submittedToken)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('client_services');
        }
        
        if (!$client->isSubscribedToService($service)) {
            $this->addFlash('error', 'Vous n\'êtes pas abonné à ce service.');
            return $this->redirectToRoute('client_services');
        }
        
        $client->removeSubscribedService($service);
        $em->flush();
        
        $this->addFlash('success', sprintf('Vous vous êtes désabonné de "%s".', $service->getName()));
        return $this->redirectToRoute('client_services');
    }
    
    #[Route('/my-subscriptions', name: 'client_my_subscriptions', methods: ['GET'])]
    public function mySubscriptions(EntityManagerInterface $em): Response
    {
        /** @var Client $client */
        $client = $this->getUser();
        
        // Récupérer tous les services actifs
        $services = $em->getRepository(Service::class)
            ->findBy(['isActive' => true]);
        
        return $this->render('client/services/my_subscriptions.html.twig', [
            'client' => $client,
            'services' => $services,
        ]);
    }
}