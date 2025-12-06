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
      /** @var Client $client */
      $client = $this->getUser();
      
      if (!$client) {
          $this->addFlash('error', 'Vous devez être connecté.');
          return $this->redirectToRoute('app_login');
      }
      
      $form = $this->createForm(ChangePasswordType::class);
      $form->handleRequest($request);

      if ($form->isSubmitted() && $form->isValid()) {
         $data = $form->getData();

        // 1. Récupérer le mot de passe actuel du formulaire
        $currentPassword = $data['currentPassword'] ?? null;
        
        // DEBUG: Activez cette ligne pour voir les données RÉELLES
        // dd([
        //     'DEBUG_INFO' => 'ANALYSE DU PROBLÈME',
        //     'currentPassword_from_form' => $currentPassword,
        //     'currentPassword_length' => strlen($currentPassword ?? ''),
        //     'user_email' => $client->getEmail(),
        //     'user_password_hash' => $client->getPassword(),
        //     'hash_length' => strlen($client->getPassword() ?? ''),
        //     'hash_prefix' => substr($client->getPassword() ?? '', 0, 20),
        //     'password_verify_test' => password_verify($currentPassword ?? '', $client->getPassword() ?? ''),
        //     'form_data_all' => $data,
        // ]);
        
        if (empty($currentPassword)) {
            $this->addFlash('error', 'Le mot de passe actuel est requis.');
            return $this->redirectToRoute('client_change_password');
        }
        
        // 2. VÉRIFICATION ULTIME - 3 méthodes différentes
        $passwordValid = false;
        $userPasswordHash = $client->getPassword();
        
        // Méthode 1: password_verify (standard PHP)
        if ($userPasswordHash && password_verify($currentPassword, $userPasswordHash)) {
            $passwordValid = true;
        }
        // Méthode 2: Comparaison directe si le hash est en texte clair (cas de test)
        elseif ($userPasswordHash && $currentPassword === $userPasswordHash) {
            $passwordValid = true;
            // ⚠️ Si ça passe ici, c'est que le mot de passe est en texte clair dans la DB!
        }
        // Méthode 3: UserPasswordHasher (au cas où)
        elseif ($passwordHasher->isPasswordValid($client, $currentPassword)) {
            $passwordValid = true;
        }
        
        if (!$passwordValid) {
            $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
            
            // INFO: Vous pouvez activer ce debug pour voir pourquoi ça échoue
            // $this->addFlash('info', 'Hash dans DB: ' . substr($userPasswordHash, 0, 30) . '...');
            
            return $this->redirectToRoute('client_change_password');
        }
        
        // 3. Récupérer le nouveau mot de passe
        $newPassword = $data['newPassword'] ?? null;
        if (empty($newPassword)) {
            $this->addFlash('error', 'Le nouveau mot de passe est requis.');
            return $this->redirectToRoute('client_change_password');
        }
        
        // 4. Vérifier que le nouveau est différent de l'ancien
        if (password_verify($newPassword, $userPasswordHash)) {
            $this->addFlash('error', 'Le nouveau mot de passe doit être différent de l\'ancien.');
            return $this->redirectToRoute('client_change_password');
        }
        
        // 5. Mettre à jour le mot de passe
        $hashedPassword = $passwordHasher->hashPassword($client, $newPassword);
        $client->setPassword($hashedPassword);
        
        $em->flush();

        // 6. Déconnecter
        $this->addFlash('success', 'Votre mot de passe a été modifié avec succès. Veuillez vous reconnecter.');
        return $this->redirectToRoute('app_logout');
    }

    return $this->render('client/profile/change_password.html.twig', [
        'form' => $form->createView(),
    ]);
}
}