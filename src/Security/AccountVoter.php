<?php

namespace App\Security;

use App\Entity\Account;
use App\Entity\Admin;
use App\Entity\Client;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class AccountVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';

    protected function supports(string $attribute, $subject): bool
    {
        // On ne vote que sur les attributs qu'on connait
        if (!in_array($attribute, [self::VIEW, self::EDIT])) {
            return false;
        }

        // Et seulement si le sujet est bien une instance de Account
        return $subject instanceof Account;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Si l'utilisateur n'est pas connecté → refuse
        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Account $account */
        $account = $subject;

        // Admin a tous les droits
        if ($user instanceof Admin || in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Seuls les clients propriétaires du compte peuvent voir/éditer
        return $account->getClient() === $user;
    }
}