<?php

namespace App\Service;

use App\Service\PaymentService;
use App\Dto\CourseDto;
use App\Dto\TransactionDto;
use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\EnumCourseType;
use App\Exception\NegativeDepositValueException;
use App\Repository\CourseRepository;
use App\Repository\TransactionRepository;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BillingService
{
    public function __construct(
        private CourseRepository $courseRepository,
        private TransactionRepository $transactionRepository,
        private EntityManagerInterface $em,
        private PaymentService $paymentService
    ) {
    }

    public function getAllCourses(): array
    {
        return array_map(
            static fn(Course $course) => CourseDto::fromEntity($course),
            $this->courseRepository->findAll()
        );
    }

    public function getCourseByCode(string $code): array
    {
        $course = $this->courseRepository->findOneBy(['code' => $code]);
        if (!$course) {
            throw new NotFoundHttpException('Course not found');
        }
        return CourseDto::fromEntity($course);
    }

    public function payCourse(string $code, User $user): array
    {
        $course = $this->courseRepository->findOneBy(['code' => $code]);
        if (!$course) {
            throw new NotFoundHttpException('Course not found');
        }

        $type = EnumCourseType::byCode($course->getType());

        if ($type === EnumCourseType::FREE) {
            return [
                'success' => true,
                'course_type' => $type->value,
                'expires_at' => null,
            ];
        }

        try {
            $transaction = $this->paymentService->pay($user, $course);
        } catch (\Throwable $e) {
            return [
                'code' => 406,
                'message' => $e->getMessage(),
            ];
        }

        return [
            'success' => true,
            'course_type' => $type->value,
            'expires_at' => $transaction->getValidUntil()?->format(DateTimeInterface::ATOM),
        ];
    }

    public function getUserTransactions(User $user, array $filters = []): array
    {
        $transactions = $this->transactionRepository->findByFilters($user, $filters);

        return array_map(
            fn(Transaction $t) => TransactionDto::fromEntity($t),
            $transactions
        );
    }

    public function deposit(User $user, float $amount): array
    {
        try {
            $this->paymentService->deposit($user, $amount);

            return [
                'success' => true,
                'new_balance' => $user->getBalance(),
            ];
        } catch (NegativeDepositValueException $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            return [
                'code' => 500,
                'message' => 'Ошибка при пополнении баланса',
            ];
        }
    }
}
