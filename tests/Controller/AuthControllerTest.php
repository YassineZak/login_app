<?php
/**
 * Created by PhpStorm.
 * User: yassine
 * Date: 25/05/20
 * Time: 20:25
 */

namespace App\Tests\Controller;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use App\DataFixtures\UserNotActiveFixtures;
use App\DataFixtures\UserActiveFixtures;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class AuthControllerTest extends WebTestCase
{
    use FixturesTrait;

    public function testHomePageIsRestricted() {
        $client = static::createClient();
        $client->request('GET', '/');
        $this->assertResponseRedirects('http://localhost/login' );
    }

    public function testUserLoggedCanAccess() {
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        /**  @var User $user */
        $user = $em->getRepository(User::class)->findOneBy([]);
        $session = $client->getContainer()->get('session');
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $session->set('_security_main', serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);
        $client->request('GET', '/');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testDisplayLogin() {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('.login100-form-title', 'Login');
    }

    public function testLoginWithBadCredentials() {
        $client = static::createClient();
        $csrfToken = $client->getContainer()->get('security.csrf.token_manager')->getToken('authenticate');
        $client->request('POST', '/login', [
            '_username' => 'john@doe.fr',
            '_password' => '00000',
            '_csrf' => $csrfToken
        ]);
        $this->assertResponseRedirects('http://localhost/login');
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert.alert-danger', 'Invalid credentials.');
    }

    public function testAuthSucceed() {
        $client = static::createClient();
        $this->loadFixtures([UserActiveFixtures::class]);
        $csrfToken = $client->getContainer()->get('security.csrf.token_manager')->getToken('authenticate');
        $client->request('POST', '/login', [
            '_username' => 'user@active.fr',
            '_password' => '000000',
            '_csrf' => $csrfToken
        ]);
        $this->assertResponseRedirects('http://localhost/');
        $client->followRedirect();
        $this->assertSelectorTextContains('.btn.btn-primary', 'Logout');
    }

    public function testAuthUserNotActive() {
        $client = static::createClient();
        $this->loadFixtures([UserNotActiveFixtures::class]);
        $csrfToken = $client->getContainer()->get('security.csrf.token_manager')->getToken('authenticate');
        $client->request('POST', '/login', [
            '_username' => 'user@notactive.fr',
            '_password' => '000000',
            '_csrf' => $csrfToken
        ]);
        $this->assertResponseRedirects('http://localhost/login');
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert.alert-danger', 'Account is locked.');
    }
}