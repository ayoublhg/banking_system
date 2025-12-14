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
        $newPasswordData = $data['plainPassword'] ?? null; // ← CORRIGÉ ICI
        
        if (empty($currentPassword)) {
            $this->addFlash('error', 'Le mot de passe actuel est requis.');
            return $this->redirectToRoute('client_change_password');
        }
        
        if (!$newPasswordData || empty($newPasswordData)) {
            $this->addFlash('error', 'Le nouveau mot de passe est requis.');
            return $this->redirectToRoute('client_change_password');
        }
        
        // 2. Vérifier l'ancien mot de passe
        if (!$passwordHasher->isPasswordValid($client, $currentPassword)) {
            $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
            return $this->redirectToRoute('client_change_password');
        }
        
        // 3. Vérifier que le nouveau est différent de l'ancien
        if ($passwordHasher->isPasswordValid($client, $newPasswordData)) {
            $this->addFlash('error', 'Le nouveau mot de passe doit être différent de l\'ancien.');
            return $this->redirectToRoute('client_change_password');
        }
        
        // 4. Mettre à jour le mot de passe
        $hashedPassword = $passwordHasher->hashPassword($client, $newPasswordData);
        $client->setPassword($hashedPassword);
        
        $em->flush();

        // 5. Déconnecter
        $this->addFlash('success', 'Votre mot de passe a été modifié avec succès. Veuillez vous reconnecter.');
        return $this->redirectToRoute('app_logout');
    }

    return $this->render('client/profile/change_password.html.twig', [
        'form' => $form->createView(),
    ]);
}
}