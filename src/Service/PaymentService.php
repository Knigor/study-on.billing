<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\EnumCourseType;
use App\Enum\EnumTransactionType;
use App\Exception\NegativeDepositValueException;
use App\Exception\NotEnoughBalanceException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class PaymentService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @throws NegativeDepositValueException
     */
    public function deposit(User $user, float $amount): void
    {
        if ($amount <= 0) {
            throw new NegativeDepositValueException('Сумма пополнения должна быть положительной');
        }

        $this->entityManager->wrapInTransaction(function () use ($user, $amount) {
            $transaction = new Transaction();
            $transaction->setTransactionDate(new DateTimeImmutable());
            $transaction->setOperationType(EnumTransactionType::DEPOSIT->value); // 2 — deposit
            $transaction->setAmount($amount);
            $transaction->setOwner($user);

            $user->setBalance($user->getBalance() + $amount);

            $this->entityManager->persist($transaction);
            $this->entityManager->persist($user);
        });
    }

    public function pay(User $user, Course $course): Transaction
    {
        $price = $course->getPrice();
        if ($user->getBalance() < $price) {
            throw new NotEnoughBalanceException('Недостаточно средств');
        }

        $transactionTime = new DateTimeImmutable();

        $transaction = new Transaction();
        $transaction->setTransactionDate($transactionTime);
        $transaction->setOperationType(EnumTransactionType::PAYMENT->value); // 1 — payment
        $transaction->setCourse($course);
        $transaction->setAmount($price);
        $transaction->setOwner($user);

        if ($course->getType() === EnumCourseType::RENT->value) {
            $transaction->setValidUntil($transactionTime->modify('+30 days'));
        }

        $this->entityManager->wrapInTransaction(function () use ($transaction, $user, $price) {
            $user->setBalance($user->getBalance() - $price);
            $this->entityManager->persist($transaction);
            $this->entityManager->persist($user);
        });

        return $transaction;
    }
}
