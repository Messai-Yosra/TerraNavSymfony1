<?php

namespace App\Form;

use App\Entity\Story;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class StoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('media', FileType::class, [
                'label' => 'Media (Image/Video)',
                'required' => true,
                'attr' => [
                    'accept' => 'image/*,video/*',
                    'data-max-file-size' => '10485760' // 10MB en bytes
                ]
            ])
            ->add('text', TextareaType::class, [
                'label' => 'Texte de la story',
                'required' => false,
                'attr' => [
                    'maxlength' => 500,
                    'placeholder' => 'Ajouter un texte à votre story...'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Story::class,
        ]);
    }
}