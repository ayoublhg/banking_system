<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher, 
        EntityManagerInterface $entityManager
    ): Response
    {
        // Si l'utilisateur est déjà connecté, rediriger vers la page appropriée
        if ($this->getUser()) {
            $user = $this->getUser();
            
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                return $this->redirectToRoute('admin_dashboard');
            }
            
            return $this->redirectToRoute('client_dashboard');
        }

        // Créer un nouveau client
        $client = new Client();
        $form = $this->createForm(RegistrationFormType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encoder le mot de passe
            $client->setPassword(
                $userPasswordHasher->hashPassword(
                    $client,
                    $form->get('plainPassword')->getData()
                )
            );

            // Définir les rôles par défaut
            $client->setRoles(['ROLE_USER']);

            // Enregistrer le client
            $entityManager->persist($client);
            $entityManager->flush();

            // Message de succès
            $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');

            // Rediriger vers la page de connexion
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}