<?php

namespace App\Controller;

use App\Service\BillingService;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use OpenApi\Attributes as OA;

class CourseController extends AbstractController
{
    #[Route('/api/v1/courses', name: 'courses_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/courses',
        summary: 'Получение списка всех курсов',
        tags: ['Courses'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список курсов',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'code', type: 'string'),
                        new OA\Property(property: 'title', type: 'string'),
                        new OA\Property(property: 'type', type: 'string'),
                        new OA\Property(property: 'price', type: 'number')
                    ]
                ))
            )
        ]
    )]
    public function list(BillingService $billingService): JsonResponse
    {
        return $this->json($billingService->getAllCourses());
    }

    #[Route('/api/v1/courses/{code}', name: 'course_get', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/courses/{code}',
        summary: 'Получить курс по коду',
        description: 'Возвращает курс по переданному коду',
        tags: ['Course'],
        parameters: [
            new OA\Parameter(name: 'code', in: 'path', required: true, description: 'Код курса', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Информация о курсе',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(response: 404, description: 'Курс не найден')
        ]
    )]
    public function get(string $code, BillingService $billingService): JsonResponse
    {
        return $this->json($billingService->getCourseByCode($code));
    }
}
