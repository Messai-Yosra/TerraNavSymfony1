<?php

namespace App\Form;

use App\Entity\Chambre;
use App\Entity\Hebergement;
use App\Service\hebergements\GeoLocationService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class ChambreType extends AbstractType
{
    private $geoLocationService;

    public function __construct(GeoLocationService $geoLocationService)
    {
        $this->geoLocationService = $geoLocationService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $countryInfo = $this->geoLocationService->getCurrentCountryInfo();
        $defaultCurrency = $countryInfo['currency'];

        $builder
            ->add('numero', TextType::class, [
                'label' => 'Numéro de chambre',
                'constraints' => [
                    new NotBlank(['message' => 'Le numéro de chambre est obligatoire'])
                ]
            ])
            ->add('disponibilite', CheckboxType::class, [
                'label' => 'Disponible immédiatement',
                'required' => false
            ])
            ->add('devise', ChoiceType::class, [
                'label' => 'Devise',
                'mapped' => false,
                'choices' => [
                    'Auto ('. $defaultCurrency .')' => 'auto',
                    'Euro (EUR)' => 'EUR',
                    'Dollar (USD)' => 'USD',
                    'Dinar Tunisien (TND)' => 'TND',
                    'Livre Sterling (GBP)' => 'GBP'
                ],
                'data' => 'auto',
                'attr' => [
                    'class' => 'currency-selector',
                    'onchange' => 'updatePriceLabel(this.value)'
                ]
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix par nuit (' . $defaultCurrency . ')',
                'constraints' => [
                    new NotBlank(['message' => 'Le prix est obligatoire']),
                    new Positive(['message' => 'Le prix doit être positif'])
                ],
                'attr' => [
                    'data-original-currency' => $defaultCurrency
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 5]
            ])
            ->add('capacite', NumberType::class, [
                'label' => 'Capacité (personnes)',
                'constraints' => [
                    new NotBlank(['message' => 'La capacité est obligatoire']),
                    new Positive(['message' => 'La capacité doit être positive'])
                ]
            ])
            ->add('equipements', TextareaType::class, [
                'label' => 'Équipements',
                'required' => false,
                'attr' => ['rows' => 5]
            ])
            ->add('vue', TextType::class, [
                'label' => 'Vue',
                'required' => false
            ])
            ->add('taille', NumberType::class, [
                'label' => 'Taille (m²)',
                'required' => false,
                'constraints' => [
                    new Positive(['message' => 'La taille doit être positive'])
                ]
            ])
            ->add('url_3d', TextType::class, [
                'label' => 'Lien visite virtuelle 3D',
                'required' => false,
                'help' => 'URL complète commençant par http:// ou https://'
            ])
            ->add('hebergement', EntityType::class, [
                'class' => Hebergement::class,
                'label' => 'Hébergement associé',
                'choice_label' => 'nom', // Assurez-vous que votre entité Hebergement a une propriété 'nom'
                'placeholder' => 'Sélectionnez un hébergement'
            ])
            ->add('images', FileType::class, [
                'label' => 'Images de la chambre',
                'multiple' => true,
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'image/jpeg, image/png, image/webp',
                    'onchange' => 'previewImages(this)'
                ],
                'constraints' => [
                    new All([
                        new File([
                            'maxSize' => '5M',
                            'mimeTypes' => [
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ],
                            'mimeTypesMessage' => 'Seuls les formats JPG, PNG et WEBP sont acceptés',
                        ])
                    ])
                ],
                'help' => 'Formats acceptés: JPG, PNG, WEBP (max 5MB par image)'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Chambre::class,
        ]);
    }
}