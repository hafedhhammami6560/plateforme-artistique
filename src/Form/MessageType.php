<?php

namespace App\Form;

use App\Entity\Message;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire pour envoyer un message dans une discussion
 */
class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Écrivez votre message...',
                    'class' => 'form-control',
                    'rows' => 4,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le message ne peut pas être vide',
                    ]),
                    new Length([
                        'min' => 1,
                        'max' => 5000,
                        'minMessage' => 'Le message doit contenir au moins {{ limit }} caractère',
                        'maxMessage' => 'Le message ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('isContractProposal', CheckboxType::class, [
                'label' => 'Marquer comme proposition de contrat',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'help' => 'Cochez cette case si ce message contient une proposition de contrat',
            ])
        ;

        // Optionnellement ajouter un champ pour les pièces jointes
        if ($options['show_attachment_field']) {
            $builder->add('attachment', FileType::class, [
                'label' => 'Pièce jointe',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'Formats acceptés: PDF, JPG, PNG (Max 5MB)',
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier PDF, JPG ou PNG valide',
                    ]),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
            'show_attachment_field' => false,
        ]);
    }
}
