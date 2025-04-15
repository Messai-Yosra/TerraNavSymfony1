<?php

// src/Form/VoyageType.php

namespace App\Form\voyages;

use App\Entity\Voyage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class VoyageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('destination', TextType::class, [
                'label' => '<i class="fas fa-map-marked-alt me-2 text-primary"></i>Destination',
                'label_html' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Destination'
                ]
            ])
            ->add('pointDepart', TextType::class, [
                'label' => '<i class="fas fa-map-marker-alt me-2 text-primary"></i>Point de départ',
                'label_html' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Point de départ'
                ]
            ])
            ->add('titre', TextType::class, [
                'label' => '<i class="fas fa-heading me-2 text-primary"></i>Titre du voyage',
                'label_html' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Titre du voyage'
                ]
            ])
            ->add('dateDepart', DateTimeType::class, [
                'label' => '<i class="fas fa-plane-departure me-2 text-primary"></i>Date de départ',
                'label_html' => true,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateRetour', DateTimeType::class, [
                'label' => '<i class="fas fa-plane-arrival me-2 text-primary"></i>Date de retour',
                'label_html' => true,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('type', ChoiceType::class, [
                'label' => '<i class="fas fa-tags me-2 text-primary"></i>Type de voyage',
                'label_html' => true,
                'choices' => [
                    'Avion' => 'Avion',
                    'Train' => 'Train',
                    'Bateau' => 'Bateau',
                ],
                'placeholder' => 'Sélectionner un type',
                'attr' => ['class' => 'form-select']
            ])
            ->add('nbPlacesD', NumberType::class, [
                'label' => '<i class="fas fa-users me-2 text-primary"></i>Nombre de places',
                'label_html' => true,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1
                ]
            ])
            ->add('prix', NumberType::class, [
                'label' => '<i class="fas fa-money-bill-wave me-2 text-primary"></i>Prix',
                'label_html' => true,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => 0
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => '<i class="fas fa-align-left me-2 text-primary"></i>Description détaillée',
                'label_html' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Voyage::class,
        ]);
    }
}