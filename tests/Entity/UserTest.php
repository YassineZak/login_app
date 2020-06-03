<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{

    public function testUserEntity()
    {
        $user = new User();
        $user->setEmail('test@test.fr')
            ->setPassword('test123')
            ->setBirth(new \DateTime('01/10/1988'))
            ->setUsername('yassinezak')
            ->setStatus(false);
        $this->assertEquals("test@test.fr", $user->getEmail());
        $this->assertEquals("test123", $user->getPassword());
        $this->assertEquals(new \DateTime('01/10/1988'), $user->getBirth());
        $this->assertEquals('yassinezak', $user->getUsername());
        $this->assertEquals(false, $user->getStatus());
    }
}