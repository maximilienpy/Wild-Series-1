<?php


namespace App\DataFixtures;

use Faker;
use App\Entity\Episode;
use App\Service\Slugify;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class EpisodeFixtures extends Fixture implements DependentFixtureInterface
{

    private $slugify;

    public function __construct(Slugify $slugify)
    {
        $this->slugify = $slugify;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');

        for ($i = 0 ;$i < 150 ;$i++) {
            $episode = new Episode();
            $episode->setNumber($faker->numberBetween(1,5));
            $episode->setSynopsis($faker->paragraph);
            $episode->setTitle($faker->text(20));
            $title = $this->slugify->generate($episode->getTitle());
            $episode->setSlug($title);
            $manager->persist($episode);
            $episode->setSeason($this->getReference('season_'.rand(0,49)));
        }
        $manager->flush();
    }

    public function getDependencies()
    {
        return [SeasonFixtures::class];
    }
}