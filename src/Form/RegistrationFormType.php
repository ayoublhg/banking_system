<?php

namespace App\Form;

use App\Entity\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'placeholder' => 'votre@email.com',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un email']),
                ]
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'property_path' => 'lastName', // <-- IMPORTANT: mapper à lastName
                'attr' => [
                    'placeholder' => 'Votre nom',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre nom']),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'max' => 50,
                    ]),
                ]
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'property_path' => 'firstName', // <-- IMPORTANT: mapper à firstName
                'attr' => [
                    'placeholder' => 'Votre prénom',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer votre prénom']),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères',
                        'max' => 50,
                    ]),
                ]
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => [
                        'placeholder' => 'Minimum 6 caractères',
                        'class' => 'form-control'
                    ],
                    'constraints' => [
                        new NotBlank(['message' => 'Veuillez entrer un mot de passe']),
                        new Length([
                            'min' => 6,
                            'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                            'max' => 4096,
                        ]),
                    ]
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => [
                        'placeholder' => 'Répétez le mot de passe',
                        'class' => 'form-control'
                    ],
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas',
                'mapped' => false,
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'J\'accepte les conditions',
                'mapped' => false,
                'constraints' => [
                    new IsTrue(['message' => 'Vous devez accepter les conditions']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
        ]);
    }
}