<?php

namespace App\Form;

use App\Entity\Discussion;
use App\Entity\Produit;
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
        
        // Récupérer les types de discussion disponibles pour l'utilisateur
        $availableTypes = [];
        if ($user && $permissionService) {
            $availableTypes = $permissionService->getAvailableDiscussionTypes($user);
        } else {
            // Par défaut, tous les types
            $availableTypes = [
                Discussion::TYPE_PUBLICATION_RIGHTS => 'Type A: droits sur produit existant',
                Discussion::TYPE_CUSTOM_ORDER => 'Type B: commande œuvre sur mesure'
            ];
        }
        
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la discussion',
                'attr' => [
                    'placeholder' => 'Ex: Acquisition droits album ou Commande sculpture',
                    'class' => 'form-control'
                ],
                'required' => true
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de discussion',
                'choices' => array_flip($availableTypes),
                'expanded' => true,
                'attr' => ['class' => 'form-check-input'],
                'required' => true,
                'help' => 'Choisissez le type de discussion selon vos besoins'
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

        // Ajouter le champ produit uniquement pour Type A
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $discussion = $event->getData();
            $form = $event->getForm();

            if (!$discussion || $discussion->getType() === Discussion::TYPE_PUBLICATION_RIGHTS) {
                $form->add('produit', EntityType::class, [
                    'class' => Produit::class,
                    'choice_label' => function(Produit $produit) {
                        return $produit->getNom() . ' - ' . $produit->getPrix() . '€';
                    },
                    'label' => 'Produit concerné',
                    'placeholder' => 'Sélectionnez un produit',
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
                if (!$form->has('produit')) {
                    $form->add('produit', EntityType::class, [
                        'class' => Produit::class,
                        'choice_label' => function(Produit $produit) {
                            return $produit->getNom() . ' - ' . $produit->getPrix() . '€';
                        },
                        'label' => 'Produit concerné',
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
