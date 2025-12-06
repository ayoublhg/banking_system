<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Entity\Client;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        if ($user = $this->getUser()) {
            if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                return $this->redirectToRoute('admin_dashboard');
            }

            return $this->redirectToRoute('client_dashboard');
        }
        
        return $this->render('security/home.html.twig');
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new Client();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            ));

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // Récupère l'erreur de login s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Dernier username entré par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new \LogicException('This method will be intercepted by the logout key in security.yaml');
    }
}