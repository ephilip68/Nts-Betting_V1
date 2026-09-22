<?php

namespace App\DataFixtures;

use App\Entity\Pronostic;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Utilisateur de test standard
        $user = new User();
        $user->setEmail('thomas11@nts-betting.test');
        $user->setNickname('Thomas11');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'test123'));
        $user->setDateInscription(new \DateTime());
        $user->setPhoto(null);
        $user->setPoints(0);
        $user->setVipUntil(null);
        $user->setIsVerified(true);
        $manager->persist($user);

        // Utilisateur VIP de test (pour vérifier le déblocage des pronostics VIP)
        $vipUser = new User();
        $vipUser->setEmail('vip@nts-betting.test');
        $vipUser->setNickname('MembreVIP');
        $vipUser->setRoles(['ROLE_USER']);
        $vipUser->setPassword($this->passwordHasher->hashPassword($vipUser, 'test123'));
        $vipUser->setDateInscription(new \DateTime());
        $vipUser->setPoints(0);
        $vipUser->setVipUntil((new \DateTime())->modify('+30 days'));
        $vipUser->setIsVerified(true);
        $manager->persist($vipUser);

        // Compte admin — accès à /admin/pronostics
        $admin = new User();
        $admin->setEmail('admin@nts-betting.test');
        $admin->setNickname('Admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setDateInscription(new \DateTime());
        $admin->setPoints(0);
        $admin->setVipUntil(null);
        $admin->setIsVerified(true);
        $manager->persist($admin);

        // Pronostic du jour (gratuit, mis en avant)
        $featured = new Pronostic();
        $featured->setSport('football');
        $featured->setCompetition('Ligue des Champions');
        $featured->setTeamHome('Real Madrid');
        $featured->setTeamAway('AC Milan');
        $featured->setMatchDate((new \DateTime())->modify('+1 day')->setTime(21, 0));
        $featured->setBetType('1X2');
        $featured->setBetValue('Real Madrid');
        $featured->setOdds('1.65');
        $featured->setConfidence(4);
        $featured->setAnalysis('Le Real Madrid reste sur une série de victoires consécutives à domicile en Ligue des Champions. Face à un Milan en difficulté défensive, la valeur est du côté des Madrilènes.');
        $featured->setIsVip(false);
        $featured->setIsFeatured(true);
        $manager->persist($featured);

        // Pronostic gratuit classique
        $free = new Pronostic();
        $free->setSport('football');
        $free->setCompetition('Ligue 1');
        $free->setTeamHome('PSG');
        $free->setTeamAway('Marseille');
        $free->setMatchDate((new \DateTime())->modify('+2 days')->setTime(20, 45));
        $free->setBetType('Double chance');
        $free->setBetValue('PSG ou nul');
        $free->setOdds('1.30');
        $free->setConfidence(5);
        $free->setAnalysis('Le PSG à domicile face à un OM diminué par les absences.');
        $free->setIsVip(false);
        $manager->persist($free);

        // Pronostic VIP
        $vip = new Pronostic();
        $vip->setSport('basketball');
        $vip->setCompetition('NBA');
        $vip->setTeamHome('Lakers');
        $vip->setTeamAway('Celtics');
        $vip->setMatchDate((new \DateTime())->modify('+3 days')->setTime(2, 30));
        $vip->setBetType('Handicap');
        $vip->setBetValue('Lakers -4.5');
        $vip->setOdds('1.90');
        $vip->setConfidence(4);
        $vip->setAnalysis('Analyse approfondie réservée aux membres VIP : rotation des joueurs, forme récente et historique des confrontations directes.');
        $vip->setIsVip(true);
        $manager->persist($vip);

        $manager->flush();
    }
}
