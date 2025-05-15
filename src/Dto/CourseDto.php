<?php

namespace App\Dto;

use App\Entity\Course;
use App\Enum\EnumCourseType;

class CourseDto
{
    public static function fromEntity(Course $course): array
    {
        $data = [
            'code' => $course->getCode(),
            'type' => EnumCourseType::byCode($course->getType())->value,
        ];

        if ($course->getPrice() !== null) {
            $data['price'] = number_format($course->getPrice(), 2, '.', '');
        }

        return $data;
    }
}
