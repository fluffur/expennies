<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\Entity\PasswordReset;
use App\Entity\User;
use App\Entity\UserLoginCode;

class PasswordResetService
{
    public function __construct(
        private readonly EntityManagerServiceInterface $entityManagerService,
        private readonly HashService $hashService
    )
    {
    }

    public function generate(string $email): PasswordReset
    {
        $passwordReset = new PasswordReset();

        $token = bin2hex(random_bytes(32));
        $passwordReset->setToken($token);
        $passwordReset->setEmail($email);
        $passwordReset->setExpiration(new \DateTime('+30 minutes'));


        $this->entityManagerService->sync($passwordReset);

        return $passwordReset;
    }

    public function verify(User $user, string $code): bool
    {
        $userLoginCode = $this->entityManagerService->getRepository(UserLoginCode::class)->findOneBy(
            ['user' => $user, 'code' => $code, 'isActive' => true]
        );

        if (! $userLoginCode) {
            return false;
        }

        if ($userLoginCode->getExpiration() <= new \DateTime()) {
            return false;
        }

        return true;
    }

    public function findByToken(string $token): ?PasswordReset
    {
        return $this->entityManagerService
            ->getRepository(PasswordReset::class)
            ->createQueryBuilder('pr')
            ->select('pr')
            ->where('pr.token = :token')
            ->andWhere('pr.isActive = :active')
            ->andWhere('pr.expiration > :now')
            ->setParameters([
                'token' => $token,
                'active' => true,
                'now' => new \DateTime(),
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deactivateAllPasswordResets(string $email): void
    {
        $this->entityManagerService->getRepository(PasswordReset::class)
            ->createQueryBuilder('pr')
            ->update()
            ->set('pr.isActive', '0')
            ->where('pr.email = :email')
            ->andWhere('pr.isActive = 1')
            ->setParameter('email', $email)
            ->getQuery()
            ->execute();
    }

    public function updatePassword(User $user, #[\SensitiveParameter] string $password): void
    {
        $this->entityManagerService->wrapInTransaction(function () use ($user, $password) {
            $this->deactivateAllPasswordResets($user->getEmail());
            $user->setPassword($this->hashService->hashPassword($password));
            $this->entityManagerService->sync($user);

        });
    }
}
