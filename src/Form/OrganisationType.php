<?php
/**
 * Formulaire OrganisationType
 * 
 * Définit le formulaire pour créer/modifier une organisation artistique
 */

namespace App\Form;

use App\Entity\Organisation;
use App\Entity\Communite;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrganisationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dateEvenement', DateType::class, [
                'label' => "Date de l'événement",
                'widget' => 'single_text',
                'required' => false,
                'html5' => true,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'organisation',
                'choices' => [
                    'Événement musical' => 'evenement_musical',
                    'Musée' => 'musee',
                    'Théâtre' => 'theatre',
                    'Galerie' => 'galerie',
                    'Autre' => 'autre',
                ],
                'required' => true,
                'placeholder' => 'Sélectionner le type',
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'required' => true,
            ])
            ->add('addressGoogle', TextType::class, [
                'label' => 'Adresse Google Maps',
                'required' => false,
            ])
            ->add('latitude', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => ['type' => 'hidden'],
            ])
            ->add('longitude', TextType::class, [
                'label' => false,
                'required' => false,
                'attr' => ['type' => 'hidden'],
            ])
            ->add('communite', EntityType::class, [
                'class' => Communite::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir une communauté',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Organisation::class,
        ]);
    }
}
