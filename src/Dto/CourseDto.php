<?php

namespace App\Dto;

class CourseDto
{
    public string $code;
    public string $type;
    public ?string $price;

    public function __construct(string $code, string $type, ?string $price = null)
    {
        $this->code = $code;
        $this->type = $type;
        $this->price = $price;
    }
}
