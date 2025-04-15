<?php

namespace App\Form;

use App\Entity\Chambre;
use App\Entity\Hebergement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChambreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numero')
            ->add('disponibilite')
            ->add('prix')
            ->add('description')
            ->add('capacite')
            ->add('equipements')
            ->add('vue')
            ->add('taille')
            ->add('url3d')
            ->add('hebergement', EntityType::class, [
                'class' => Hebergement::class,
                'choice_label' => 'id',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Chambre::class,
        ]);
    }
}