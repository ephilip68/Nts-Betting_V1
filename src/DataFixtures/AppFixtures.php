<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();

        $user->setEmail('thomas11@nts-betting.test');
        $user->setNickname('Thomas11');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('test123');
        $user->setDateInscription(new \DateTime());
        $user->setPhoto(null);
        $user->setPoints(0);
        $user->setVipUntil(null);
        $user->setIsVerified(true);

        $manager->persist($user);

        $manager->flush();
    }
}