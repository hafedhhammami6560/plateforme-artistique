<?php

namespace App\Form;

use App\Entity\Discussion;
use App\Entity\Product;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Doctrine\ORM\EntityRepository;

/**
 * Formulaire pour créer ou éditer une discussion
 */
class DiscussionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subject', TextType::class, [
                'label' => 'Sujet de la discussion',
                'attr' => [
                    'placeholder' => 'Ex: Intéressé par votre œuvre...',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Length([
                        'min' => 5,
                        'max' => 500,
                        'minMessage' => 'Le sujet doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le sujet ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
                'required' => false,
            ])
        ;

        // Artist radio selection (required)
        if ($options['show_artist_field']) {
            $builder->add('artist', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'label' => 'Artistes',
                'expanded' => false,
                'multiple' => false,
                'attr' => [
                    'class' => 'form-select',
                    'size' => 8,
                    'style' => 'max-height: 220px; overflow:auto;'
                ],
                'placeholder' => 'Sélectionnez un artiste',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un artiste']),
                ],
                'query_builder' => function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('u')
                        ->where('u.type = :type')
                        ->setParameter('type', 'artist')
                        ->orderBy('u.username', 'ASC');
                },
            ]);

            // Product, dynamically filtered by selected artist
            $builder->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => function (Product $product) {
                    return $product->getTitle() . ' - ' . $product->getArtist()->getUsername();
                },
                'label' => 'Produit concerné',
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Sélectionnez un produit',
                'disabled' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un produit']),
                ],
            ]);

            // Helper to modify product field based on artist
            $formModifier = function (FormInterface $form, $artistId) {
                $form->add('product', EntityType::class, [
                    'class' => Product::class,
                    'choice_label' => function (Product $product) {
                        return $product->getTitle() . ' - ' . $product->getArtist()->getUsername();
                    },
                    'label' => 'Produit concerné',
                    'attr' => ['class' => 'form-select'],
                        'placeholder' => 'Sélectionnez un produit',
                        'disabled' => false,
                    'constraints' => [new NotBlank(['message' => 'Veuillez sélectionner un produit'])],
                    'query_builder' => function (EntityRepository $repository) use ($artistId) {
                        $qb = $repository->createQueryBuilder('p')
                            ->leftJoin('p.artist', 'a')
                            ->addSelect('a')
                            ->where('p.status = :status')
                            ->setParameter('status', 'published');
                        if ($artistId) {
                            $qb->andWhere('a.id = :artistId')
                               ->setParameter('artistId', $artistId);
                        }
                        return $qb->orderBy('p.title', 'ASC');
                    },
                ]);
            };

            // On initial display
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $artistId = $data && $data->getArtist() ? $data->getArtist()->getId() : null;
                $formModifier($event->getForm(), $artistId);
            });

            // On submit (artist selected)
            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $artistId = $data['artist'] ?? null;
                $formModifier($event->getForm(), $artistId);
            });
        } else {
            // Fallback: product list without artist filter (previous behavior)
            $builder->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => function (Product $product) {
                    return $product->getTitle() . ' - ' . $product->getArtist()->getUsername();
                },
                'label' => 'Produit concerné',
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Sélectionnez un produit',
                'constraints' => [new NotBlank(['message' => 'Veuillez sélectionner un produit'])],
                'query_builder' => function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('p')
                        ->leftJoin('p.artist', 'a')
                        ->addSelect('a')
                        ->where('p.status = :status')
                        ->setParameter('status', 'published')
                        ->orderBy('p.title', 'ASC');
                },
            ]);
        }

        // Si l'utilisateur n'est pas un artiste, on ajoute le champ publisher
        if ($options['show_publisher_field']) {
            $builder->add('publisher', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'label' => 'Publisher',
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Sélectionnez un publisher',
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('u')
                        ->where('u.type = :type')
                        ->setParameter('type', 'publisher')
                        ->orderBy('u.username', 'ASC');
                },
            ]);
        }

        // Champ pour le message initial
        if ($options['show_initial_message']) {
            $builder->add('initialMessage', TextareaType::class, [
                'label' => 'Message initial',
                'mapped' => false,
                'attr' => [
                    'placeholder' => 'Écrivez votre premier message...',
                    'class' => 'form-control',
                    'rows' => 5,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le message initial est obligatoire',
                    ]),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'Le message doit contenir au moins {{ limit }} caractères',
                    ]),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Discussion::class,
            'show_artist_field' => true,
            'show_publisher_field' => false,
            'show_initial_message' => true,
        ]);
    }
}
