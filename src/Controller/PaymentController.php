<?php

namespace App\Controller;

use App\Service\BillingService;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;

class PaymentController extends AbstractController
{
    #[Route('/api/v1/courses/{code}/pay', name: 'course_pay', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/courses/{code}/pay',
        summary: 'Оплата курса',
        description: 'Производит попытку оплатить курс по коду',
        tags: ['Payments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'code',
                in: 'path',
                required: true,
                description: 'Код курса',
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Оплата прошла успешно',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string', nullable: true),
                        new OA\Property(property: 'transaction_id', type: 'integer', nullable: true)
                    ]
                )
            ),
            new OA\Response(
                response: 406,
                description: 'Недостаточно средств или другая ошибка оплаты',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Неавторизован')
        ]
    )]
    #[Security(name: 'bearerAuth')]
    public function pay(string $code, BillingService $billingService): JsonResponse
    {
        $user = $this->getUser();
        $result = $billingService->payCourse($code, $user);

        return isset($result['success']) ? $this->json($result) : $this->json($result, 406);
    }

    #[Route('/api/v1/deposit', name: 'api_deposit', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/deposit',
        summary: 'Пополнение баланса',
        description: 'Позволяет пользователю пополнить баланс',
        tags: ['Payments'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'amount', type: 'number', example: 1000)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Баланс успешно пополнен',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'new_balance', type: 'number')
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Некорректная сумма пополнения или ошибка валидации',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'code', type: 'integer'),
                        new OA\Property(property: 'message', type: 'string')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Неавторизован')
        ],
        security: [['bearerAuth' => []]]
    )]
    #[Security(name: 'bearerAuth')]
    public function deposit(Request $request, BillingService $billingService): JsonResponse
    {
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $amount = $data['amount'] ?? null;

        if (!is_numeric($amount)) {
            return $this->json([
                'code' => 400,
                'message' => 'Некорректная сумма пополнения',
            ], 400);
        }

        $result = $billingService->deposit($user, (float)$amount);

        return $this->json($result, $result['code'] ?? 200);
    }
}
