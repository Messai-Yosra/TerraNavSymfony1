<?php
// filepath: c:\Users\asus\TerraNavSymfony1\TerraNavSymfony1\src\Form\ReclamationClientType.php
namespace App\Form;

use App\Entity\Reclamation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReclamationClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sujet', ChoiceType::class, [
                'label' => 'Sujet de la réclamation',
                'choices' => [
                    'Problème technique' => 'Problème technique',
                    'Question sur un voyage' => 'Question sur un voyage',
                    'Problème de paiement' => 'Problème de paiement',
                    'Suggestion' => 'Suggestion',
                    'Autre' => 'Autre'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un sujet'])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Votre message',
                'attr' => ['rows' => 5],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez décrire votre réclamation'])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
        ]);
    }
}