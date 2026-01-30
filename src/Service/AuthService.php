<?php

namespace App\Service;

use App\DTO\LoginRequest;
use App\DTO\RegisterRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Interface\AuthServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function register(RegisterRequest $registerRequest): User
    {
        if ($this->userRepository->findOneBy(['email' => $registerRequest->email])) {
            throw new \RuntimeException('User with this email already exists');
        }

        if ($this->userRepository->findOneBy(['username' => $registerRequest->username])) {
            throw new \RuntimeException('User with this username already exists');
        }

        $user = new User();
        $user->setUsername($registerRequest->username);
        $user->setEmail($registerRequest->email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $registerRequest->password));
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function login(LoginRequest $loginRequest): ?User
    {
        $user = $this->userRepository->findOneBy(['email' => $loginRequest->email]);

        if (!$user || !$this->validateCredentials($user, $loginRequest->password)) {
            return null;
        }

        return $user;
    }

    public function validateCredentials(User $user, string $password): bool
    {
        return $this->passwordHasher->isPasswordValid($user, $password);
    }
}

