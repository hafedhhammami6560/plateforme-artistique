<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Product;
use App\Entity\Discussion;
use App\Entity\Message;
use App\Entity\Contract;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create Admin User
        $admin = new User();
        $admin->setEmail('admin@artconnect.com');
        $admin->setName('Admin User');
        $admin->setUsername('admin');
        $admin->setType('admin');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setIsVerified(true);
        $manager->persist($admin);

        // Create Artists
        $artists = [];
        for ($i = 1; $i <= 3; $i++) {
            $artist = new User();
            $artist->setEmail("artist{$i}@example.com");
            $artist->setName("Artist {$i}");
            $artist->setUsername("artist{$i}");
            $artist->setType('artist');
            $artist->setFirstName("Artist");
            $artist->setLastName("{$i}");
            $artist->setRoles(['ROLE_ARTIST', 'ROLE_USER']);
            $artist->setPassword($this->passwordHasher->hashPassword($artist, 'password'));
            $artist->setIsVerified(true);
            $manager->persist($artist);
            $artists[] = $artist;
        }

        // Create Publishers
        $publishers = [];
        for ($i = 1; $i <= 3; $i++) {
            $publisher = new User();
            $publisher->setEmail("pub{$i}@example.com");
            $publisher->setName("Publisher {$i}");
            $publisher->setUsername("publisher{$i}");
            $publisher->setType('publisher');
            $publisher->setFirstName("Publisher");
            $publisher->setLastName("{$i}");
            $publisher->setRoles(['ROLE_PUBLISHER', 'ROLE_USER']);
            $publisher->setPassword($this->passwordHasher->hashPassword($publisher, 'password'));
            $publisher->setIsVerified(true);
            $manager->persist($publisher);
            $publishers[] = $publisher;
        }

        // Create Products
        $products = [];
        $categories = ['painting', 'sculpture', 'photo', 'digital', 'mixed'];
        foreach ($artists as $index => $artist) {
            for ($j = 1; $j <= 2; $j++) {
                $product = new Product();
                $product->setTitle("Artwork {$index}-{$j}");
                $product->setDescription("Beautiful artwork created by {$artist->getName()}");
                $product->setCategory($categories[($index + $j) % count($categories)]);
                $product->setPrice((50 + ($index * 10) + ($j * 5)) . '.00');
                $product->setStatus('published');
                $product->setArtist($artist);
                $manager->persist($product);
                $products[] = $product;
            }
        }

        // Create Discussions
        $discussions = [];
        foreach ($artists as $index => $artist) {
            $publisher = $publishers[$index % count($publishers)];
            $product = $products[$index * 2];

            $discussion = new Discussion();
            $discussion->setSubject("Collaboration proposal for {$product->getTitle()}");
            $discussion->setArtist($artist);
            $discussion->setPublisher($publisher);
            $discussion->setProduct($product);
            $discussion->setStatus('open');
            $manager->persist($discussion);
            $discussions[] = $discussion;

            // Add messages to discussion
            $message1 = new Message();
            $message1->setContent("Hello, I'm interested in publishing your artwork.");
            $message1->setSender($publisher);
            $message1->setDiscussion($discussion);
            $manager->persist($message1);

            $message2 = new Message();
            $message2->setContent("Thank you for your interest! I'd love to discuss this further.");
            $message2->setSender($artist);
            $message2->setDiscussion($discussion);
            $manager->persist($message2);
        }

        // Create Contracts
        foreach ($discussions as $index => $discussion) {
            $contract = new Contract();
            $contract->setTerms("This contract establishes the terms of collaboration between the artist and publisher for the artwork '{$discussion->getProduct()->getTitle()}'. The publisher will promote and distribute the artwork, and the artist retains full copyright.");
            $contract->setCommissionRate((10 + ($index * 5)) . '.00');
            $contract->setStartDate(new \DateTime('+1 week'));
            $contract->setEndDate(new \DateTime('+1 year'));
            $contract->setDiscussion($discussion);
            $contract->setStatus($index === 0 ? 'signed' : 'proposed');
            
            if ($index === 0) {
                $contract->setSignedBy($discussion->getArtist());
                $contract->setSignedAt(new \DateTime());
            }
            
            $manager->persist($contract);
        }

        $manager->flush();
    }
}
