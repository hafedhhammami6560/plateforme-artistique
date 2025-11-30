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