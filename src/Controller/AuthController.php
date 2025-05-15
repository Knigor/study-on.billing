<?php

namespace App\Controller;

use App\Dto\UserDto;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    #[Route('/api/v1/auth', name: 'api_auth', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/auth',
        summary: 'Аутентификация пользователя',
        description: 'Аутентификация пользователя, ввод почты и пароля',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            description: 'Authentication credentials',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UserDto::class))
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authentication successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'access_token', type: 'string'),
                        new OA\Property(
                            property: 'user',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'))
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Неверные данные',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(type: 'string')
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Не авторизован',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string'),
                        new OA\Property(property: 'message', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function authenticate(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): Response {
        $data = json_decode($request->getContent(), true);

        $dto = new UserDto();
        $dto->email = $data['email'] ?? '';
        $dto->password = $data['password'] ?? '';

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $formattedErrors = [];
            foreach ($errors as $error) {
                $formattedErrors[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $formattedErrors], Response::HTTP_BAD_REQUEST);
        }

        /** @var User|null $user */
        $user = $em->getRepository(User::class)->findOneBy(['email' => $dto->email]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $dto->password)) {
            return new JsonResponse([
                'error' => 'Invalid credentials',
                'message' => 'Wrong email or password'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $accessToken = $jwtManager->create($user);

        $refreshPayload = [
            'username' => $user->getUserIdentifier(),
            'exp' => time() + 60 * 60 * 24 * 7
        ];
        $refreshToken = base64_encode(json_encode($refreshPayload));

        $refreshTokenCookie = Cookie::create('refresh_token')
            ->withValue($refreshToken)
            ->withHttpOnly(true)
            ->withSameSite('lax')
            ->withSecure(false)
            ->withPath('/')
            ->withExpires(strtotime('+7 days'));

        $response = new JsonResponse([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ]
        ]);
        $response->headers->setCookie($refreshTokenCookie);

        return $response;
    }

    #[Route('/api/v1/register', name: 'api_register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/register',
        summary: 'Регистрация нового пользователя',
        description: 'Создание нового пользователя, ввод почты и пароля',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            description: 'User registration data',
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UserDto::class, groups: ['registration']))
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Успешная регистрация',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'access_token', type: 'string'),
                        new OA\Property(
                            property: 'user',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'))
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Неверно введены данные',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(type: 'string')
                        ),
                        new OA\Property(property: 'error', type: 'string'),
                        new OA\Property(property: 'message', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $dto = new UserDto();
        $dto->email = $data['email'] ?? '';
        $dto->password = $data['password'] ?? '';
        $dto->roles = $data['roles'] ?? ['ROLE_USER'];

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $formattedErrors = [];
            foreach ($errors as $error) {
                $formattedErrors[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $formattedErrors], JsonResponse::HTTP_BAD_REQUEST);
        }

        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $dto->email]);
        if ($existingUser) {
            return new JsonResponse([
                'error' => 'Email already taken',
                'message' => 'User with this email already exists'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($dto->email);
        $hashedPassword = $passwordHasher->hashPassword($user, $dto->password);
        $user->setPassword($hashedPassword);
        $user->setRoles($dto->roles);

        $em->persist($user);
        $em->flush();

        $accessToken = $jwtManager->create($user);

        $refreshPayload = [
            'username' => $user->getUserIdentifier(),
            'exp' => time() + 60 * 60 * 24 * 7
        ];
        $refreshToken = base64_encode(json_encode($refreshPayload));

        $refreshTokenCookie = Cookie::create('refresh_token')
            ->withValue($refreshToken)
            ->withHttpOnly(true)
            ->withSameSite('lax')
            ->withSecure(false)
            ->withPath('/')
            ->withExpires(strtotime('+7 days'));

        $response = new JsonResponse([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ]
        ], JsonResponse::HTTP_CREATED);
        $response->headers->setCookie($refreshTokenCookie);

        return $response;
    }


    #[Route('/api/v1/token/refresh', name: 'api_token_refresh', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/token/refresh',
        summary: 'Обновление access_token по refresh_token',
        description: 'Обновление JWT access_token используя refresh_token',
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Токен успешно обновлен',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'access_token', type: 'string', description: 'Новый access token'),
                        new OA\Property(property: 'refresh_token', type: 'string', description: 'Refresh token из cookie (без изменений)')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Ошибка аутентификации',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', description: 'Код ошибки'),
                        new OA\Property(property: 'message', type: 'string', description: 'Подробности ошибки')
                    ]
                )
            )
        ]
    )]
    public function refreshToken(
        Request $request,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $refreshToken = $request->cookies->get('refresh_token');

        if (!$refreshToken) {
            return new JsonResponse([
                'error' => 'Missing refresh token',
                'message' => 'Refresh token not provided'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $payload = json_decode(base64_decode($refreshToken), true);

        if (!isset($payload['username'], $payload['exp']) || $payload['exp'] < time()) {
            return new JsonResponse([
                'error' => 'Invalid refresh token',
                'message' => 'Token is malformed or expired'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        /** @var User|null $user */
        $user = $em->getRepository(User::class)->findOneBy(['email' => $payload['username']]);

        if (!$user) {
            return new JsonResponse([
                'error' => 'User not found',
                'message' => 'No user associated with this token'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $newAccessToken = $jwtManager->create($user);

        return new JsonResponse([
            'access_token' => $newAccessToken,
            'refresh_token' => $refreshToken
        ]);
    }


}