<?php

namespace App\Security\Voter;

use App\Entity\Bankroll;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Un membre ne peut voir/modifier/supprimer que ses propres bankrolls NTS Vault.
 */
class BankrollVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'BANKROLL_EDIT' && $subject instanceof Bankroll;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User || !$subject instanceof Bankroll) {
            return false;
        }

        return $subject->getUser() === $user;
    }
}
