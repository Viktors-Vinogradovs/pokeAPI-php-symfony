<?php

namespace App\Repository;

use App\Entity\Favorite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favorite>
 */
class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    /**
     * @return list<string>
     */
    public function findNamesByClientId(string $clientId): array
    {
        $rows = $this->createQueryBuilder('f')
            ->select('f.pokemonName')
            ->where('f.clientId = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('f.createdAt', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return $rows;
    }

    public function add(string $clientId, string $pokemonName): void
    {
        $existing = $this->findOneBy([
            'clientId' => $clientId,
            'pokemonName' => $pokemonName,
        ]);

        if ($existing !== null) {
            return;
        }

        $favorite = new Favorite($clientId, $pokemonName);
        $this->getEntityManager()->persist($favorite);
        $this->getEntityManager()->flush();
    }

    public function remove(string $clientId, string $pokemonName): void
    {
        $existing = $this->findOneBy([
            'clientId' => $clientId,
            'pokemonName' => $pokemonName,
        ]);

        if ($existing === null) {
            return;
        }

        $this->getEntityManager()->remove($existing);
        $this->getEntityManager()->flush();
    }

    public function countByClientId(string $clientId): int
    {
        return $this->count(['clientId' => $clientId]);
    }
}
