<?php

namespace App\Controller;

use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class UserController extends AbstractController
{
    #[Route('/api/v1/users/current', name: 'api_users_current', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/users/current',
        summary: 'Получение данных текущего пользователя',
        description: 'Возвращает информацию о текущем аутентифицированном пользователе',
        tags: ['User'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный запрос',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'username', type: 'string'),
                        new OA\Property(
                            property: 'roles',
                            type: 'array',
                            items: new OA\Items(type: 'string')
                        ),
                        new OA\Property(
                            property: 'balance',
                            type: 'number',
                            format: 'float'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Пользователь не аутентифицирован',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string')
                    ]
                )
            )
        ]
    )]
    #[Security(name: 'bearerAuth')]
    public function getCurrentUser(#[CurrentUser] $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse([
                'error' => 'Пользователь не аутентифицирован'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'username' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'balance' => $user->getBalance()
        ]);
    }
}