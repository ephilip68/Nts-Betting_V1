<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PreferencesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('theme', ChoiceType::class, [
                'label' => 'Thème du site',
                'expanded' => true,
                'choices' => [
                    'Sombre' => 'dark',
                    'Clair' => 'light',
                    'Système' => 'system',
                ],
                'help' => 'Le site n\'a pour l\'instant qu\'un thème sombre — ton choix est enregistré pour plus tard.',
            ])
            ->add('oddsFormat', ChoiceType::class, [
                'label' => 'Format des cotes',
                'choices' => [
                    'Décimal (1.85)' => 'decimal',
                    'Fractionnaire (17/20)' => 'fractional',
                ],
            ])
            ->add('timezone', ChoiceType::class, [
                'label' => 'Fuseau horaire',
                'choices' => [
                    '(UTC+1) Paris' => 'Europe/Paris',
                    '(UTC+0) Londres' => 'Europe/London',
                    '(UTC-4) Montréal' => 'America/Montreal',
                    '(UTC+1) Bruxelles' => 'Europe/Brussels',
                    '(UTC+2) Genève' => 'Europe/Zurich',
                ],
            ])
            ->add('favoriteSport', ChoiceType::class, [
                'label' => 'Sport favori',
                'required' => false,
                'placeholder' => '—',
                'choices' => [
                    'Football' => 'football',
                    'Basketball' => 'basketball',
                    'Tennis' => 'tennis',
                    'Rugby' => 'rugby',
                    'Hockey' => 'hockey',
                    'MMA / Boxe' => 'mma',
                ],
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
