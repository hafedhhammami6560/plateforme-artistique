<?php

namespace App\Form;

use App\Entity\Contrat;
use App\Entity\Project;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContratType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('montant', MoneyType::class, [
                'label' => 'Montant (Ôé¼)',
                'currency' => 'EUR',
                'constraints' => [new NotBlank()],
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de d├®but',
                'widget' => 'single_text',
                'constraints' => [new NotBlank()],
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'constraints' => [new NotBlank()],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En attente' => Contrat::STATUT_EN_ATTENTE,
                    'Accept├®' => Contrat::STATUT_ACCEPTE,
                    'Actif' => Contrat::STATUT_ACTIF,
                    'Termin├®' => Contrat::STATUT_TERMINE,
                    'Annul├®' => Contrat::STATUT_ANNULE,
                ],
            ])
            ->add('producteur', EntityType::class, [
                'label' => 'Producteur',
                'class' => User::class,
                'choice_label' => function ($user) {
                    return $user->getName() . ' (' . $user->getEmail() . ')';
                },
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('u')
                        ->orderBy('u.name', 'ASC');
                },
                'constraints' => [new NotBlank()],
            ])
            ->add('artiste', EntityType::class, [
                'label' => 'Type utilisateur',
                'class' => User::class,
                'choice_label' => function ($user) {
                    $roles = $user->getRoles();
                    $roleLabel = '';
                    if (in_array('ROLE_ADMIN', $roles)) {
                        $roleLabel = 'Admin';
                    } elseif (in_array('ROLE_ARTIST', $roles)) {
                        $roleLabel = 'Artiste';
                    } elseif (in_array('ROLE_PRODUCER', $roles)) {
                        $roleLabel = 'Producteur';
                    } else {
                        $roleLabel = 'Client';
                    }
                    return $user->getName() . ' - ' . $roleLabel . ' (' . $user->getEmail() . ')';
                },
                'group_by' => function ($user) {
                    $roles = $user->getRoles();
                    if (in_array('ROLE_ADMIN', $roles)) {
                        return 'Administrateurs';
                    } elseif (in_array('ROLE_ARTIST', $roles)) {
                        return 'Artistes';
                    } elseif (in_array('ROLE_PRODUCER', $roles)) {
                        return 'Producteurs';
                    }
                    return 'Clients';
                },
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('u')
                        ->orderBy('u.name', 'ASC');
                },
                'constraints' => [new NotBlank()],
            ])
            ->add('produit', EntityType::class, [
                'label' => 'Projet',
                'class' => Project::class,
                'choice_label' => 'title',
                'placeholder' => 'Sélectionner un projet',
                'required' => false,
            ])
            ->add('termes', TextareaType::class, [
                'label' => 'Termes du contrat',
                'attr' => ['rows' => 6],
                'constraints' => [new NotBlank()],
            ])
            ->add('documentFile', FileType::class, [
                'label' => 'Document PDF',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Seuls les fichiers PDF sont accept├®s',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contrat::class,
        ]);
    }
}
