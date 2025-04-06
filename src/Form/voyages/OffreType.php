<?php
// src/Form/OffreType.php

namespace App\Form\voyages;

use App\Entity\Offre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class OffreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => '<i class="fas fa-heading me-2 text-primary"></i>Titre de l\'offre',
                'label_html' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Titre promotionnel'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => '<i class="fas fa-align-left me-2 text-primary"></i>Description',
                'label_html' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Détails de l\'offre...'
                ]
            ])
            ->add('reduction', NumberType::class, [
                'label' => '<i class="fas fa-percentage me-2 text-primary"></i>Réduction (%)',
                'label_html' => true,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 100,
                    'step' => 0.5,
                    'placeholder' => '0-100%'
                ]
            ])
            ->add('dateDebut', DateTimeType::class, [
                'label' => '<i class="fas fa-calendar-alt me-2 text-primary"></i>Date de début',
                'label_html' => true,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateFin', DateTimeType::class, [
                'label' => '<i class="fas fa-calendar-times me-2 text-primary"></i>Date de fin',
                'label_html' => true,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offre::class,
        ]);
    }
}