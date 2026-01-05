<?php
// App\Form\OfferType.php

namespace App\Form;

use App\Entity\Offer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => "Job Title",
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Senior PHP Developer'
                ],
                'required' => true
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5
                ],
                'required' => true
            ])
            ->add('skills', TextareaType::class, [
                'label' => "Required Skills",
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'PHP, Symfony, MySQL, JavaScript...'
                ],
                'required' => true
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Remote, Paris, New York...'
                ]
            ])
            ->add('salary', TextType::class, [
                'label' => 'Salary',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '$60,000 - $80,000'
                ]
            ])
            ->add('contractType', ChoiceType::class, [
                'label' => 'Contract Type',
                'required' => false,
                'choices' => [
                    'Full-time' => 'Full-time',
                    'Part-time' => 'Part-time',
                    'Contract' => 'Contract',
                    'Remote' => 'Remote',
                    'Internship' => 'Internship'
                ],
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Select contract type'
            ]);

        // Removed createdAt and recruiter fields - they'll be set automatically
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offer::class,
        ]);
    }
}
