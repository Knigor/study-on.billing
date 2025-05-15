<?php

namespace App\DataFixtures;

use App\Config\TransactionType;
use App\Entity\Transaction;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use App\Entity\Course;
use DateTimeImmutable;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class TransactionFixtures extends Fixture implements DependentFixtureInterface
{

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            CourseFixtures::class,
        ];
    }

    private array $data = [
        'knigor1337@gmail.com' => [
            [
                'course' => null,
                'type' => TransactionType::DEPOSIT,
                'value' => 1000,
                'created_at' => '2025-05-15T14:48:01+00:00',
                'expires_at' => null,
            ],
            [
                'course' => 'english-language',
                'type' => TransactionType::PAYMENT,
                'value' => 2000,
                'created_at' => '2025-05-15T14:48:01+00:00',
                'expires_at' => '2025-05-26T14:48:01+00:00',
            ],
            [
                'course' => null,
                'type' => TransactionType::DEPOSIT,
                'value' => 1000,
                'created_at' => '2025-05-15T14:48:01+00:00',
                'expires_at' => null,
            ],
            [
                'course' => 'python-basics',
                'type' => TransactionType::PAYMENT,
                'value' => 2000,
                'created_at' => '2025-05-15T14:48:01+00:00',
                'expires_at' => '2025-05-26T14:48:01+00:00',
            ],
            [
                'course' => 'data-science-advanced',
                'type' => TransactionType::PAYMENT,
                'value' => 2000,
                'created_at' => '2025-05-15T14:48:01+00:00',
                'expires_at' => '2025-05-26T14:48:01+00:00',
            ],
            [
                'course' => 'web-development',
                'type' => TransactionType::PAYMENT,
                'value' => 2500,
                'created_at' => '2025-05-15T14:48:01+00:00',
                'expires_at' => '2025-05-26T14:48:01+00:00',
            ],
            [
                'course' => 'machine-learning',
                'type' => TransactionType::PAYMENT,
                'value' => 3700,
                'created_at' => '2025-05-15T14:48:01+00:00',
                'expires_at' => '2025-05-26T14:48:01+00:00',
            ],
            [
                'course' => 'graphic-design',
                'type' => TransactionType::PAYMENT,
                'value' => 3700,
                'created_at' => '2025-05-15T14:48:01+00:00',
                'expires_at' => '2025-05-26T14:48:01+00:00',
            ],

        ],

    ];

    /**
     * @throws \DateMalformedStringException
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->data as $userEmail => $dataTrans) {
            $user = $manager->getRepository(User::class)->findOneBy(['email' => $userEmail]);
            foreach ($dataTrans as $dataOneTrans) {
                $transaction = new Transaction();
                $course = $manager->getRepository(Course::class)->findOneBy(['code' => $dataOneTrans['course']]);
                $transaction->setOwner($user)
                    ->setCourse($course)
                    ->setOperationType($dataOneTrans['type'])
                    ->setAmount($dataOneTrans['value'])
                    ->setTransactionDate(new DateTimeImmutable($dataOneTrans['created_at'], new \DateTimeZone('Europe/Moscow')))
                    ->setValidUntil($dataOneTrans['expires_at'] ? new DateTimeImmutable($dataOneTrans['expires_at'], new \DateTimeZone('Europe/Moscow')) : null);
                $manager->persist($transaction);
            }
        }

        $manager->flush();
    }

}