<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

/**
 * Class PasswordUpdater
 *
 * @package App\Service
 */
class PasswordUpdater
{
    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    /**
     * PasswordUpdater constructor.
     * @param EncoderFactoryInterface $encoderFactory
     */
    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * @param User $user
     * @return User
     */
    public function hashPassword(User $user) : User
    {
        $plainPassword = $user->getPlainPassword();

        if (0 === \strlen($plainPassword)) {
            return $user;
        }

        $encoder = $this->encoderFactory->getEncoder($user);
        $user->setSalt(null);

        $hashedPassword = $encoder->encodePassword($plainPassword, $user->getSalt());
        $user->setPassword($hashedPassword);
        $user->eraseCredentials();

        return $user;
    }
}
