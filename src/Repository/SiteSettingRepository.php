<?php

namespace App\Repository;

use App\Entity\SiteSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SiteSetting>
 */
class SiteSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteSetting::class);
    }

    /**
     * Récupère l'unique ligne de réglages, en la créant si elle n'existe pas encore.
     */
    public function getSettings(): SiteSetting
    {
        $settings = $this->find(1);

        if (!$settings) {
            $settings = new SiteSetting();
            $this->getEntityManager()->persist($settings);
            $this->getEntityManager()->flush();
        }

        return $settings;
    }
}
