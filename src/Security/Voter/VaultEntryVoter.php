<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\VaultEntry;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Un membre ne peut modifier/supprimer que ses propres paris NTS Vault.
 */
class VaultEntryVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'VAULT_ENTRY_EDIT' && $subject instanceof VaultEntry;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User || !$subject instanceof VaultEntry) {
            return false;
        }

        return $subject->getBankroll()?->getUser() === $user;
    }
}
