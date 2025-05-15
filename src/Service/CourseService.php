<?php

namespace App\Service;

use App\Dto\CourseDto;
use App\Entity\Course;
use App\Repository\CourseRepository;

class CourseService
{
    public function __construct(private CourseRepository $courseRepository) {}

    /**
     * @return CourseDto[]
     */
    public function getAllCourses(): array
    {
        $courses = $this->courseRepository->findAll();
        $result = [];
        foreach ($courses as $course) {
            $result[] = $this->toDto($course);
        }
        return $result;
    }

    public function getCourseByCode(string $code): ?CourseDto
    {
        $course = $this->courseRepository->findOneBy(['code' => $code]);
        if (!$course) {
            return null;
        }
        return $this->toDto($course);
    }

    private function toDto(Course $course): CourseDto
    {
        $typeMap = [
            0 => 'free',
            1 => 'rent',
            2 => 'buy',
        ];
        return new CourseDto(
            $course->getCode(),
            $typeMap[$course->getType()] ?? 'unknown',
            $course->getPrice() !== null ? number_format($course->getPrice(), 2, '.', '') : null,
        );
    }
}
