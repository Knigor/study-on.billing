<?php

namespace App\Controller;

use App\Service\BillingService;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use OpenApi\Attributes as OA;

class TransactionController extends AbstractController
{
    #[Route('/api/v1/transactions', name: 'transactions', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/transactions',
        summary: 'Получение списка транзакций пользователя',
        description: 'Фильтруется по типу транзакции, коду курса и флагу skip_expired',
        tags: ['Transactions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'filter[type]',
                in: 'query',
                required: false,
                description: 'Тип транзакции (например, payment, deposit)',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'filter[course_code]',
                in: 'query',
                required: false,
                description: 'Код курса',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'filter[skip_expired]',
                in: 'query',
                required: false,
                description: 'Пропускать ли истекшие (1 - да, 0 - нет)',
                schema: new OA\Schema(type: 'boolean')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список транзакций',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'type', type: 'string'),
                        new OA\Property(property: 'amount', type: 'number'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'course_code', type: 'string', nullable: true)
                    ]
                ))
            ),
            new OA\Response(response: 401, description: 'Неавторизован')
        ]
    )]
    #[Security(name: 'bearerAuth')]
    public function transactions(Request $request, BillingService $billingService): JsonResponse
    {
        $filter = $request->query->all('filter');

        $filters = [
            'type' => $filter['type'] ?? null,
            'course_code' => $filter['course_code'] ?? null,
            'skip_expired' => $filter['skip_expired'] ?? null,
        ];

        return $this->json($billingService->getUserTransactions($this->getUser(), $filters));
    }
}
