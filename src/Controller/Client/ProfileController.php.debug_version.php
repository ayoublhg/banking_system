<?php

namespace App\Controller\Client;

use App\Entity\Client;
use App\Form\ProfileType;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'client_profile')]
    public function index(): Response
    {
        /** @var Client $client */
        $client = $this->getUser();

        return $this->render('client/profile/index.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/edit', name: 'client_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var Client $client */
        $client = $this->getUser();

        $form = $this->createForm(ProfileType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès!');
            return $this->redirectToRoute('client_profile');
        }

        return $this->render('client/profile/edit.html.twig', [
            'form' => $form->createView(),
            'client' => $client,
        ]);
    }

    #[Route('/change-password', name: 'client_change_password', methods: ['GET', 'POST'])]
    public function changePassword(
      Request $request,
      UserPasswordHasherInterface $passwordHasher,
      EntityManagerInterface $em
    ): Response {
      $client = $this->getUser();
      $form = $this->createForm(ChangePasswordType::class);
      $form->handleRequest($request);

      if ($form->isSubmitted() && $form->isValid()) {
         $data = $form->getData();

        // DEBUG: Affiche ce que nous avons
        // dd([
        //     'currentPassword_from_form' => $data['currentPassword'] ?? 'NULL',
        //     'user_password_hash' => $client->getPassword(),
        //     'user_email' => $client->getEmail(),
        //     'isPasswordValid_test' => $passwordHasher->isPasswordValid($client, $data['currentPassword'] ?? ''),
        // ]);

        // 1. Vérifier que le mot de passe actuel est correct
        $currentPassword = $data['currentPassword'] ?? null;
        
        if (!$currentPassword) {
            $this->addFlash('error', 'Le mot de passe actuel est requis.');
            return $this->redirectToRoute('client_change_password');
        }
        
        // Version alternative de vérification
        if (!$passwordHasher->isPasswordValid($client, $currentPassword)) {
            $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
            
            // DEBUG: Vous pouvez activer cette ligne pour voir plus d'info
            // $this->addFlash('debug', 'Hash dans DB: ' . substr($client->getPassword(), 0, 20) . '...');
            
            return $this->redirectToRoute('client_change_password');
        }
        
        // 2. Récupérer le nouveau mot de passe
        $newPassword = $data['newPassword'] ?? null;
        if (!$newPassword) {
            $this->addFlash('error', 'Le nouveau mot de passe est requis.');
            return $this->redirectToRoute('client_change_password');
        }
        
        // 3. Vérifier que le nouveau mot de passe est différent de l'ancien
        if ($passwordHasher->isPasswordValid($client, $newPassword)) {
            $this->addFlash('error', 'Le nouveau mot de passe doit être différent de l\'ancien.');
            return $this->redirectToRoute('client_change_password');
        }
        
        // 4. Mettre à jour le mot de passe
        $hashedPassword = $passwordHasher->hashPassword($client, $newPassword);
        $client->setPassword($hashedPassword);
        
        $em->flush();

        // 5. Déconnecter l'utilisateur
        $this->addFlash('success', 'Votre mot de passe a été modifié avec succès. Veuillez vous reconnecter.');
        return $this->redirectToRoute('app_logout');
    }

    return $this->render('client/profile/change_password.html.twig', [
        'form' => $form->createView(),
    ]);
}
}