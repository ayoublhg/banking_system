<?php

namespace App\Controller\Client;

use App\Entity\Client;
use App\Form\ChangePasswordType;  // ← CHANGÉ ICI
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/profile')]
#[IsGranted('ROLE_USER')]
class ChangePasswordController extends AbstractController
{
    #[Route('/change-password', name: 'client_change_password')]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var Client $client */
        $client = $this->getUser();
        
        if (!$client instanceof Client) {
            throw $this->createAccessDeniedException('Accès réservé aux clients');
        }
        
        $form = $this->createForm(ChangePasswordType::class);  // ← CHANGÉ ICI
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('plainPassword')->getData();
            
            if (!$passwordHasher->isPasswordValid($client, $currentPassword)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                return $this->redirectToRoute('client_change_password');
            }
            
            $hashedPassword = $passwordHasher->hashPassword($client, $newPassword);
            $client->setPassword($hashedPassword);
            
            $entityManager->persist($client);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre mot de passe a été changé avec succès !');
            return $this->redirectToRoute('client_profile');
        }

        return $this->render('client/profile/change_password.html.twig', [
            'form' => $form->createView(),
            'client' => $client,
        ]);
    }
}