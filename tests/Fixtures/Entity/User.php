<?php

declare(strict_types=1);

namespace Ecourty\DoctrineExportBundle\Tests\Fixtures\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $email;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $firstName;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $lastName;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive;

    #[ORM\Column(type: Types::INTEGER)]
    private int $age;

    #[ORM\Column(type: Types::FLOAT)]
    private float $score;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $phone;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $city;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $country;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private string $zipCode;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $loginCount;

    public function __construct(
        string $email,
        string $firstName,
        string $lastName,
        bool $isActive,
        int $age,
        float $score,
        \DateTimeInterface $createdAt,
        string $phone,
        string $city,
        string $country,
        string $zipCode,
        int $loginCount,
        ?string $bio = null,
        ?\DateTimeInterface $lastLoginAt = null,
    ) {
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->isActive = $isActive;
        $this->age = $age;
        $this->score = $score;
        $this->createdAt = $createdAt;
        $this->phone = $phone;
        $this->city = $city;
        $this->country = $country;
        $this->zipCode = $zipCode;
        $this->loginCount = $loginCount;
        $this->bio = $bio;
        $this->lastLoginAt = $lastLoginAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getZipCode(): string
    {
        return $this->zipCode;
    }

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function getLoginCount(): int
    {
        return $this->loginCount;
    }
}
