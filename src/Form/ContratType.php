<?php

namespace App\Form;

use App\Entity\Contrat;
use App\Entity\Produit;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContratType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type de contrat',
                'choices' => [
                    'Publication Rights (Droits sur produit existant)' => Contrat::TYPE_PUBLICATION_RIGHTS,
                    'Custom Order (Commande personnalisée)' => Contrat::TYPE_CUSTOM_ORDER,
                ],
                'expanded' => true,
                'attr' => ['class' => 'form-check-input'],
                'required' => true,
                'disabled' => $options['is_edit'], // Ne peut pas changer le type en édition
            ])
            ->add('producteur', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getName() . ' (' . $user->getEmail() . ')';
                },
                'label' => 'Client (Publisher/Sponsor)',
                'placeholder' => 'Sélectionnez le client',
                'attr' => ['class' => 'form-select'],
                'required' => true,
                'disabled' => $options['is_edit'],
            ])
            ->add('prix', MoneyType::class, [
                'label' => 'Prix du contrat',
                'currency' => 'EUR',
                'attr' => [
                    'placeholder' => '0.00',
                    'class' => 'form-control'
                ],
                'required' => true,
            ])
            ->add('conditionsTexte', TextareaType::class, [
                'label' => 'Conditions et termes du contrat',
                'attr' => [
                    'rows' => 10,
                    'placeholder' => 'Décrivez les conditions du contrat en détail...',
                    'class' => 'form-control'
                ],
                'required' => true,
                'help' => 'Soyez précis sur les droits, obligations, délais, etc.'
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ]);

        // Ajouter le champ produit uniquement pour Type Publication Rights
        if ($options['show_produit']) {
            $builder->add('produit', EntityType::class, [
                'class' => Produit::class,
                'choice_label' => function(Produit $produit) {
                    return $produit->getNom() . ' - ' . $produit->getPrix() . '€';
                },
                'label' => 'Produit concerné',
                'placeholder' => 'Sélectionnez le produit',
                'attr' => ['class' => 'form-select'],
                'required' => false,
                'disabled' => $options['is_edit'],
                'help' => 'Obligatoire pour les contrats de type Publication Rights'
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contrat::class,
            'is_edit' => false,
            'show_produit' => true,
        ]);
    }
}
