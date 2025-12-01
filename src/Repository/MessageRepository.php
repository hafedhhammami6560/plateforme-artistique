<?php

namespace App\Repository;

use App\Entity\Discussion;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Récupère le dernier message de chaque discussion
     * 
     * @return Message[]
     */
    public function findLastMessages(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        // Sous-requête pour obtenir le dernier message de chaque discussion
        $sql = '
            SELECT m.*
            FROM message m
            INNER JOIN (
                SELECT discussion_id, MAX(sent_at) as max_sent_at
                FROM message
                GROUP BY discussion_id
            ) as latest ON m.discussion_id = latest.discussion_id 
                AND m.sent_at = latest.max_sent_at
            ORDER BY m.sent_at DESC
        ';

        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery();
        $results = $resultSet->fetchAllAssociative();

        // Convertir les résultats en entités
        $messages = [];
        foreach ($results as $row) {
            $message = $this->find($row['id']);
            if ($message) {
                $messages[] = $message;
            }
        }

        return $messages;
    }

    /**
     * Récupère les messages avec propositions de contrat
     * 
     * @return Message[]
     */
    public function findContractProposals(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.discussion', 'd')
            ->addSelect('d')
            ->leftJoin('m.sender', 's')
            ->addSelect('s')
            ->where('m.isContractProposal = :isProposal')
            ->setParameter('isProposal', true)
            ->orderBy('m.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère l'historique complet d'une discussion
     * 
     * @return Message[]
     */
    public function getDiscussionHistory(int $discussionId): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')
            ->addSelect('s')
            ->where('m.discussion = :discussionId')
            ->setParameter('discussionId', $discussionId)
            ->orderBy('m.sentAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les messages non lus d'un utilisateur
     * 
     * @return Message[]
     */
    public function findUnreadForUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.discussion', 'd')
            ->addSelect('d')
            ->leftJoin('m.sender', 's')
            ->addSelect('s')
            ->where('(d.artist = :user OR d.publisher = :user)')
            ->andWhere('m.sender != :user')
            ->andWhere('m.isRead = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->orderBy('m.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les messages non lus d'un utilisateur
     */
    public function countUnreadForUser(User $user): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->leftJoin('m.discussion', 'd')
            ->where('(d.artist = :user OR d.publisher = :user)')
            ->andWhere('m.sender != :user')
            ->andWhere('m.isRead = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les messages d'une discussion pour un utilisateur spécifique
     * 
     * @return Message[]
     */
    public function findByDiscussionAndUser(Discussion $discussion, User $user): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')
            ->addSelect('s')
            ->where('m.discussion = :discussion')
            ->andWhere('(m.discussion IN (
                SELECT d FROM App\Entity\Discussion d 
                WHERE d.artist = :user OR d.publisher = :user
            ))')
            ->setParameter('discussion', $discussion)
            ->setParameter('user', $user)
            ->orderBy('m.sentAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les messages récents
     * 
     * @return Message[]
     */
    public function findRecent(int $limit = 20): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.discussion', 'd')
            ->addSelect('d')
            ->leftJoin('m.sender', 's')
            ->addSelect('s')
            ->orderBy('m.sentAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de messages par contenu
     * 
     * @return Message[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.discussion', 'd')
            ->addSelect('d')
            ->leftJoin('m.sender', 's')
            ->addSelect('s')
            ->where('m.content LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('m.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Marque tous les messages d'une discussion comme lus pour un utilisateur
     */
    public function markDiscussionAsReadForUser(Discussion $discussion, User $user): void
    {
        $this->createQueryBuilder('m')
            ->update()
            ->set('m.isRead', ':isRead')
            ->where('m.discussion = :discussion')
            ->andWhere('m.sender != :user')
            ->andWhere('m.isRead = :notRead')
            ->setParameter('isRead', true)
            ->setParameter('notRead', false)
            ->setParameter('discussion', $discussion)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Compte les messages par discussion
     */
    public function countByDiscussion(Discussion $discussion): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.discussion = :discussion')
            ->setParameter('discussion', $discussion)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les statistiques globales des messages
     */
    public function getGlobalStats(): array
    {
        $totalMessages = $this->count([]);
        $contractProposals = $this->count(['isContractProposal' => true]);
        $unreadMessages = $this->count(['isRead' => false]);

        return [
            'total' => $totalMessages,
            'contractProposals' => $contractProposals,
            'unread' => $unreadMessages,
        ];
    }

    /**
     * Récupère les messages envoyés par un utilisateur
     * 
     * @return Message[]
     */
    public function findBySender(User $sender, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.discussion', 'd')
            ->addSelect('d')
            ->where('m.sender = :sender')
            ->setParameter('sender', $sender)
            ->orderBy('m.sentAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
