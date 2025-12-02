<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Product;
use App\Entity\Discussion;
use App\Entity\Message;
use App\Entity\Contract;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EntityCrudTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    protected function tearDown(): void
    {
        // Clean up any remaining entities
        if ($this->entityManager && $this->entityManager->isOpen()) {
            $this->entityManager->clear();
        }

        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testUserCrud(): void
    {
        // CREATE
        $user = new User();
        $user->setEmail('test@test.com');
        $user->setPassword('password123');
        $user->setName('Test User');
        $user->setUsername('testuser');
        $user->setType('artist');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setBio('Test bio');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->assertNotNull($user->getId());
        echo "✓ User CREATE: Success (ID: {$user->getId()})\n";

        // READ
        $foundUser = $this->entityManager->getRepository(User::class)->find($user->getId());
        $this->assertNotNull($foundUser);
        $this->assertEquals('test@test.com', $foundUser->getEmail());
        $this->assertEquals('Test User', $foundUser->getName());
        $this->assertEquals('testuser', $foundUser->getUsername());
        $this->assertEquals('artist', $foundUser->getType());
        $this->assertTrue($foundUser->isArtist());
        $this->assertFalse($foundUser->isPublisher());
        $this->assertEquals('Test User', $foundUser->getFullName());
        echo "✓ User READ: Success\n";

        // UPDATE
        $foundUser->setName('Updated User');
        $foundUser->setType('publisher');
        $this->entityManager->flush();

        $updatedUser = $this->entityManager->getRepository(User::class)->find($user->getId());
        $this->assertEquals('Updated User', $updatedUser->getName());
        $this->assertEquals('publisher', $updatedUser->getType());
        $this->assertTrue($updatedUser->isPublisher());
        echo "✓ User UPDATE: Success\n";

        // DELETE
        $userId = $user->getId();
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $deletedUser = $this->entityManager->getRepository(User::class)->find($userId);
        $this->assertNull($deletedUser);
        echo "✓ User DELETE: Success\n";
    }

    public function testProductCrud(): void
    {
        // First create an artist user
        $artist = new User();
        $artist->setEmail('artist@test.com');
        $artist->setPassword('password123');
        $artist->setName('Artist User');
        $artist->setType('artist');
        $artist->setRoles(['ROLE_ARTIST']);
        $artist->setIsVerified(true);

        $this->entityManager->persist($artist);
        $this->entityManager->flush();

        // CREATE Product
        $product = new Product();
        $product->setTitle('Test Product');
        $product->setDescription('Test description');
        $product->setCategory('painting');
        $product->setPrice('100.00');
        $product->setArtist($artist);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->assertNotNull($product->getId());
        echo "✓ Product CREATE: Success (ID: {$product->getId()})\n";

        // READ
        $foundProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertNotNull($foundProduct);
        $this->assertEquals('Test Product', $foundProduct->getTitle());
        $this->assertEquals($artist->getId(), $foundProduct->getArtist()->getId());
        echo "✓ Product READ: Success\n";

        // UPDATE
        $foundProduct->setTitle('Updated Product');
        $foundProduct->setPrice('150.00');
        $this->entityManager->flush();

        $updatedProduct = $this->entityManager->getRepository(Product::class)->find($product->getId());
        $this->assertEquals('Updated Product', $updatedProduct->getTitle());
        $this->assertEquals('150.00', $updatedProduct->getPrice());
        echo "✓ Product UPDATE: Success\n";

        // DELETE
        $productId = $product->getId();
        $this->entityManager->remove($product);
        $this->entityManager->flush();

        $deletedProduct = $this->entityManager->getRepository(Product::class)->find($productId);
        $this->assertNull($deletedProduct);
        echo "✓ Product DELETE: Success\n";

        // Cleanup
        $this->entityManager->remove($artist);
        $this->entityManager->flush();
    }

    public function testDiscussionCrud(): void
    {
        // Create artist and publisher
        $artist = new User();
        $artist->setEmail('artist2@test.com');
        $artist->setPassword('password123');
        $artist->setName('Artist User 2');
        $artist->setType('artist');
        $artist->setRoles(['ROLE_ARTIST']);
        $artist->setIsVerified(true);

        $publisher = new User();
        $publisher->setEmail('publisher@test.com');
        $publisher->setPassword('password123');
        $publisher->setName('Publisher User');
        $publisher->setType('publisher');
        $publisher->setRoles(['ROLE_PUBLISHER']);
        $publisher->setIsVerified(true);

        $product = new Product();
        $product->setTitle('Test Product 2');
        $product->setDescription('Test description 2');
        $product->setCategory('sculpture');
        $product->setArtist($artist);

        $this->entityManager->persist($artist);
        $this->entityManager->persist($publisher);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        // CREATE Discussion
        $discussion = new Discussion();
        $discussion->setSubject('Test Discussion');
        $discussion->setArtist($artist);
        $discussion->setPublisher($publisher);
        $discussion->setProduct($product);
        $discussion->setStatus('open');

        $this->entityManager->persist($discussion);
        $this->entityManager->flush();

        $this->assertNotNull($discussion->getId());
        echo "✓ Discussion CREATE: Success (ID: {$discussion->getId()})\n";

        // READ
        $foundDiscussion = $this->entityManager->getRepository(Discussion::class)->find($discussion->getId());
        $this->assertNotNull($foundDiscussion);
        $this->assertEquals('Test Discussion', $foundDiscussion->getSubject());
        $this->assertEquals($artist->getId(), $foundDiscussion->getArtist()->getId());
        $this->assertEquals($publisher->getId(), $foundDiscussion->getPublisher()->getId());
        echo "✓ Discussion READ: Success\n";

        // UPDATE
        $foundDiscussion->setStatus('closed');
        $foundDiscussion->setSubject('Updated Discussion');
        $this->entityManager->flush();

        $updatedDiscussion = $this->entityManager->getRepository(Discussion::class)->find($discussion->getId());
        $this->assertEquals('Updated Discussion', $updatedDiscussion->getSubject());
        $this->assertEquals('closed', $updatedDiscussion->getStatus());
        echo "✓ Discussion UPDATE: Success\n";

        // DELETE
        $discussionId = $discussion->getId();
        $this->entityManager->remove($discussion);
        $this->entityManager->flush();

        $deletedDiscussion = $this->entityManager->getRepository(Discussion::class)->find($discussionId);
        $this->assertNull($deletedDiscussion);
        echo "✓ Discussion DELETE: Success\n";

        // Cleanup
        $this->entityManager->remove($product);
        $this->entityManager->remove($artist);
        $this->entityManager->remove($publisher);
        $this->entityManager->flush();
    }

    public function testMessageCrud(): void
    {
        // Create users and discussion
        $sender = new User();
        $sender->setEmail('sender@test.com');
        $sender->setPassword('password123');
        $sender->setName('Sender User');
        $sender->setType('artist');
        $sender->setRoles(['ROLE_USER']);
        $sender->setIsVerified(true);

        $receiver = new User();
        $receiver->setEmail('receiver@test.com');
        $receiver->setPassword('password123');
        $receiver->setName('Receiver User');
        $receiver->setType('publisher');
        $receiver->setRoles(['ROLE_USER']);
        $receiver->setIsVerified(true);

        $product = new Product();
        $product->setTitle('Test Product 3');
        $product->setDescription('Test description 3');
        $product->setCategory('photo');
        $product->setArtist($sender);

        $discussion = new Discussion();
        $discussion->setSubject('Test Discussion 2');
        $discussion->setArtist($sender);
        $discussion->setPublisher($receiver);
        $discussion->setProduct($product);
        $discussion->setStatus('open');

        $this->entityManager->persist($sender);
        $this->entityManager->persist($receiver);
        $this->entityManager->persist($product);
        $this->entityManager->persist($discussion);
        $this->entityManager->flush();

        // CREATE Message
        $message = new Message();
        $message->setContent('Test message content');
        $message->setSender($sender);
        $message->setDiscussion($discussion);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $this->assertNotNull($message->getId());
        echo "✓ Message CREATE: Success (ID: {$message->getId()})\n";

        // READ
        $foundMessage = $this->entityManager->getRepository(Message::class)->find($message->getId());
        $this->assertNotNull($foundMessage);
        $this->assertEquals('Test message content', $foundMessage->getContent());
        $this->assertEquals($sender->getId(), $foundMessage->getSender()->getId());
        echo "✓ Message READ: Success\n";

        // UPDATE
        $foundMessage->setContent('Updated message content');
        $this->entityManager->flush();

        $updatedMessage = $this->entityManager->getRepository(Message::class)->find($message->getId());
        $this->assertEquals('Updated message content', $updatedMessage->getContent());
        echo "✓ Message UPDATE: Success\n";

        // DELETE
        $messageId = $message->getId();
        $this->entityManager->remove($message);
        $this->entityManager->flush();

        $deletedMessage = $this->entityManager->getRepository(Message::class)->find($messageId);
        $this->assertNull($deletedMessage);
        echo "✓ Message DELETE: Success\n";

        // Cleanup
        $this->entityManager->remove($discussion);
        $this->entityManager->remove($product);
        $this->entityManager->remove($sender);
        $this->entityManager->remove($receiver);
        $this->entityManager->flush();
    }

    public function testContractCrud(): void
    {
        // Create user
        $user = new User();
        $user->setEmail('contract@test.com');
        $user->setPassword('password123');
        $user->setName('Contract User');
        $user->setType('artist');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);

        $publisher = new User();
        $publisher->setEmail('contract_pub@test.com');
        $publisher->setPassword('password123');
        $publisher->setName('Contract Publisher');
        $publisher->setType('publisher');
        $publisher->setRoles(['ROLE_PUBLISHER']);
        $publisher->setIsVerified(true);

        $product = new Product();
        $product->setTitle('Contract Product');
        $product->setDescription('Contract description');
        $product->setCategory('painting');
        $product->setArtist($user);

        $discussion = new Discussion();
        $discussion->setSubject('Contract Discussion');
        $discussion->setArtist($user);
        $discussion->setPublisher($publisher);
        $discussion->setProduct($product);
        $discussion->setStatus('open');

        $this->entityManager->persist($user);
        $this->entityManager->persist($publisher);
        $this->entityManager->persist($product);
        $this->entityManager->persist($discussion);
        $this->entityManager->flush();

        // CREATE Contract
        $contract = new Contract();
        $contract->setTerms('Test terms and conditions - This is a detailed contract agreement between the artist and publisher covering all necessary aspects of the collaboration.');
        $contract->setCommissionRate('15.00');
        $contract->setStartDate(new \DateTime('2025-01-01'));
        $contract->setEndDate(new \DateTime('2025-12-31'));
        $contract->setDiscussion($discussion);
        $contract->setStatus('draft');

        $this->entityManager->persist($contract);
        $this->entityManager->flush();

        $this->assertNotNull($contract->getId());
        echo "✓ Contract CREATE: Success (ID: {$contract->getId()})\n";

        // READ
        $foundContract = $this->entityManager->getRepository(Contract::class)->find($contract->getId());
        $this->assertNotNull($foundContract);
        $this->assertStringContainsString('Test terms', $foundContract->getTerms());
        $this->assertEquals('draft', $foundContract->getStatus());
        $this->assertEquals('15.00', $foundContract->getCommissionRate());
        echo "✓ Contract READ: Success\n";

        // UPDATE
        $foundContract->setStatus('signed');
        $foundContract->setSignedBy($user);
        $foundContract->setCommissionRate('20.00');
        $this->entityManager->flush();

        $updatedContract = $this->entityManager->getRepository(Contract::class)->find($contract->getId());
        $this->assertEquals('20.00', $updatedContract->getCommissionRate());
        $this->assertEquals('signed', $updatedContract->getStatus());
        $this->assertEquals($user->getId(), $updatedContract->getSignedBy()->getId());
        echo "✓ Contract UPDATE: Success\n";

        // DELETE
        $contractId = $contract->getId();
        $this->entityManager->remove($contract);
        $this->entityManager->flush();

        $deletedContract = $this->entityManager->getRepository(Contract::class)->find($contractId);
        $this->assertNull($deletedContract);
        echo "✓ Contract DELETE: Success\n";

        // Cleanup
        $this->entityManager->remove($discussion);
        $this->entityManager->remove($product);
        $this->entityManager->remove($user);
        $this->entityManager->remove($publisher);
        $this->entityManager->flush();
    }

    public function testEntityRelationships(): void
    {
        // Test User-Product relationship
        $artist = new User();
        $artist->setEmail('rel_artist@test.com');
        $artist->setPassword('password123');
        $artist->setName('Relationship Artist');
        $artist->setType('artist');
        $artist->setRoles(['ROLE_ARTIST']);
        $artist->setIsVerified(true);

        $product1 = new Product();
        $product1->setTitle('Product 1');
        $product1->setDescription('Description 1');
        $product1->setCategory('painting');
        $product1->setArtist($artist);

        $product2 = new Product();
        $product2->setTitle('Product 2');
        $product2->setDescription('Description 2');
        $product2->setCategory('sculpture');
        $product2->setArtist($artist);

        $this->entityManager->persist($artist);
        $this->entityManager->persist($product1);
        $this->entityManager->persist($product2);
        $this->entityManager->flush();

        // Clear and reload to test relationship
        $this->entityManager->clear();
        $reloadedArtist = $this->entityManager->getRepository(User::class)->find($artist->getId());
        
        // Test that artist has products
        $this->assertCount(2, $reloadedArtist->getProducts());
        echo "✓ User-Product Relationship: Success (Artist has 2 products)\n";
        
        // Use reloaded artist for rest of test
        $artist = $reloadedArtist;

        // Test Discussion relationships
        $publisher = new User();
        $publisher->setEmail('rel_pub@test.com');
        $publisher->setPassword('password123');
        $publisher->setName('Relationship Publisher');
        $publisher->setType('publisher');
        $publisher->setRoles(['ROLE_PUBLISHER']);
        $publisher->setIsVerified(true);

        $discussion = new Discussion();
        $discussion->setSubject('Relationship Test');
        $discussion->setArtist($artist);
        $discussion->setPublisher($publisher);
        $discussion->setProduct($product1);
        $discussion->setStatus('open');

        // Need to get product1 from database since we cleared entity manager
        $product1FromDb = $this->entityManager->getRepository(Product::class)->findOneBy(['title' => 'Product 1']);
        
        $discussion->setProduct($product1FromDb);
        
        $this->entityManager->persist($publisher);
        $this->entityManager->persist($discussion);
        $this->entityManager->flush();

        // Clear and reload to test relationships
        $artistId = $artist->getId();
        $publisherId = $publisher->getId();
        $product1Id = $product1FromDb->getId();
        $this->entityManager->clear();
        
        $artist = $this->entityManager->getRepository(User::class)->find($artistId);
        $publisher = $this->entityManager->getRepository(User::class)->find($publisherId);
        $product1 = $this->entityManager->getRepository(Product::class)->find($product1Id);

        $this->assertCount(1, $artist->getArtistDiscussions());
        $this->assertCount(1, $publisher->getPublisherDiscussions());
        echo "✓ User-Discussion Relationship: Success\n";

        // Test Message-Discussion relationship
        // Reload discussion from database since we cleared entity manager
        $discussionFromDb = $this->entityManager->getRepository(Discussion::class)->findOneBy(['subject' => 'Relationship Test']);
        
        $message1 = new Message();
        $message1->setContent('Message 1');
        $message1->setSender($artist);
        $message1->setDiscussion($discussionFromDb);

        $message2 = new Message();
        $message2->setContent('Message 2');
        $message2->setSender($publisher);
        $message2->setDiscussion($discussionFromDb);

        $this->entityManager->persist($message1);
        $this->entityManager->persist($message2);
        $this->entityManager->flush();

        // Clear and reload to test relationships
        $discussionId = $discussionFromDb->getId();
        $this->entityManager->clear();
        
        $discussion = $this->entityManager->getRepository(Discussion::class)->find($discussionId);
        $artist = $this->entityManager->getRepository(User::class)->find($artistId);
        $publisher = $this->entityManager->getRepository(User::class)->find($publisherId);

        $this->assertCount(2, $discussion->getMessages());
        $this->assertCount(1, $artist->getMessages());
        $this->assertCount(1, $publisher->getMessages());
        echo "✓ Message-Discussion-User Relationship: Success\n";

        // Cleanup - reload all entities from database since some are detached
        $allProducts = $this->entityManager->getRepository(Product::class)->findAll();
        $allDiscussions = $this->entityManager->getRepository(Discussion::class)->findAll();
        $allMessages = $this->entityManager->getRepository(Message::class)->findAll();
        $allUsers = $this->entityManager->getRepository(User::class)->findAll();
        
        // Remove messages first due to foreign key constraints
        foreach ($allMessages as $msg) {
            $this->entityManager->remove($msg);
        }
        $this->entityManager->flush();
        
        // Remove discussions
        foreach ($allDiscussions as $disc) {
            $this->entityManager->remove($disc);
        }
        $this->entityManager->flush();
        
        // Remove products
        foreach ($allProducts as $prod) {
            $this->entityManager->remove($prod);
        }
        $this->entityManager->flush();
        
        // Remove users
        foreach ($allUsers as $u) {
            $this->entityManager->remove($u);
        }
        $this->entityManager->flush();
    }
}
