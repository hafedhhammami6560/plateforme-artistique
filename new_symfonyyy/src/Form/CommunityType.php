<?php

namespace App\Form;

use App\Entity\Community;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommunityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Community Name',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter community name']
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug (URL)',
                'attr' => ['class' => 'form-control', 'placeholder' => 'community-url-slug']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 5, 'placeholder' => 'Describe your community...']
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Community Type',
                'choices' => [
                    'General' => 'general',
                    'Artist' => 'artist',
                    'Category' => 'category'
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('isPrivate', CheckboxType::class, [
                'label' => 'Private Community',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Community::class,
        ]);
    }
}
