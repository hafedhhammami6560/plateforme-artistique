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

use App\Entity\User;

use App\Entity\Organisation;
use App\Entity\Communite;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrganisationType extends AbstractType
{
    /**
     * Construction du formulaire
     * 
     * @param FormBuilderInterface $builder Constructeur Symfony
     * @param array $options Options du formulaire
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Type d'organisation
            ->add('type', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
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
            // Sélection du nom d'après la liste des organisations créées par le user
            ->add('name', EntityType::class, [
                'class' => Organisation::class,
                'choices' => $options['user_organisations'] ?? [],
                'choice_label' => 'name',
                'label' => 'Nom',
                'required' => true,
                'placeholder' => 'Sélectionner un nom',
            ])
            
            // Adresse (optionnel)
            
            // Email avec validation automatique du format
            
                // Adresse Google Maps (optionnel)
                ->add('addressGoogle', TextType::class, [
                    'label' => 'Adresse Google Maps',
                    'required' => false,
                ])
                // Latitude (masqué)
                ->add('latitude', TextType::class, [
                    'label' => false,
                    'required' => false,
                    'attr' => ['type' => 'hidden'],
                ])
                // Longitude (masqué)
                ->add('longitude', TextType::class, [
                    'label' => false,
                    'required' => false,
                    'attr' => ['type' => 'hidden'],
                ])
            
            // Relation ManyToOne: Sélection de la communauté parent
            // EntityType génère un <select> avec toutes les Communite
            ->add('communite', EntityType::class, [
                'class' => Communite::class,         // Entité liée
                'choice_label' => 'name',             // Propriété affichée dans le select
                'placeholder' => 'Choisir une communauté',  // Option vide
                'required' => false,                  // Optionnel
            ])
            // Sélection du créateur (User réel)
            ->add('createdBy', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'label' => 'Créateur',
                'required' => true
            ])
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
            'user_organisations' => [],
        ]);
    }
}