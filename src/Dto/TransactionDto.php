<?php

namespace App\Dto;

use App\Entity\Transaction;
use DateTimeInterface;

class TransactionDto
{
    public static function fromEntity(Transaction $transaction): array
    {
        $data = [
            'id' => $transaction->getId(),
            'created_at' => $transaction->getTransactionDate()->format(DateTimeInterface::ATOM),
            'type' => $transaction->getOperationType() === 1 ? 'payment' : 'deposit',
            'amount' => number_format($transaction->getAmount(), 2, '.', ''),
        ];

        if ($transaction->getCourse()) {
            $data['course_code'] = $transaction->getCourse()->getCode();
        }

        return $data;
    }
}
