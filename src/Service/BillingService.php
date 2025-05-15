<?php

namespace App\Service;

use App\Dto\CourseDto;
use App\Dto\TransactionDto;
use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\EnumCourseType;
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
        private EntityManagerInterface $em
    ) {}

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

        $price = $course->getPrice();
        if ($user->getBalance() < $price) {
            return [
                'code' => 406,
                'message' => 'На вашем счету недостаточно средств',
            ];
        }

        // списываем деньги
        $user->setBalance($user->getBalance() - $price);

        $transaction = new Transaction();
        $transaction->setOwner($user);
        $transaction->setCourse($course);
        $transaction->setAmount($price);
        $transaction->setTransactionDate(new \DateTime());
        $transaction->setOperationType(1); // 1 — payment

        if ($type === EnumCourseType::RENT) {
            $expires = (new \DateTime())->modify('+30 days');
            $transaction->setValidUntil($expires);
        }

        $this->em->persist($transaction);
        $this->em->flush();

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
}
