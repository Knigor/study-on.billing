<?php

namespace App\Service;

use App\Dto\TransactionDto;
use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\TransactionRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class TransactionService
{
    public function __construct(
        private EntityManagerInterface $em,
        private TransactionRepository $transactionRepository,
        private CourseRepository $courseRepository
    ) {}

    /**
     * Оплата курса пользователем
     * @throws Exception если недостаточно средств или курс не найден
     */
    public function payForCourse(User $user, string $courseCode): array
    {
        /** @var Course|null $course */
        $course = $this->courseRepository->findOneBy(['code' => $courseCode]);
        if (!$course) {
            throw new Exception('Курс не найден');
        }

        $price = $course->getPrice() ?? 0.0;
        if ($user->getBalance() < $price) {
            throw new Exception('На вашем счету недостаточно средств', 406);
        }

        // Списываем средства
        $user->setBalance($user->getBalance() - $price);

        // Создаем транзакцию платежа
        $transaction = new Transaction();
        $transaction->setOwner($user);
        $transaction->setCourse($course);
        $transaction->setOperationType(Transaction::OPERATION_PAYMENT); // В Entity надо добавить константы
        $transaction->setTransactionDate(new DateTimeImmutable());

        // Для аренды устанавливаем срок действия (например 1 месяц)
        $expiresAt = null;
        if ($course->getType() === 1) { // rent
            $expiresAt = (new DateTimeImmutable())->modify('+30 days');
            $transaction->setValidUntil($expiresAt);
        }

        $this->em->persist($transaction);
        $this->em->persist($user);
        $this->em->flush();

        $typeMap = [
            0 => 'free',
            1 => 'rent',
            2 => 'buy',
        ];

        return [
            'success' => true,
            'course_type' => $typeMap[$course->getType()] ?? 'unknown',
            'expires_at' => $expiresAt?->format(\DateTimeInterface::ATOM) ?: null,
        ];
    }

    /**
     * Получение истории транзакций пользователя с фильтрами
     * @param User $user
     * @param array $filters
     * @return TransactionDto[]
     */
    public function getUserTransactions(User $user, array $filters = []): array
    {
        $qb = $this->transactionRepository->createQueryBuilder('t')
            ->where('t.owner = :user')
            ->setParameter('user', $user);

        if (!empty($filters['type'])) {
            $typeMap = ['payment' => Transaction::OPERATION_PAYMENT, 'deposit' => Transaction::OPERATION_DEPOSIT];
            if (isset($typeMap[$filters['type']])) {
                $qb->andWhere('t.operationType = :opType')->setParameter('opType', $typeMap[$filters['type']]);
            }
        }

        if (!empty($filters['course_code'])) {
            $qb->join('t.course', 'c')
                ->andWhere('c.code = :code')
                ->setParameter('code', $filters['course_code']);
        }

        if (!empty($filters['skip_expired']) && $filters['skip_expired']) {
            $qb->andWhere('t.validUntil IS NULL OR t.validUntil > CURRENT_TIMESTAMP()');
        }

        $qb->orderBy('t.transactionDate', 'DESC');

        $transactions = $qb->getQuery()->getResult();

        $result = [];
        foreach ($transactions as $transaction) {
            $result[] = $this->toDto($transaction);
        }

        return $result;
    }

    private function toDto(Transaction $transaction): TransactionDto
    {
        $typeMap = [
            Transaction::OPERATION_PAYMENT => 'payment',
            Transaction::OPERATION_DEPOSIT => 'deposit',
        ];

        return new TransactionDto(
            $transaction->getId(),
            $transaction->getTransactionDate()->format(\DateTimeInterface::ATOM),
            $typeMap[$transaction->getOperationType()] ?? 'unknown',
            $transaction->getCourse()?->getCode(),
            number_format($transaction->getCourse()?->getPrice() ?? 0, 2, '.', '')
        );
    }
}
