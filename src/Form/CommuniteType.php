<?php
/**
 * Formulaire CommuniteType
 * 
 * Formulaire simple pour créer/modifier une communauté
 * Contient uniquement les champs de base (nom et description)
 * 
 * Champs:
 * - name        : Nom de la communauté (requis)
 * - description : Description longue (optionnel)
 */
namespace App\Form;

use App\Entity\Communite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CommuniteType extends AbstractType
{
    /**
     * Construction du formulaire
     * 
     * Formulaire minimaliste avec 2 champs seulement
     * 
     * @param FormBuilderInterface $builder Constructeur Symfony
     * @param array $options Options du formulaire
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Nom de la communauté (champ texte requis)
            ->add('name', TextType::class, ['label' => 'Nom'])
            
            // Description (zone de texte multi-lignes optionnelle)
            ->add('description', TextareaType::class, [
                'label' => 'Description', 
                'required' => false  // Pas obligatoire
            ])
            // Type d'utilisateur principal pour cette communauté
            ->add('userType', ChoiceType::class, [
                'label' => 'Type de membre principal',
                'choices' => [
                    'Artiste' => 'artist',
                    'Organisateur' => 'organizer',
                    'Galerie' => 'gallery',
                    'Collectionneur' => 'collector',
                    'Autre' => 'other',
                ],
                'required' => false,
                'placeholder' => 'Sélectionner un type (optionnel)'
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
            'data_class' => Communite::class,  // Entité liée
        ]);
    }
}