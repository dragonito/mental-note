<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use AppBundle\Entity\User;
use AppBundle\Entity\Tag;

/**
 * @author Tobias Olry (tobias.olry@web.de)
 */
class TagRepository extends EntityRepository
{

    public function search($query, User $user)
    {

        $qb = $this->createQueryBuilder('t');

        $qb
            ->andWhere("t IN (SELECT DISTINCT t2 FROM " . Tag::class . " t2 INNER JOIN t2.entries e WHERE e.user = :user)")
            ->setParameter('user', $user)
        ;

        if (strlen($query) > 0) {
            $qb
                ->andWhere("t.name LIKE :query")
                ->setParameter('query', "%$query%")
            ;
        }

        $qb->add('orderBy', 't.name ASC');

        return $qb;
    }
}
