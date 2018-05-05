<?php

namespace App\Repository;

use App\Entity\Collection;
use App\Entity\Tag;
use App\Entity\User;
use App\Model\Search;
use Doctrine\ORM\EntityRepository;

/**
 * Class TagRepository
 *
 * @package App\Repository
 */
class TagRepository extends EntityRepository
{
    const RESULTS_PER_PAGE = 10;

    public function findById(string $id) : ?Tag
    {
        return $this
            ->createQueryBuilder('t')
            ->leftJoin('t.items', 'i')
            ->leftJoin('i.image', 'i_i')
            ->addSelect('i, i_i')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return array
     */
    public function findAll() : array
    {
        return $this
            ->createQueryBuilder('t')
            ->orderBy('t.label', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param $itemsCount
     * @param int $page
     * @param string|null $search
     * @param bool $ignoreTagsWithoutItems
     * @return array
     */
    public function countItemsByTag($itemsCount, $page = 1, string $search = null, bool $ignoreTagsWithoutItems = false) : array
    {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('DISTINCT t as tag')
            ->addSelect('count(DISTINCT i.id) as itemCount')
            ->addSelect('(count(DISTINCT i.id)*100.0/:totalItems) as percent')
            ->from('App\Entity\Tag', 't')
            ->leftJoin('t.items', 'i')
            ->groupBy('t.id')
            ->orderBy('itemCount', 'DESC')
            ->setFirstResult(($page - 1) * self::RESULTS_PER_PAGE)
            ->setMaxResults(self::RESULTS_PER_PAGE)
            ->setParameter('totalItems', $itemsCount > 0 ? $itemsCount : 1)
        ;

        if (true === $ignoreTagsWithoutItems) {
            $qb->having('count(i.id) > 0');
        }

        if (\is_string($search) && !empty($search)) {
            $qb
                ->andWhere('LOWER(t.label) LIKE LOWER(:search)')
                ->setParameter('search', '%'.trim($search).'%')
            ;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string|null $search
     * @param bool $ignoreTagsWithoutItems
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countTags(string $search = null, bool $ignoreTagsWithoutItems = false) : int
    {
        $qb = $this->_em
            ->createQueryBuilder()
            ->select('count(DISTINCT t.id)')
            ->from('App\\Entity\\Tag', 't')
        ;

        if (true === $ignoreTagsWithoutItems) {
            $qb
                ->innerJoin('t.items', 'i')
                ->having('count(i.id) > 1')
            ;
        }

        if (\is_string($search) && !empty($search)) {
            $qb
                ->andWhere('LOWER(t.label) LIKE LOWER(:search)')
                ->setParameter('search', '%'.trim($search).'%')
            ;
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findOneByLabelAndOwner(string $label, User $owner) : ?Tag
    {
        return $this
            ->createQueryBuilder('t')
            ->where('t.owner = :owner')
            ->andWhere('t.label = :label')
            ->orderBy('t.label', 'ASC')
            ->setParameter('owner', $owner)
            ->setParameter('label', $label)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @param string $string
     * @return array
     */
    public function findLike(string $string) : array
    {
        return $this
            ->createQueryBuilder('t')
            ->andWhere('LOWER(t.label) LIKE LOWER(:label)')
            ->orderBy('t.label', 'ASC')
            ->setParameter('label', '%'.$string.'%')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Find tags that 100% of a collection items have in common.
     *
     * @param Collection $collection
     *
     * @return array
     */
    public function findRelatedToCollection(Collection $collection) : array
    {
        return $this
            ->createQueryBuilder('t')
            ->leftJoin('t.items', 'i')
            ->where('i.collection = :collection')
            ->orderBy('t.label', 'ASC')
            ->groupBy('t.id')
            ->having('count(i.id) =
                (SELECT COUNT(i2.id)
                FROM App\Entity\Item i2
                WHERE i2.collection = :collection)')
            ->setParameter('collection', $collection)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Find for search.
     *
     * @param User $owner
     * @param Search $search
     *
     * @return array
     */
    public function findForSearch(User $owner, Search $search) : array
    {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('t as tag')
            ->addSelect('count(i.id) as itemCount')
            ->addSelect('(count(i.id)*100.0/:totalItems) as percent')
            ->from('App\Entity\Tag', 't')
            ->leftJoin('t.items', 'i')
            ->groupBy('t.id')
            ->orderBy('itemCount', 'DESC')
            ->setParameter('totalItems', $owner->getItems()->count())
        ;

        if (\is_string($search->getSearch()) && !empty($search->getSearch())) {
            $qb
                ->andWhere('LOWER(t.label) LIKE LOWER(:search)')
                ->setParameter('search', '%'.$search->getSearch().'%')
            ;
        }

        if ($search->getCreatedAt() instanceof \DateTime) {
            $createdAt = $search->getCreatedAt();
            $qb
                ->andWhere('t.createdAt BETWEEN :start AND :end')
                ->setParameter('start', $createdAt->setTime(0, 0, 0))
                ->setParameter('end', $createdAt->setTime(23, 59, 59))
            ;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param User $owner
     * @return array
     */
    public function findByUserAndWithoutItems(User $owner)
    {
        return $this
            ->createQueryBuilder('t')
            ->leftJoin('t.items', 'i')
            ->where('i.id IS NULL')
            ->andWhere('t.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array
     */
    public function findAllForHighlight()
    {
        return $this->_em
            ->createQueryBuilder()
            ->select('DISTINCT partial t.{id, label}, LENGTH(t.label) as HIDDEN length')
            ->from('App\\Entity\\Tag', 't')
            ->orderBy('length', 'DESC')
            ->getQuery()
            ->getArrayResult()
        ;
    }

    /**
     * @param Tag $tag
     * @return array
     */
    public function findRelatedTags(Tag $tag)
    {
        $subrequest = $this->_em->createQueryBuilder()
            ->select('DISTINCT i2.id')
            ->from('App\\Entity\\Item', 'i2')
            ->leftJoin('i2.tags', 't2')
            ->where('t2.id = :tag')
        ;

        return $this->_em
            ->createQueryBuilder()
            ->select('DISTINCT partial t.{id, label}')
            ->from('App\\Entity\\Tag', 't')
            ->leftJoin('t.items', 'i')
            ->where("i.id IN ($subrequest)")
            ->andWhere('t.id != :tag')
            ->orderBy('t.label', 'ASC')
            ->setParameter('tag', $tag)
            ->getQuery()
            ->getArrayResult()
        ;
    }

    /**
     * @return int
     */
    public function countAll() : int
    {
        return $this
            ->createQueryBuilder('t')
            ->select('count(t.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}