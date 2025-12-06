<?php
// src/Form/AccountRequestType.php

namespace App\Form;

use App\Entity\Account;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type de compte',
                'choices' => [
                    'Compte Courant' => Account::TYPE_CHECKING,
                    'Compte Épargne' => Account::TYPE_SAVINGS,
                    'Compte Professionnel' => Account::TYPE_BUSINESS,
                ],
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('initialDeposit', MoneyType::class, [
                'label' => 'Dépôt initial (minimum 50€)',
                'currency' => 'EUR',
                'attr' => [
                    'class' => 'form-control',
                    'min' => '50',
                    'step' => '0.01'
                ],
                'required' => true,
                'scale' => 2,
                'html5' => true,
            ])
            ->add('purpose', TextType::class, [
                'label' => 'Usage prévu du compte',
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}