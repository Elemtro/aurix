<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\VoiceSample;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VoiceSample>
 */
final class VoiceSampleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VoiceSample::class);
    }

    public function findOneByUserAndIndex(User $user, int $phraseIndex): ?VoiceSample
    {
        return $this->findOneBy(['user' => $user, 'phraseIndex' => $phraseIndex]);
    }

    /**
     * @return list<VoiceSample>
     */
    public function findForUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['phraseIndex' => 'ASC']);
    }
}
