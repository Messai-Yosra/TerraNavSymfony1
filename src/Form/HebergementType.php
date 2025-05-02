<?php

namespace App\Form;

use App\Entity\Hebergement;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HebergementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'hébergement',
                'attr' => ['class' => 'form-control']
            ])
            ->add('type_hebergement', ChoiceType::class, [
                'choices' => [
                    'Hôtel' => 'Hôtel',
                    'Appartement' => 'Appartement',
                    'Villa' => 'Villa',
                    'Maison d\'hôtes' => 'Maison d\'hôtes',
                    'Resort' => 'Resort'
                ],
                'label' => 'Type d\'hébergement'
            ])
            ->add('description', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 5],
                'label' => 'Description',
                'required' => false
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse'
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville'
            ])
            ->add('pays', TextType::class, [
                'label' => 'Pays'
            ])
            ->add('note_moyenne')
            ->add('services', TextareaType::class, [
                'label' => 'Services offerts',
                'required' => false
            ])
            ->add('politique_annulation', TextareaType::class, [
                'label' => 'Politique d\'annulation',
                'required' => false
            ])
            ->add('contact', TextType::class, [
                'label' => 'Contact',
                'required' => true,
                'attr' => [
                    'class' => 'form-control phone-input',
                    'placeholder' => 'Numéro de téléphone'
                ]
            ])
            ->add('nb_chambres', IntegerType::class, [
                'label' => 'Nombre de chambres'
            ])
            ->add('id_user', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => 'id',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-primary']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Hebergement::class,
        ]);
    }
}