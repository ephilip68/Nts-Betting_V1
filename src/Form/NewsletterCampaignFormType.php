<?php

namespace App\Form;

use App\Entity\NewsletterCampaign;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewsletterCampaignFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subject', TextType::class, [
                'label' => 'Sujet de l\'e-mail',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu (en Markdown — comme les articles du blog)',
                'attr' => ['rows' => 16],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NewsletterCampaign::class,
        ]);
    }
}
