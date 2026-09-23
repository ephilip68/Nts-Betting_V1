<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom complet',
                'constraints' => [new NotBlank(message: 'Merci de renseigner ton nom.')],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'constraints' => [new NotBlank(message: 'Merci de renseigner ton e-mail.')],
            ])
            ->add('subject', ChoiceType::class, [
                'label' => 'Sujet',
                'placeholder' => 'Choisissez un sujet',
                'choices' => [
                    'Question sur un abonnement' => 'abonnement',
                    'Question sur le NTS Vault' => 'vault',
                    'Problème technique' => 'technique',
                    'Partenariat' => 'partenariat',
                    'Autre' => 'autre',
                ],
                'constraints' => [new NotBlank(message: 'Merci de choisir un sujet.')],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'constraints' => [
                    new NotBlank(message: 'Merci de renseigner un message.'),
                    new Length(min: 10, minMessage: 'Ton message doit contenir au moins {{ limit }} caractères.'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
