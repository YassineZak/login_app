<?php
namespace App\Security;

use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Flex\Response;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user)
    {

    }

    public function checkPostAuth(UserInterface $user)
    {
        $isActive = $user->getStatus();
        if($isActive == false){
            throw new LockedException("Veuillez activer votre compte via le lien d'activation");
        }
    }
}
