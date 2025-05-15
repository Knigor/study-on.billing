<?php

namespace App\DataFixtures;

use App\Config\CourseType;
use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{

    private array $data = [
        [
            'chars_code' => 'english-language',
            'type' => CourseType::RENT,
            'price' => 1000.50,
        ],
        [
            'chars_code' => 'python-basics',
            'type' => CourseType::FREE,
            'price' => 0.00,
        ],
        [
            'chars_code' => 'data-science-advanced',
            'type' => CourseType::BUY,
            'price' => 2500.75,
        ],
        [
            'chars_code' => 'web-development',
            'type' => CourseType::RENT,
            'price' => 1800.00,
        ],
        [
            'chars_code' => 'machine-learning',
            'type' => CourseType::BUY,
            'price' => 3500.00,
        ],
        [
            'chars_code' => 'graphic-design',
            'type' => CourseType::RENT,
            'price' => 1200.25,
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach ($this->data as $dataCourse) {
            $course = new Course();
            $course->setCode($dataCourse['chars_code'])
                ->setType($dataCourse['type'])
                ->setPrice($dataCourse['price']);
            $manager->persist($course);
        }

        $manager->flush();
    }
}