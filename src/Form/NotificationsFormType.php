<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotificationsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('notifyNewPronostics', CheckboxType::class, [
                'label' => 'Nouveaux pronostics',
                'required' => false,
            ])
            ->add('notifyResultsAnalysis', CheckboxType::class, [
                'label' => 'Résultats et analyses',
                'required' => false,
            ])
            ->add('notifyOffers', CheckboxType::class, [
                'label' => 'Offres et promotions',
                'required' => false,
            ])
            ->add('notifySiteNews', CheckboxType::class, [
                'label' => 'Actualités du site',
                'required' => false,
            ])
            ->add('notifySubscriptionReminders', CheckboxType::class, [
                'label' => "Rappels d'abonnement",
                'required' => false,
            ])
            ->add('notifyTelegramMessages', CheckboxType::class, [
                'label' => 'Messages du groupe Telegram',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
