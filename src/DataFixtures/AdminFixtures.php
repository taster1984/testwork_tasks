<?php

namespace App\DataFixtures;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AdminFixtures extends Fixture
{
    private $encoder;

    private $em;

    public function __construct(UserPasswordEncoderInterface $encoder, EntityManagerInterface $entityManager)
    {
        $this->encoder = $encoder;
        $this->em = $entityManager;
    }

    public function load(\Doctrine\Persistence\ObjectManager $manager)
    {
        $adminsData = [
            0 => [
                'username' => 'admin',
                'role' => ['ROLE_ADMIN'],
                'password' => 'admin'
            ]
        ];

        foreach ($adminsData as $admin) {
            $newAdmin = new User();
            $newAdmin->setUsername($admin['username']);
            $newAdmin->setPassword($this->encoder->encodePassword($newAdmin, $admin['password']));
            $newAdmin->setRoles($admin['role']);
            $this->em->persist($newAdmin);
        }

        $this->em->flush();
    }
}