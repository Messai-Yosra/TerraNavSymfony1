<?php

namespace App\Form;

use App\Entity\Hebergement;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class Hebergement1Type extends AbstractType
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    // Vérifier que ce formulaire est correctement configuré

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'hébergement',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir un nom']),
                    new Length(['min' => 3, 'max' => 100])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => ['rows' => 5]
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'required' => true
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'required' => true
            ])
            ->add('pays', TextType::class, [
                'label' => 'Pays',
                'required' => true
            ])
            ->add('services', TextareaType::class, [
                'label' => 'Services offerts',
                'required' => true,
                'attr' => ['placeholder' => 'Listez les services disponibles']
            ])
            ->add('politique_annulation', TextareaType::class, [
                'label' => 'Politique d\'annulation',
                'required' => true,
                'attr' => ['placeholder' => 'Décrivez votre politique d\'annulation']
            ])
            ->add('contact', TextType::class, [
                'label' => 'Contact',
                'required' => true,
                'attr' => ['placeholder' => 'Numéro de téléphone ou email']
            ])
            ->add('type_hebergement', ChoiceType::class, [
                'label' => 'Type d\'hébergement',
                'required' => true,
                'choices' => [
                    'Hôtel' => 'hotel',
                    'Appartement' => 'appartement',
                    'Maison' => 'maison',
                    'Villa' => 'villa',
                    'Chalet' => 'chalet'
                ]
            ])
            ->add('nb_chambres', NumberType::class, [
                'label' => 'Nombre de chambres',
                'required' => true,
                'attr' => ['min' => 1]
            ])
            // Le champ note_moyenne est géré automatiquement dans le contrôleur
            ->add('note_moyenne', HiddenType::class, [
                'data' => 0.0,
            ]);

        // Ajouter le champ id_user pour tous les utilisateurs, pas seulement les admins
        $builder->add('id_user', EntityType::class, [
            'class' => Utilisateur::class,
            'choice_label' => function (Utilisateur $user) {
                return $user->getEmail() . ' (ID: ' . $user->getId() . ')';
            },
            'placeholder' => 'Sélectionnez un propriétaire',
            'required' => true,
            'label' => 'Propriétaire',
            'attr' => ['class' => 'form-select'],
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'Ajouter l\'hébergement',
            'attr' => ['class' => 'btn btn-primary']
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Hebergement::class,
        ]);
    }
}
