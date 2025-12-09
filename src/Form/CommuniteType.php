<?php
namespace App\Form;

use App\Entity\Communite;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

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
            // Nom de la communauté : sélection depuis la liste des utilisateurs
            ->add('name', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'label' => 'Nom (utilisateur)',
                'required' => true,
                'placeholder' => 'Sélectionner un utilisateur',
            ])
            
            // Description (zone de texte multi-lignes optionnelle)
            ->add('description', TextareaType::class, [
                'label' => 'Description', 
                'required' => false  // Pas obligatoire
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
            'data_class' => Communite::class,  // Entité liée
        ]);
    }
}