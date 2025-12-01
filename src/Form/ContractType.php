<?php

namespace App\Form;

use App\Entity\Contract;
use App\Entity\Discussion;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Formulaire pour créer ou éditer un contrat
 */
class ContractType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('terms', TextareaType::class, [
                'label' => 'Termes du contrat',
                'attr' => [
                    'placeholder' => 'Décrivez les termes et conditions du contrat...',
                    'class' => 'form-control',
                    'rows' => 8,
                ],
                'help' => 'Minimum 50 caractères requis',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Les termes du contrat sont obligatoires',
                    ]),
                ],
            ])
            ->add('commissionRate', NumberType::class, [
                'label' => 'Taux de commission',
                'html5' => false, // renders as text input but maps to float
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 15.5',
                ],
                'help' => 'Pourcentage de commission (0 à 100)',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le taux de commission est obligatoire',
                    ]),
                    new Range([
                        'min' => 0,
                        'max' => 100,
                        'notInRangeMessage' => 'Le taux doit être entre {{ min }} et {{ max }}',
                    ]),
                ],
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'help' => 'Date de démarrage du contrat',
                'constraints' => [
                    new NotBlank([
                        'message' => 'La date de début est obligatoire',
                    ]),
                ],
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'help' => 'Date de fin du contrat',
                'constraints' => [
                    new NotBlank([
                        'message' => 'La date de fin est obligatoire',
                    ]),
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes additionnelles',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Notes internes, rappels...',
                    'class' => 'form-control',
                    'rows' => 4,
                ],
                'help' => 'Informations complémentaires (optionnel)',
            ])
        ;

        // Si on crée un nouveau contrat, on ajoute le champ discussion
        if ($options['show_discussion_field']) {
            $builder->add('discussion', EntityType::class, [
                'class' => Discussion::class,
                'choice_label' => function (Discussion $discussion) {
                    return sprintf(
                        'Discussion #%d - %s avec %s',
                        $discussion->getId(),
                        $discussion->getProduct()->getTitle(),
                        $discussion->getArtist()->getUsername()
                    );
                },
                'label' => 'Discussion liée',
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Sélectionnez une discussion',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner une discussion',
                    ]),
                ],
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('d')
                        ->leftJoin('d.contract', 'c')
                        ->where('c.id IS NULL')
                        ->andWhere('d.status = :status')
                        ->setParameter('status', Discussion::STATUS_ACTIVE)
                        ->orderBy('d.createdAt', 'DESC');
                },
            ]);
        }

        // Pour l'édition, on peut ajouter le champ statut
        if ($options['show_status_field']) {
            $builder->add('status', ChoiceType::class, [
                'label' => 'Statut du contrat',
                'choices' => Contract::STATUSES,
                'attr' => ['class' => 'form-select'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contract::class,
            'show_discussion_field' => true,
            'show_status_field' => false,
        ]);
    }
}
