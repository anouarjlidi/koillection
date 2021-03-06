<?php

namespace App\Doctrine;

/**
 * Class QueryNameGenerator
 *
 * @package App\Doctrine
 */
final class QueryNameGenerator
{
    /**
     * @var int
     */
    private $incrementedAssociation = 1;

    /**
     * @var int
     */
    private $incrementedName = 1;

    /**
     * @param string $association
     * @return string
     */
    public function generateJoinAlias(string $association): string
    {
        return sprintf('%s_a%d', $association, $this->incrementedAssociation++);
    }

    /**
     * @param string $name
     * @return string
     */
    public function generateParameterName(string $name): string
    {
        return sprintf('%s_p%d', str_replace('.', '_', $name), $this->incrementedName++);
    }
}
