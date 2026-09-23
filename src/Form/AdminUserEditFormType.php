<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Édition d'un membre depuis l'admin : rôle admin et octroi manuel de VIP
 * (offert/ambassadeur/test — indépendant de l'abonnement Stripe, voir
 * User::isVip()). L'email, le mot de passe et l'abonnement Stripe ne sont
 * pas modifiables ici — l'abonnement se gère via le Customer Portal Stripe.
 */
class AdminUserEditFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('isAdmin', CheckboxType::class, [
                'label' => 'Administrateur (accès au back-office)',
                'required' => false,
                'mapped' => false,
            ])
            ->add('vipUntil', DateType::class, [
                'label' => 'VIP offert jusqu\'au (indépendant de Stripe, laisser vide pour retirer)',
                'widget' => 'single_text',
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
