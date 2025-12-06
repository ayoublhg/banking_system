<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Form\ResetPasswordRequestFormType;
use App\Form\ResetPasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    #[Route('', name: 'app_forgot_password_request', methods: ['GET', 'POST'])]
    public function request(
        Request $request,
        MailerInterface $mailer,
        TokenGeneratorInterface $tokenGenerator,
        EntityManagerInterface $em,
        TranslatorInterface $translator
    ): Response {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user) {
                $token = $tokenGenerator->generateToken();
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
                $em->flush();

                $resetUrl = $this->generateUrl(
                    'app_reset_password',
                    ['token' => $token],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $emailMessage = (new Email())
                    ->from('noreply@bankingsystem.com')
                    ->to($user->getEmail())
                    ->subject($translator->trans('Réinitialisation de votre mot de passe'))
                    ->htmlTemplate('security/reset_password_email.html.twig')
                    ->context([
                        'resetUrl' => $resetUrl,
                        'user'     => $user,
                        'expiration' => 60 // minutes
                    ]);

                $mailer->send($emailMessage);
            }

            $this->addFlash('success', $translator->trans('Si cette adresse existe, un email de réinitialisation a été envoyé.'));
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password_request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    #[Route('/reset/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function reset(
        string $token,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        $user = $em->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);

            $em->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès !');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }
}