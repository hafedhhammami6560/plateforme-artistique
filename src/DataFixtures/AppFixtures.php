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
        // ========================================
        // 1. CRÉATION DES UTILISATEURS
        // ========================================
        
        // Artistes
        $artistes = [];
        $artistsData = [
            ['Marie', 'Dubois', 'marie.dubois@artconnect.com', 'artist'],
            ['Pierre', 'Martin', 'pierre.martin@artconnect.com', 'artist'],
            ['Sophie', 'Bernard', 'sophie.bernard@artconnect.com', 'artist'],
            ['Lucas', 'Thomas', 'lucas.thomas@artconnect.com', 'artist'],
            ['Emma', 'Petit', 'emma.petit@artconnect.com', 'artist'],
        ];

        foreach ($artistsData as [$firstName, $lastName, $email, $type]) {
            $user = new User();
            $user->setEmail($email);
            $user->setUsername(strtolower($firstName) . '_' . strtolower($lastName));
            $user->setFullName($firstName . ' ' . $lastName);
            $user->setType($type);
            $user->setRoles(['ROLE_ARTIST']);
            $user->setAvatar('https://i.pravatar.cc/150?u=' . urlencode($email));
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $manager->persist($user);
            $artistes[] = $user;
        }

        // Publishers
        $publishers = [];
        $publishersData = [
            ['Galaxy Arts', 'galaxy.arts@artconnect.com', 'publisher'],
            ['Creative Co', 'creative.co@artconnect.com', 'publisher'],
            ['Digital Dreams', 'digital.dreams@artconnect.com', 'publisher'],
        ];

        foreach ($publishersData as [$name, $email, $type]) {
            $user = new User();
            $user->setEmail($email);
            $user->setUsername(strtolower(str_replace(' ', '_', $name)));
            $user->setFullName($name);
            $user->setType($type);
            $user->setRoles(['ROLE_PUBLISHER']);
            $user->setAvatar('https://i.pravatar.cc/150?u=' . urlencode($email));
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $manager->persist($user);
            $publishers[] = $user;
        }

        // Admin
        $admin = new User();
        $admin->setEmail('admin@artconnect.com');
        $admin->setUsername('admin');
        $admin->setFullName('Administrateur Système');
        $admin->setType('publisher');
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_PUBLISHER']);
        $admin->setAvatar('https://i.pravatar.cc/150?u=admin@artconnect.com');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $manager->persist($admin);

        // Utilisateurs de test simples
        $testArtist = new User();
        $testArtist->setEmail('artist@artconnect.com');
        $testArtist->setUsername('artist');
        $testArtist->setFullName('Test Artiste');
        $testArtist->setType('artist');
        $testArtist->setRoles(['ROLE_ARTIST']);
        $testArtist->setAvatar('https://i.pravatar.cc/150?u=artist@artconnect.com');
        $testArtist->setPassword($this->passwordHasher->hashPassword($testArtist, 'password'));
        $manager->persist($testArtist);
        $artistes[] = $testArtist;

        $testPublisher = new User();
        $testPublisher->setEmail('publisher@artconnect.com');
        $testPublisher->setUsername('publisher');
        $testPublisher->setFullName('Test Publisher');
        $testPublisher->setType('publisher');
        $testPublisher->setRoles(['ROLE_PUBLISHER']);
        $testPublisher->setAvatar('https://i.pravatar.cc/150?u=publisher@artconnect.com');
        $testPublisher->setPassword($this->passwordHasher->hashPassword($testPublisher, 'password'));
        $manager->persist($testPublisher);
        $publishers[] = $testPublisher;

        $manager->flush();

        // ========================================
        // 2. CRÉATION DES PRODUITS
        // ========================================
        
        $products = [];
        $productsData = [
            ['Coucher de Soleil Abstrait', 'peinture', 'Une œuvre abstraite capturant la beauté du crépuscule', 2500.00, $artistes[0]],
            ['Portrait Moderne', 'peinture', 'Portrait expressionniste aux couleurs vives', 3200.00, $artistes[1]],
            ['Sculpture en Bronze', 'sculpture', 'Sculpture contemporaine en bronze patiné', 8500.00, $artistes[2]],
            ['Paysage Urbain', 'photo', 'Photographie nocturne de la ville', 1200.00, $artistes[3]],
            ['Art Numérique Futuriste', 'digital', 'Création numérique inspirée de la science-fiction', 1800.00, $artistes[4]],
            ['Nature Morte', 'peinture', 'Composition florale à l\'huile', 2100.00, $artistes[0]],
            ['Sculpture Abstraite', 'sculpture', 'Forme abstraite en acier inoxydable', 6700.00, $artistes[2]],
            ['Portrait Photographique', 'photo', 'Série de portraits en noir et blanc', 950.00, $artistes[3]],
            ['Art Digital Géométrique', 'digital', 'Composition géométrique animée', 1500.00, $artistes[4]],
            ['Paysage Montagneux', 'peinture', 'Peinture acrylique de montagnes enneigées', 2800.00, $artistes[1]],
        ];

        foreach ($productsData as [$title, $category, $description, $price, $artist]) {
            $product = new Product();
            $product->setTitle($title);
            $product->setCategory($category);
            $product->setDescription($description);
            $product->setPrice($price);
            $product->setArtist($artist);
            $product->setIsPublished(true);
            $product->setImage('https://picsum.photos/seed/' . urlencode($title) . '/300/200');
            $manager->persist($product);
            $products[] = $product;
        }

        $manager->flush();

        // ========================================
        // 3. CRÉATION DES DISCUSSIONS
        // ========================================
        
        $discussions = [];
        $discussionsData = [
            [$products[0], $artistes[0], $publishers[0], 'pending', 'Intéressé par votre œuvre'],
            [$products[1], $artistes[1], $publishers[1], 'active', 'Collaboration potentielle'],
            [$products[2], $artistes[2], $publishers[0], 'active', 'Exposition sculpture'],
            [$products[3], $artistes[3], $publishers[2], 'active', 'Série photographique'],
            [$products[4], $artistes[4], $publishers[1], 'closed', 'Art numérique - projet terminé'],
            [$products[5], $artistes[0], $publishers[2], 'active', 'Collection nature'],
            [$products[6], $artistes[2], $publishers[1], 'pending', 'Demande d\'information'],
            [$products[7], $artistes[3], $publishers[0], 'active', 'Portfolio photo'],
            [$products[8], $artistes[4], $publishers[2], 'active', 'NFT collection'],
            [$products[9], $artistes[1], $publishers[0], 'closed', 'Négociation terminée'],
            [$products[0], $artistes[0], $publishers[1], 'archived', 'Ancienne discussion'],
            [$products[1], $artistes[1], $publishers[2], 'active', 'Nouveau projet'],
            [$products[2], $artistes[2], $publishers[2], 'pending', 'Proposition galerie'],
            [$products[3], $artistes[3], $publishers[1], 'active', 'Magazine photo'],
            [$products[4], $artistes[4], $publishers[0], 'active', 'Collaboration digitale'],
        ];

        foreach ($discussionsData as $index => [$product, $artist, $publisher, $status, $subject]) {
            $discussion = new Discussion();
            $discussion->setProduct($product);
            $discussion->setArtist($artist);
            $discussion->setPublisher($publisher);
            $discussion->setStatus($status);
            $discussion->setSubject($subject);
            $discussion->setCreatedAt(new \DateTimeImmutable('-' . (30 - $index * 2) . ' days'));
            $discussion->setUpdatedAt(new \DateTimeImmutable('-' . (15 - $index) . ' days'));
            $manager->persist($discussion);
            $discussions[] = $discussion;
        }

        $manager->flush();

        // ========================================
        // 4. CRÉATION DES MESSAGES
        // ========================================
        
        $messagesTemplates = [
            "Bonjour, je suis très intéressé(e) par votre œuvre. Pourrions-nous en discuter ?",
            "Bonjour ! Merci pour votre intérêt. Je serais ravi(e) d'en parler avec vous.",
            "Quelles sont vos conditions pour une collaboration ?",
            "Je propose un contrat avec une commission de {rate}%. Qu'en pensez-vous ?",
            "Les termes me semblent acceptables. Pouvez-vous m'envoyer une proposition formelle ?",
            "Parfait ! Je vais préparer le contrat.",
            "J'ai quelques questions sur les droits d'utilisation.",
            "Je vous propose une exclusivité sur 2 ans.",
            "Pourrions-nous organiser une rencontre ?",
            "Oui, avec plaisir. Quand êtes-vous disponible ?",
        ];

        foreach ($discussions as $discussion) {
            if ($discussion->getStatus() === 'archived') {
                continue; // Pas de messages pour les discussions archivées
            }

            $messageCount = rand(2, 8);
            $isPublisher = true;

            for ($i = 0; $i < $messageCount; $i++) {
                $message = new Message();
                $message->setDiscussion($discussion);
                $message->setSender($isPublisher ? $discussion->getPublisher() : $discussion->getArtist());
                
                $content = $messagesTemplates[$i % count($messagesTemplates)];
                $content = str_replace('{rate}', rand(15, 35), $content);
                $message->setContent($content);
                
                $message->setIsContractProposal($i > 2 && rand(0, 4) === 0);
                $message->markAsRead(rand(0, 1) === 1);
                $message->setSentAt(new \DateTimeImmutable('-' . (20 - $i * 2) . ' days'));
                
                $manager->persist($message);
                $isPublisher = !$isPublisher;
            }
        }

        $manager->flush();

        // ========================================
        // 5. CRÉATION DES CONTRATS
        // ========================================
        
        $contractsData = [
            [$discussions[1], 'draft', 20, 'now', '+1 year'],
            [$discussions[2], 'proposed', 25, '+5 days', '+1 year'],
            [$discussions[3], 'signed', 30, '+3 days', '+2 years'],
            [$discussions[5], 'active', 15, '-10 days', '+350 days'],
            [$discussions[7], 'active', 20, '-20 days', '+320 days'],
            [$discussions[8], 'signed', 35, '+2 days', '+6 months'],
            [$discussions[11], 'proposed', 25, '+7 days', '+18 months'],
            [$discussions[14], 'terminated', 30, '-200 days', '-5 days'],
            [$discussions[0], 'proposed', 18, '+10 days', '+1 year'],
            [$discussions[4], 'draft', 12, 'now', '+8 months'],
        ];

        foreach ($contractsData as $index => [$discussion, $status, $rate, $startDate, $endDate]) {
            $contract = new Contract();
            $contract->setDiscussion($discussion);
            $contract->setStatus($status);
            $contract->setCommissionRate($rate);
            
            $terms = "CONTRAT DE COLLABORATION ARTISTIQUE\n\n";
            $terms .= "Entre " . $discussion->getArtist()->getFullName() . " (l'Artiste)\n";
            $terms .= "Et " . $discussion->getPublisher()->getFullName() . " (le Publisher)\n\n";
            $terms .= "ARTICLE 1 - OBJET\n";
            $terms .= "Le présent contrat a pour objet la collaboration artistique concernant l'œuvre « " . $discussion->getProduct()->getTitle() . " ».\n\n";
            $terms .= "ARTICLE 2 - COMMISSION\n";
            $terms .= "Le Publisher percevra une commission de " . $rate . "% sur toutes les ventes.\n\n";
            $terms .= "ARTICLE 3 - DURÉE\n";
            $terms .= "Le présent contrat prend effet à la date de signature et reste valable jusqu'à la date de fin spécifiée.\n\n";
            $terms .= "ARTICLE 4 - DROITS ET OBLIGATIONS\n";
            $terms .= "L'Artiste conserve tous les droits moraux sur son œuvre.";
            
            $contract->setTerms($terms);
            
            if ($startDate) {
                $contract->setStartDate(new \DateTime($startDate));
            }
            if ($endDate) {
                $contract->setEndDate(new \DateTime($endDate));
            }
            
            // Si signé, actif ou terminé, définir la signature
            if (in_array($status, ['signed', 'active', 'terminated'])) {
                $contract->setSignedBy($discussion->getArtist());
                $contract->setSignedAt(new \DateTime('-' . (60 - $index * 5) . ' days'));
            }
            
            $manager->persist($contract);
        }

        $manager->flush();

        echo "\n✅ Fixtures chargées avec succès !\n";
        echo "📊 Statistiques :\n";
        echo "   - " . count($artistes) . " artistes\n";
        echo "   - " . count($publishers) . " publishers\n";
        echo "   - 1 administrateur\n";
        echo "   - " . count($products) . " produits\n";
        echo "   - " . count($discussions) . " discussions\n";
        echo "   - 10 contrats\n\n";
        echo "🔑 Comptes de test :\n";
        echo "   Artiste    : artist@artconnect.com / password\n";
        echo "   Publisher  : publisher@artconnect.com / password\n";
        echo "   Admin      : admin@artconnect.com / password\n\n";
    }
}
