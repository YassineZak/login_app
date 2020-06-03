<?php
/**
 * Created by PhpStorm.
 * User: yassine
 * Date: 23/05/20
 * Time: 12:03
 */

namespace App\Tests\Repository;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    use FixturesTrait;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testCount() {
        $this->loadFixtures([UserFixtures::class]);
        $users = $this->entityManager->getRepository(User::class)->count([]);
        $this->assertEquals(5, $users);
    }


}