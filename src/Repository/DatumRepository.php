<?php

namespace App\Repository;

use App\Enum\DatumTypeEnum;
use Doctrine\ORM\EntityRepository;

/**
 * Class DatumRepository
 *
 * @package App\Repository
 */
class DatumRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function findSigns() : array
    {
        return $this
            ->createQueryBuilder('d')
            ->leftJoin('d.item', 'i')
            ->leftJoin('d.image', 'd_i')
            ->addSelect('d_i')
            ->andWhere('d.type = :type')
            ->setParameter('type', DatumTypeEnum::TYPE_SIGN)
            ->getQuery()
            ->getResult()
        ;
    }
}