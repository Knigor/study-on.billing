<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use App\DataFixtures\UserFixtures;

class BillingUserControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        // Получаем хэшер из контейнера
        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        // Загружаем фикстуры
        $loader = new Loader();
        $loader->addFixture(new UserFixtures($passwordHasher));

        $purger = new ORMPurger($this->entityManager);
        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->purge();
        $executor->execute($loader->getFixtures());
    }

    public function testSuccessfulAuthentication(): void
    {
        $credentials = [
            'email' => 'user@example.com',
            'password' => '123456',
        ];

        $this->client->request(
            'POST',
            '/api/v1/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($credentials)
        );

        self::assertResponseIsSuccessful();
        self::assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('access_token', $response);
        $this->assertArrayHasKey('user', $response);
        $this->assertArrayHasKey('id', $response['user']);
        $this->assertArrayHasKey('roles', $response['user']);
        $this->assertNotEmpty($response['access_token']);

        $this->assertTrue($this->client->getResponse()->headers->has('Set-Cookie'));
        $this->assertStringContainsString(
            'refresh_token',
            $this->client->getResponse()->headers->get('Set-Cookie')
        );
    }

    // авторизация с неправильной почтой

    public function testAuthWithExistingEmail(): void
    {
        $this->client->request('POST', '/api/v1/auth', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => 'user@examplecomzzz',
            'password' => '123456',
        ]));

        self::assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('email', $response['errors']);
        $this->assertEquals('Неверный формат email', $response['errors']['email']);
    }


    // авторизация с неправильным паролем

    public function testAuthWithWrongPassword(): void
    {
        $this->client->request('POST', '/api/v1/auth', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => 'user@example.com',
            'password' => '123',
        ]));

        self::assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('password', $response['errors']);
        $this->assertEquals('Пароль должен содержать минимум 6 символов', $response['errors']['password']);
    }



    public function testSuccessfulRegistration(): void
    {
        $this->client->request('POST', '/api/v1/register', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => 'newuser@example.com',
            'password' => 'newpass123',
        ]));

        self::assertResponseStatusCodeSame(201);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('access_token', $response);
        $this->assertArrayHasKey('user', $response);
        $this->assertArrayHasKey('id', $response['user']);
        $this->assertArrayHasKey('roles', $response['user']);
    }


    // пользователь уже есть в системе
    public function testRegisterWithExistingEmail(): void
    {
        $this->client->request('POST', '/api/v1/register', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => 'user@example.com',
            'password' => '123456',
        ]));

        self::assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Email already taken', $response['error']);
    }



    public function testRegisterWithInvalidData(): void
    {
        $this->client->request('POST', '/api/v1/register', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => '',
            'password' => '',
        ]));

        self::assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $response);
    }

    public function testGetCurrentUserSuccess(): void
    {
        // Сначала логинимся
        $this->client->request('POST', '/api/v1/auth', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => 'user@example.com',
            'password' => '123456',
        ]));

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $token = $response['access_token'];

        // Затем запрашиваем /api/v1/users/current
        $this->client->request('GET', '/api/v1/users/current', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $token,
        ]);

        self::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('username', $response);
        $this->assertArrayHasKey('roles', $response);
        $this->assertArrayHasKey('balance', $response);
    }

    public function testGetCurrentUserUnauthenticated(): void
    {
        $this->client->request('GET', '/api/v1/users/current');
        self::assertResponseStatusCodeSame(401);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('JWT Token not found', $response['message']);
    }
}
