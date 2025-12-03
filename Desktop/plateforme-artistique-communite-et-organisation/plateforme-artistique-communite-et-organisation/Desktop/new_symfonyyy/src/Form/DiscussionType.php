<?php

namespace App\Form;

use App\Entity\Contrat;
use App\Entity\Discussion;
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
                'label' => 'Titre',
                'constraints' => [new NotBlank()],
            ])
            ->add('sujet', TextType::class, [
                'label' => 'Sujet',
                'constraints' => [new NotBlank()],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Ouverte' => Discussion::STATUT_OUVERTE,
                    'Ferm├®e' => Discussion::STATUT_FERMEE,
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
                'constraints' => [new NotBlank()],
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
                'constraints' => [new NotBlank()],
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Message',
                'attr' => ['rows' => 8],
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'Le message doit contenir au moins 10 caract├¿res',
                    ]),
                ],
            ])
            ->add('contrat', EntityType::class, [
                'label' => 'Contrat li├®',
                'class' => Contrat::class,
                'choice_label' => function ($contrat) {
                    return 'Contrat #' . $contrat->getId() . ' - ' . number_format($contrat->getMontant(), 2, ',', ' ') . ' Ôé¼';
                },
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
