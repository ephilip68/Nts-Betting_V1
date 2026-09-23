<?php

namespace App\Form;

use App\Entity\SiteSetting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SiteSettingFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contactEmail', EmailType::class, [
                'label' => 'E-mail de contact',
                'required' => false,
            ])
            ->add('telegramUrl', UrlType::class, [
                'label' => 'Lien du groupe Telegram',
                'required' => false,
            ])
            ->add('twitterUrl', UrlType::class, [
                'label' => 'Lien Twitter / X',
                'required' => false,
            ])
            ->add('instagramUrl', UrlType::class, [
                'label' => 'Lien Instagram',
                'required' => false,
            ])
            ->add('youtubeUrl', UrlType::class, [
                'label' => 'Lien YouTube',
                'required' => false,
            ])
            ->add('maintenanceMode', CheckboxType::class, [
                'label' => 'Activer le mode maintenance (site public inaccessible, admin toujours accessible)',
                'required' => false,
            ])
            ->add('maintenanceMessage', TextareaType::class, [
                'label' => 'Message affiché pendant la maintenance',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SiteSetting::class,
        ]);
    }
}
