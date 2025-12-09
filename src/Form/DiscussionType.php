<?php

namespace App\Form;

use App\Entity\Contrat;
use App\Entity\Discussion;
use App\Entity\Project;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class DiscussionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la discussion',
                'attr' => ['placeholder' => 'Ex: Discussion sur les droits de publication'],
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est obligatoire']),
                    new Length([
                        'min' => 5,
                        'max' => 255,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de discussion',
                'choices' => [
                    'Droits de publication' => Discussion::TYPE_PUBLICATION_RIGHTS,
                    'Commande personnalisée' => Discussion::TYPE_CUSTOM_ORDER,
                ],
            ])
            ->add('sujet', TextType::class, [
                'label' => 'Sujet',
                'required' => false,
                'attr' => ['placeholder' => 'Sujet spécifique de la discussion'],
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Message initial',
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Écrivez votre message...'
                ],
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Length([
                        'min' => 10,
                        'minMessage' => 'Le message doit contenir au moins {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En cours' => Discussion::STATUT_EN_COURS,
                    'Terminée' => Discussion::STATUT_TERMINEE,
                ],
            ])
            ->add('initiateur', EntityType::class, [
                'label' => 'Initiateur',
                'class' => User::class,
                'choice_label' => function ($user) {
                    return $user->getName() . ' (' . $user->getEmail() . ')';
                },
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('u')
                        ->orderBy('u.name', 'ASC');
                },
                'constraints' => [new NotBlank(['message' => 'L\'initiateur est obligatoire'])],
            ])
            ->add('destinataire', EntityType::class, [
                'label' => 'Destinataire',
                'class' => User::class,
                'choice_label' => function ($user) {
                    return $user->getName() . ' (' . $user->getEmail() . ')';
                },
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('u')
                        ->orderBy('u.name', 'ASC');
                },
                'constraints' => [new NotBlank(['message' => 'Le destinataire est obligatoire'])],
            ])
            ->add('contrat', EntityType::class, [
                'label' => 'Contrat lié (optionnel)',
                'class' => Contrat::class,
                'choice_label' => function ($contrat) {
                    return 'Contrat #' . $contrat->getId() . ' - ' . number_format($contrat->getMontant(), 2, ',', ' ') . ' €';
                },
                'placeholder' => 'Sélectionner un contrat',
                'required' => false,
            ])
            ->add('produit', EntityType::class, [
                'label' => 'Projet lié (optionnel)',
                'class' => Project::class,
                'choice_label' => function ($project) {
                    return $project->getTitle();
                },
                'placeholder' => 'Sélectionner un projet',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Discussion::class,
        ]);
    }
}

