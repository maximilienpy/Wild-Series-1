<?php


namespace App\DataFixtures;

use App\Entity\Episode;
use Faker;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class EpisodeFixtures extends Fixture implements DependentFixtureInterface
{


    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');

        for ($i = 0 ;$i < 150 ;$i++) {
            $episode = new Episode();
            $episode->setNumber($faker->numberBetween(0,5));
            $episode->setSynopsis($faker->paragraph);
            $episode->setTitle($faker->text(20));
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