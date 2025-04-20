<?php

namespace App\Form;

use App\Entity\Trajet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TrajetSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pointDepart', TextType::class, [
                'label' => 'Départ',
                'required' => true,
                'attr' => ['placeholder' => 'Ville ou gare de départ'],
            ])
            ->add('destination', TextType::class, [
                'label' => 'Destination',
                'required' => true,
                'attr' => ['placeholder' => 'Ville ou gare d\'arrivée'],
            ])
            ->add('dateDepart', DateType::class, [
                'label' => 'Date',
                'required' => true,
                'widget' => 'single_text',
            ])
            ->add('capacite', IntegerType::class, [
                'label' => 'Passagers', // Nom du champ clair
                'required' => true,
                'mapped' => false, // Ne pas mapper à Trajet
                'attr' => [
                    'min' => 1,
                    'max' => 6,
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nombre de passagers est requis']),
                    new Assert\Positive(['message' => 'Le nombre de passagers doit être positif']),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 6,
                        'notInRangeMessage' => 'Le nombre de passagers doit être entre {{ min }} et {{ max }}',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trajet::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'trajet_search',
        ]);
    }
}