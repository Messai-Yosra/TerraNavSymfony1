<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SignUpType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('prenom')
            ->add('email')
            ->add('username')
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'attr' => [
                    'placeholder' => 'Créez un mot de passe sécurisé'
                ]
            ])
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'Client' => 'CLIENT',
                    'Agence' => 'AGENCE',
                ],
                'expanded' => false,
                'multiple' => false,
                'label' => 'Rôle'
            ])
            ->add('nomagence')
            ->add('typeAgence', ChoiceType::class, [
                'choices' => [
                    'Hébergement' => 'hebergement',
                    'Voyage' => 'voyage',
                    'Transport' => 'transport'
                ],
                'required' => false,
                'placeholder' => 'Sélectionnez un type d\'agence',
                'mapped' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
