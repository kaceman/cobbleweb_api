<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Adds a User entity to the database.
     *
     * @param User $entity The User entity to be added.
     * @param bool $flush  Whether to flush changes to the database immediately.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(User $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Removes a User entity from the database.
     *
     * @param User $entity The User entity to be removed.
     * @param bool $flush  Whether to flush changes to the database immediately.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(User $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Finds active users created since a specified date.
     *
     * @param \DateTime $sinceDate The date since which to find active users.
     *
     * @return User[] An array of active User entities.
     */
    public function findActiveUsersCreatedSince(\DateTime $sinceDate)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.createdAt >= :sinceDate')
            ->andWhere('u.active = :isActive')
            ->setParameter('sinceDate', $sinceDate)
            ->setParameter('isActive', true)
            ->getQuery()
            ->getResult();
    }

}
