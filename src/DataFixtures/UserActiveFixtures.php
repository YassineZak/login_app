<?php
namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserActiveFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        for ($i = 0; $i < 1; $i++) {
            $user = new User();
            $user->setEmail('user@active.fr');
            $user->setPassword('$2y$10$0NWibH0auObGn2DEAAv8gul/UQtGYBWlahoJSghQiw7VL1jebgEuW');
            $user->setUsername('user'.$i);
            $user->setStatus(true);
            $user->setBirth(new \DateTime('01/10/1988'));
            $user->setTokenRegistration();
            $manager->persist($user);
        }

        $manager->flush();
    }
}
