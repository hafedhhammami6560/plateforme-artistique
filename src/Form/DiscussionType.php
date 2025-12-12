<?php

namespace App\Form;

use App\Entity\Discussion;
use App\Entity\Projet;
use App\Entity\User;
use App\Service\PermissionService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DiscussionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'] ?? null;
        $permissionService = $options['permission_service'] ?? null;
        
        // Afficher tous les types mais déterminer lesquels sont disponibles
        $allTypes = [
            'publication_rights' => 'Type A: droits sur projet existant (Publisher/Sponsor)',
            'custom_order' => 'Type B: commande œuvre sur mesure (Artiste/Musicien/Scénariste)'
        ];
        
        $availableTypes = [];
        if ($user && $permissionService) {
            $availableTypes = $permissionService->getAvailableDiscussionTypes($user);
        } else {
            $availableTypes = $allTypes;
        }
        
        // Déterminer le type par défaut
        $defaultType = !empty($availableTypes) ? array_key_first($availableTypes) : null;
        
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la discussion',
                'attr' => [
                    'placeholder' => 'Ex: Acquisition droits album ou Commande sculpture',
                    'class' => 'form-control'
                ],
                'required' => true
            ])
            ->add('destinataire', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getName() . ' (' . $user->getEmail() . ')';
                },
                'label' => 'Destinataire',
                'placeholder' => 'Sélectionnez un utilisateur',
                'attr' => ['class' => 'form-select'],
                'required' => true
            ])
            ->add('messageInitial', TextareaType::class, [
                'label' => 'Message initial',
                'mapped' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Décrivez votre demande...',
                    'class' => 'form-control'
                ],
                'required' => true
            ]);

        // Ajouter le champ projet uniquement pour Type A
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $discussion = $event->getData();
            $form = $event->getForm();

            if (!$discussion || $discussion->getType() === Discussion::TYPE_PUBLICATION_RIGHTS) {
                $form->add('projet', EntityType::class, [
                    'class' => Projet::class,
                    'choice_label' => function(projet $projet) {
                        return $projet->getNom() . ' - ' . $projet->getPrix() . '€';
                    },
                    'label' => 'projet concerné',
                    'placeholder' => 'Sélectionnez un projet',
                    'attr' => ['class' => 'form-select'],
                    'required' => false,
                    'help' => 'Obligatoire pour Type A (Publication Rights)'
                ]);
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if (isset($data['type']) && $data['type'] === Discussion::TYPE_PUBLICATION_RIGHTS) {
                if (!$form->has('projet')) {
                    $form->add('projet', EntityType::class, [
                        'class' => Projet::class,
                        'choice_label' => function(projet $projet) {
                            return $projet->getNom() . ' - ' . $projet->getPrix() . '€';
                        },
                        'label' => 'projet concerné',
                        'attr' => ['class' => 'form-select'],
                        'required' => false
                    ]);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Discussion::class,
            'user' => null,
            'permission_service' => null,
        ]);
    }
}

