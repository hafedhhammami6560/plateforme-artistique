<?php

namespace App\Form;

use App\Entity\Contrat;
use App\Entity\projet;
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
        // Le type est toujours défini par la discussion, afficher en lecture seule
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type de contrat (défini par la discussion)',
                'choices' => [
                    'Type A: Publication Rights (Droits sur projet existant)' => Contrat::TYPE_PUBLICATION_RIGHTS,
                    'Type B: Custom Order (Commande personnalisée)' => Contrat::TYPE_CUSTOM_ORDER,
                ],
                'expanded' => true,
                'attr' => ['class' => 'form-check-input'],
                'required' => true,
                'disabled' => true, // Toujours désactivé, défini par la discussion
                'help' => 'Le type de contrat est automatiquement défini selon la discussion associée.'
            ])
            ->add('artiste', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getName() . ' (' . $user->getEmail() . ')';
                },
                'label' => 'Artiste',
                'attr' => ['class' => 'form-select'],
                'required' => true,
                'disabled' => $options['from_discussion'] || $options['is_edit'],
                'placeholder' => $options['from_discussion'] ? null : 'Sélectionnez l\'artiste',
            ])
            ->add('producteur', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getName() . ' (' . $user->getEmail() . ')';
                },
                'label' => 'Client (Publisher/Sponsor)',
                'placeholder' => $options['from_discussion'] ? null : 'Sélectionnez le client',
                'attr' => ['class' => 'form-select'],
                'required' => true,
                'disabled' => $options['from_discussion'] || $options['is_edit'],
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

        // Ajouter le champ projet uniquement pour Type Publication Rights
        if ($options['show_projet']) {
            $projetOptions = [
                'class' => projet::class,
                'choice_label' => function(projet $projet) {
                    return $projet->getNom() . ' - ' . $projet->getPrix() . '€';
                },
                'label' => 'projet concerné',
                'placeholder' => 'Sélectionnez le projet',
                'attr' => ['class' => 'form-select'],
                'required' => false,
                'disabled' => $options['is_edit'],
                'help' => 'Obligatoire pour les contrats de type Publication Rights'
            ];
            
            // Filtrer les projets par artiste si current_user est fourni
            if ($options['current_user']) {
                $projetOptions['query_builder'] = function($repository) use ($options) {
                    return $repository->createQueryBuilder('p')
                        ->where('p.artist = :artist')
                        ->setParameter('artist', $options['current_user'])
                        ->orderBy('p.nom', 'ASC');
                };
            }
            
            $builder->add('projet', EntityType::class, $projetOptions);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contrat::class,
            'is_edit' => false,
            'show_projet' => true,
            'current_user' => null,
            'from_discussion' => false,
        ]);
    }
}

