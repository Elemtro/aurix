<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserPhrase;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserPhrase>
 */
final class UserPhraseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPhrase::class);
    }

    /**
     * @return list<UserPhrase>
     */
    public function findForUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['phraseIndex' => 'ASC']);
    }
}
