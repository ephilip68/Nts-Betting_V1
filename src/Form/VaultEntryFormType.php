<?php

namespace App\Form;

use App\Entity\SystemOption;
use App\Entity\VaultEntry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VaultEntryFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $sports = include __DIR__ . '/../../config/vault_data/sports.php';
        $winnerOptionsBySport = include __DIR__ . '/../../config/vault_data/winner_options.php';

        $allWinnerOptions = [];
        foreach ($winnerOptionsBySport as $opts) {
            foreach ($opts as $label => $value) {
                $allWinnerOptions[$label] = $value;
            }
        }

        $builder
            ->add('placedAt', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date du pari',
            ])
            ->add('bookmaker', TextType::class, [
                'label' => 'Bookmaker (optionnel)',
                'required' => false,
                'attr' => ['placeholder' => 'Betclic, Winamax...'],
            ])
            ->add('betType', ChoiceType::class, [
                'label' => 'Type de pari',
                'choices' => [
                    'Simple' => VaultEntry::TYPE_SIMPLE,
                    'Live' => VaultEntry::TYPE_LIVE,
                    'Combiné' => VaultEntry::TYPE_COMBINE,
                    'Live Combiné' => VaultEntry::TYPE_LIVE_COMBINE,
                    'Système' => VaultEntry::TYPE_SYSTEME,
                ],
            ])
            ->add('systemOption', EntityType::class, [
                'class' => SystemOption::class,
                'choice_label' => fn (SystemOption $option) => $option->getLabel() . ' — ' . $option->getValue(),
                'placeholder' => 'Sélectionnez un système',
                'required' => false,
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie / Sport',
                'choices' => $sports,
                'required' => false,
            ])
            ->add('competition', TextType::class, ['label' => 'Compétition', 'required' => false])
            ->add('homeTeam', TextType::class, ['label' => 'Équipe domicile', 'required' => false])
            ->add('awayTeam', TextType::class, ['label' => 'Équipe extérieure', 'required' => false])
            ->add('winner', ChoiceType::class, [
                'label' => 'Résultat',
                'choices' => $allWinnerOptions,
                'required' => false,
            ])
            ->add('winnerLabel', HiddenType::class, ['required' => false])
            ->add('odds', NumberType::class, [
                'label' => 'Cote',
                'scale' => 2,
            ])
            ->add('stake', NumberType::class, [
                'label' => 'Mise (€)',
                'scale' => 2,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut du pari',
                'choices' => [
                    'En attente' => VaultEntry::STATUS_PENDING,
                    'Gagné' => VaultEntry::STATUS_WON,
                    'Perdu' => VaultEntry::STATUS_LOST,
                    'Annulé / remboursé' => VaultEntry::STATUS_VOID,
                    'Cashout' => VaultEntry::STATUS_CASHOUT,
                ],
            ])
            ->add('cashoutGain', NumberType::class, [
                'label' => 'Gain cashout (€)',
                'scale' => 2,
                'required' => false,
            ])
            ->add('isBoosted', HiddenType::class, ['required' => false])
            ->add('isFreebet', HiddenType::class, ['required' => false])
            ->add('isInsured', HiddenType::class, ['required' => false])
            ->add('selections', CollectionType::class, [
                'entry_type' => VaultEntrySelectionFormType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'label' => false,
                'required' => false,
            ])
        ;

        // Pré-remplit le choix "winner" selon la catégorie déjà connue (paris simple/live).
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($winnerOptionsBySport) {
            $data = $event->getData();

            if (in_array($data['betType'] ?? null, [VaultEntry::TYPE_COMBINE, VaultEntry::TYPE_LIVE_COMBINE, VaultEntry::TYPE_SYSTEME], true)) {
                return;
            }

            $sport = $data['category'] ?? null;
            $choices = $winnerOptionsBySport[$sport] ?? $winnerOptionsBySport['Autre'];
            $event->getForm()->add('winner', ChoiceType::class, ['choices' => $choices, 'label' => 'Résultat', 'required' => false]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VaultEntry::class,
        ]);
    }
}
