<?php

namespace App\Repository;

use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefreshToken>
 */
class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function findValidToken(string $token): ?RefreshToken
    {
        $refreshToken = $this->findOneBy(['token' => $token]);
        
        if (!$refreshToken || !$refreshToken->isValid()) {
            return null;
        }

        return $refreshToken;
    }

    public function revokeUserTokens(User $user): void
    {
        $tokens = $this->createQueryBuilder('rt')
            ->where('rt.user = :user')
            ->andWhere('rt.is_revoked = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        foreach ($tokens as $token) {
            $token->setIsRevoked(true);
        }
    }

    public function deleteExpiredTokens(): int
    {
        return $this->createQueryBuilder('rt')
            ->delete()
            ->where('rt.expires_at < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}

