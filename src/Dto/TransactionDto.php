<?php

namespace App\Dto;

class TransactionDto
{
    public int $id;
    public string $createdAt;
    public string $type;
    public ?string $courseCode;
    public string $amount;

    public function __construct(int $id, string $createdAt, string $type, ?string $courseCode, string $amount)
    {
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->type = $type;
        $this->courseCode = $courseCode;
        $this->amount = $amount;
    }
}
