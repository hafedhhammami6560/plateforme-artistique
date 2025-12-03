<?php

namespace App\Form;

use App\Entity\Contrat;
use App\Entity\Produit;
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
                'label' => 'Artiste',
                'class' => User::class,
                'choice_label' => function ($user) {
                    return $user->getName() . ' (' . $user->getEmail() . ')';
                },
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('u')
                        ->where('u.roles LIKE :role')
                        ->setParameter('role', '%ROLE_ARTIST%')
                        ->orderBy('u.name', 'ASC');
                },
                'constraints' => [new NotBlank()],
            ])
            ->add('produits', EntityType::class, [
                'label' => 'Produits',
                'class' => Produit::class,
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'nom',
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
