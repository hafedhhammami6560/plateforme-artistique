<?php
/**
 * Formulaire CommunityType
 * 
 * Définit la structure du formulaire pour créer/modifier une communauté
 * Utilise le composant Form de Symfony pour générer automatiquement le HTML
 * 
 * Champs du formulaire:
 * - name        : Nom de la communauté (TextType, requis)
 * - slug        : URL-friendly slug (TextType, requis)
 * - description : Description longue (TextareaType, optionnel)
 * - type        : Type de communauté (ChoiceType avec options: general/artist/category)
 * - isPrivate   : Visibilité (CheckboxType, false par défaut)
 * 
 * Tous les champs utilisent Bootstrap (classe 'form-control') pour le style
 * Les placeholders améliorent l'expérience utilisateur
 */
namespace App\Form;

use App\Entity\Community;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommunityType extends AbstractType
{
    /**
     * Construction du formulaire
     * 
     * Cette méthode définit tous les champs du formulaire avec leurs options
     * Chaque champ est ajouté avec ->add() et peut avoir:
     * - Un type (TextType, ChoiceType, etc.)
     * - Un label (libellé affiché)
     * - Des attributs HTML (classe CSS, placeholder, etc.)
     * - Des contraintes de validation
     * 
     * @param FormBuilderInterface $builder Constructeur de formulaire Symfony
     * @param array $options Options passées au formulaire
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ nom: Texte simple avec placeholder
            ->add('name', TextType::class, [
                'label' => 'Community Name',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter community name']
            ])
            // Champ slug: URL-friendly (ex: mon-super-slug)
            ->add('slug', TextType::class, [
                'label' => 'Slug (URL)',
                'attr' => ['class' => 'form-control', 'placeholder' => 'community-url-slug']
            ])
            // Champ description: Zone de texte multi-lignes (optionnel)
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,  // Pas obligatoire
                'attr' => ['class' => 'form-control', 'rows' => 5, 'placeholder' => 'Describe your community...']
            ])
            // Champ type: Liste déroulante avec 3 choix
            // Format: 'Label affiché' => 'valeur_stockée'
            ->add('type', ChoiceType::class, [
                'label' => 'Community Type',
                'choices' => [
                    'General' => 'general',      // Communauté générale
                    'Artist' => 'artist',        // Communauté d'artiste
                    'Category' => 'category'     // Communauté par catégorie
                ],
                'attr' => ['class' => 'form-control']
            ])
            // Champ isPrivate: Case à cocher pour communauté privée
            ->add('isPrivate', CheckboxType::class, [
                'label' => 'Private Community',
                'required' => false,  // Pas obligatoire (false par défaut)
                'attr' => ['class' => 'form-check-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Community::class,
        ]);
    }
}
