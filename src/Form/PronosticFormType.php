<?php

namespace App\Form;

use App\Entity\Pronostic;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PronosticFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sport', ChoiceType::class, [
                'choices' => [
                    'Football' => 'football',
                    'Basketball' => 'basketball',
                    'Tennis' => 'tennis',
                ],
            ])
            ->add('competition', TextType::class)
            ->add('teamHome', TextType::class, ['label' => 'Équipe domicile'])
            ->add('teamAway', TextType::class, ['label' => 'Équipe extérieur'])
            ->add('teamHomeLogo', TextType::class, ['label' => 'Logo équipe domicile (URL, optionnel)', 'required' => false])
            ->add('teamAwayLogo', TextType::class, ['label' => 'Logo équipe extérieur (URL, optionnel)', 'required' => false])
            ->add('matchDate', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date du match',
            ])
            ->add('betType', TextType::class, [
                'label' => 'Type de pari (ex : 1X2, Double chance, Buts...)',
            ])
            ->add('betValue', TextType::class, [
                'label' => 'Valeur du pronostic (ex : Real Madrid, Plus de 2.5 buts...)',
            ])
            ->add('odds', NumberType::class, [
                'label' => 'Cote',
                'scale' => 2,
            ])
            ->add('confidence', ChoiceType::class, [
                'label' => 'Niveau de confiance',
                'choices' => [
                    '1 - Faible' => 1,
                    '2' => 2,
                    '3 - Moyen' => 3,
                    '4' => 4,
                    '5 - Très élevé' => 5,
                ],
            ])
            ->add('analysis', TextareaType::class, [
                'label' => 'Analyse détaillée',
                'required' => false,
            ])
            ->add('isVip', CheckboxType::class, [
                'label' => 'Réservé aux membres VIP',
                'required' => false,
            ])
            ->add('isFeatured', CheckboxType::class, [
                'label' => 'Mettre en avant ("pronostic du jour")',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Résultat',
                'choices' => [
                    'En attente' => Pronostic::STATUS_PENDING,
                    'Gagné' => Pronostic::STATUS_WON,
                    'Perdu' => Pronostic::STATUS_LOST,
                    'Annulé' => Pronostic::STATUS_VOID,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pronostic::class,
        ]);
    }
}
