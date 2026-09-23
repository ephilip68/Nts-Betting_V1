<?php

namespace App\Form;

use App\Entity\VaultEntrySelection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Une sélection (un match) au sein d'un pari combiné ou système du NTS Vault.
 */
class VaultEntrySelectionFormType extends AbstractType
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
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie / Sport',
                'choices' => $sports,
                'row_attr' => ['class' => 'form-row'],
            ])
            ->add('competition', TextType::class, [
                'label' => 'Compétition',
                'required' => false,
                'row_attr' => ['class' => 'form-row'],
            ])
            ->add('homeTeam', TextType::class, [
                'label' => 'Équipe domicile',
                'row_attr' => ['class' => 'form-row'],
            ])
            ->add('awayTeam', TextType::class, [
                'label' => 'Équipe extérieure',
                'row_attr' => ['class' => 'form-row'],
            ])
            ->add('winner', ChoiceType::class, [
                'label' => 'Résultat',
                'choices' => $allWinnerOptions,
                'required' => false,
                'row_attr' => ['class' => 'form-row'],
            ])
            ->add('winnerLabel', HiddenType::class, ['required' => false])
            ->add('odds', NumberType::class, [
                'label' => 'Cote',
                'scale' => 2,
                'row_attr' => ['class' => 'form-row'],
            ])
            ->add('field', ChoiceType::class, [
                'label' => 'Statut',
                'row_attr' => ['class' => 'form-row'],
                'choices' => [
                    'Gagné' => VaultEntrySelection::FIELD_WIN,
                    'Perdu' => VaultEntrySelection::FIELD_LOSE,
                    'En attente' => VaultEntrySelection::FIELD_PENDING,
                ],
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($winnerOptionsBySport) {
            $data = $event->getData();
            $sport = $data['category'] ?? null;
            $choices = $winnerOptionsBySport[$sport] ?? $winnerOptionsBySport['Autre'];
            $event->getForm()->add('winner', ChoiceType::class, ['choices' => $choices, 'label' => 'Résultat', 'required' => false]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VaultEntrySelection::class,
        ]);
    }
}
