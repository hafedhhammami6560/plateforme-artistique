<?php
/**
 * Formulaire OrganisationType
 * 
 * Définit le formulaire pour créer/modifier une organisation artistique
 * 
 * Champs:
 * - name      : Nom de l'organisation (requis)
 * - address   : Adresse physique (optionnel)
 * - email     : Email de contact (optionnel, avec validation)
 * - communite : Relation ManyToOne avec Communite (menu déroulant)
 */
namespace App\Form;

use App\Entity\Organisation;
use App\Entity\Communite;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrganisationType extends AbstractType
{
    /**
     * OrganisationType
     * 
     * Ce formulaire gère la création / modification d'une `Organisation`.
     * Les champs `locationLat` et `locationLng` sont des champs cachés
     * remplis par le JavaScript de la page (carte + recherche d'adresse).
     * Le champ `locationAddress` contient l'adresse textuelle affichée
     * et peut être modifiée par l'utilisateur.
     */
    /**
     * Construction du formulaire
     * 
     * @param FormBuilderInterface $builder Constructeur Symfony
     * @param array $options Options du formulaire
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Nom de l'organisation (champ requis)
            ->add('name', TextType::class, ['label' => 'Nom'])
            
            // Adresse (optionnel)
            ->add('address', TextType::class, ['label' => 'Adresse', 'required' => false])
            
            // Email avec validation automatique du format
            ->add('email', EmailType::class, ['label' => 'E-mail', 'required' => false])
            
            // Relation ManyToOne: Sélection de la communauté parent
            // EntityType génère un <select> avec toutes les Communite
            ->add('communite', EntityType::class, [
                'class' => Communite::class,         // Entité liée
                'choice_label' => 'name',             // Propriété affichée dans le select
                'placeholder' => 'Choisir une communauté',  // Option vide
                'required' => false,                  // Optionnel
            ])
            // Date/heure de l'événement associé
            ->add('eventDate', DateTimeType::class, [
                'label' => 'Date et heure de l\'événement',
                'widget' => 'single_text',
                'required' => false,
            ])

            // Type d'événement (choix simple)
            ->add('eventType', ChoiceType::class, [
                'label' => 'Type d\'événement',
                'choices' => [
                    'Exposition' => 'exposition',
                    'Atelier' => 'atelier',
                    'Rencontre' => 'rencontre',
                    'Projection' => 'projection',
                    'Autre' => 'other',
                ],
                'required' => false,
                'placeholder' => 'Sélectionner un type',
            ])

            // Adresse et coordonnées (remplies via Google Maps)
            ->add('locationAddress', TextType::class, ['label' => 'Emplacement (adresse)', 'required' => false])
            ->add('locationLat', HiddenType::class, ['required' => false])
            ->add('locationLng', HiddenType::class, ['required' => false])
        ;
    }

    /**
     * Configuration des options du formulaire
     * 
     * @param OptionsResolver $resolver Résolveur d'options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Organisation::class,  // Entité liée au formulaire
        ]);
    }
}