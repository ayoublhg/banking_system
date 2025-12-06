<?php

namespace App\Form;

use App\Entity\Service;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du service',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Compte Premium'
                ],
                'required' => true
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Décrivez les avantages de ce service...'
                ],
                'required' => true
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix mensuel (€)',
                'currency' => 'EUR',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '9.99'
                ],
                'required' => true
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Image du service',
                'required' => false,
                'allow_delete' => true,
                'delete_label' => 'Supprimer l\'image actuelle',
                'download_label' => 'Télécharger l\'image',
                'download_uri' => true,
                'image_uri' => true,
                'asset_helper' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Image([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, GIF, WebP)',
                    ])
                ]
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Service actif',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'label_attr' => [
                    'class' => 'form-check-label'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
        ]);
    }
}