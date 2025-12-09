<?php

namespace App\Service;

use App\Entity\Contrat;
use App\Entity\Signature;
use App\Entity\User;
use App\Repository\SignatureRepository;
use App\ValueObject\Certificate;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Service de gestion des signatures électroniques sécurisées
 */
class ElectronicSignatureService
{
    private const TOKEN_LENGTH = 64;
    private const SIGNATURE_VALIDITY_YEARS = 10;
    private const CERTIFICATE_ISSUER = 'Plateforme Artistique CA';

    public function __construct(
        private EntityManagerInterface $em,
        private SignatureRepository $signatureRepository,
        private LoggerInterface $logger,
        private MailerInterface $mailer
    ) {}

    /**
     * Génère un token de signature sécurisé pour un utilisateur et un contrat
     */
    public function generateSignatureToken(User $user, Contrat $contract): string
    {
        // Générer un token unique et sécurisé
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        
        // Ajouter des données supplémentaires pour l'unicité
        $data = sprintf(
            '%s|%s|%s|%s',
            $token,
            $user->getId(),
            $contract->getId(),
            (new \DateTimeImmutable())->format('Y-m-d H:i:s')
        );
        
        // Hasher le token
        $hashedToken = hash('sha256', $data);
        
        $this->logger->info('Token de signature généré', [
            'user_id' => $user->getId(),
            'contract_id' => $contract->getId(),
            'token' => substr($hashedToken, 0, 10) . '...'
        ]);
        
        return $hashedToken;
    }

    /**
     * Valide un token de signature
     */
    public function validateSignatureToken(string $token): bool
    {
        // Vérifier que le token existe dans la base
        $signature = $this->signatureRepository->findByToken($token);
        
        if (!$signature) {
            $this->logger->warning('Token de signature invalide ou inexistant', [
                'token' => substr($token, 0, 10) . '...'
            ]);
            return false;
        }
        
        // Vérifier que la signature n'est pas expirée
        if ($signature->isExpired()) {
            $this->logger->warning('Token de signature expiré', [
                'signature_id' => $signature->getId(),
                'expired_at' => $signature->getExpiresAt()->format('Y-m-d H:i:s')
            ]);
            return false;
        }
        
        // Vérifier que la signature n'est pas révoquée
        if ($signature->isRevoked()) {
            $this->logger->warning('Token de signature révoqué', [
                'signature_id' => $signature->getId(),
                'revocation_reason' => $signature->getRevocationReason()
            ]);
            return false;
        }
        
        return true;
    }

    /**
     * Signe un contrat avec une signature électronique sécurisée
     */
    public function signContract(Contrat $contract, User $user, string $ipAddress, ?string $userAgent = null): Signature
    {
        // Vérifier que l'utilisateur n'a pas déjà signé
        $existingSignature = $this->signatureRepository->findByContratAndUser($contract, $user);
        if ($existingSignature) {
            throw new \RuntimeException('L\'utilisateur a déjà signé ce contrat.');
        }
        
        // Vérifier que le contrat est dans un état signable
        if (!in_array($contract->getStatut(), [Contrat::STATUT_FINAL, Contrat::STATUT_EN_ATTENTE_SIGNATURE])) {
            throw new \RuntimeException('Le contrat n\'est pas dans un état signable.');
        }
        
        // Créer la signature
        $signature = new Signature();
        $signature->setContrat($contract);
        $signature->setSignataire($user);
        $signature->setIpAddress($ipAddress);
        $signature->setUserAgent($userAgent);
        $signature->setSignedAt(new \DateTimeImmutable());
        
        // Générer le token
        $token = $this->generateSignatureToken($user, $contract);
        $signature->setSignatureToken($token);
        
        // Générer le hash de signature (inclut les données du contrat + utilisateur + timestamp)
        $signatureData = $this->generateSignatureData($contract, $user);
        $signatureHash = $this->hashSignatureData($signatureData);
        $signature->setSignatureHash($signatureHash);
        
        // Métadonnées
        $signature->setMetadata([
            'contract_version' => $contract->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'contract_hash' => hash('sha256', $contract->getConditionsTexte()),
            'user_email' => $user->getEmail(),
            'signing_method' => 'electronic',
            'platform' => 'Plateforme Artistique',
        ]);
        
        // Persister
        $this->em->persist($signature);
        
        // Mettre à jour le contrat
        $this->updateContractSignatureStatus($contract, $user);
        
        $this->em->flush();
        
        $this->logger->info('Contrat signé électroniquement', [
            'contract_id' => $contract->getId(),
            'user_id' => $user->getId(),
            'signature_id' => $signature->getId(),
            'ip_address' => $ipAddress
        ]);
        
        return $signature;
    }

    /**
     * Vérifie l'intégrité d'une signature
     */
    public function verifySignatureIntegrity(Signature $signature): bool
    {
        try {
            // Reconstruire les données de signature
            $signatureData = $this->generateSignatureData(
                $signature->getContrat(),
                $signature->getSignataire()
            );
            
            // Re-hasher et comparer
            $expectedHash = $this->hashSignatureData($signatureData);
            $actualHash = $signature->getSignatureHash();
            
            $isValid = hash_equals($expectedHash, $actualHash);
            
            if (!$isValid) {
                $this->logger->error('Intégrité de signature compromise', [
                    'signature_id' => $signature->getId(),
                    'contract_id' => $signature->getContrat()->getId()
                ]);
            }
            
            return $isValid;
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la vérification d\'intégrité', [
                'signature_id' => $signature->getId(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Génère un certificat de signature pour un contrat
     */
    public function generateSignatureCertificate(Contrat $contract): Certificate
    {
        // Vérifier que le contrat est entièrement signé
        if ($contract->getStatut() !== Contrat::STATUT_SIGNE) {
            throw new \RuntimeException('Le contrat doit être entièrement signé pour générer un certificat.');
        }
        
        $signatures = $this->signatureRepository->findByContrat($contract);
        
        // Numéro de certificat unique
        $certificateNumber = sprintf(
            'CERT-%s-%s',
            strtoupper(substr($contract->getNumeroContrat(), 0, 10)),
            strtoupper(substr(bin2hex(random_bytes(4)), 0, 8))
        );
        
        // Générer une paire de clés (simulation - en production utiliser OpenSSL)
        $publicKey = base64_encode(random_bytes(32));
        
        // Créer les données du certificat
        $certificateData = [
            'contract_id' => $contract->getId(),
            'contract_number' => $contract->getNumeroContrat(),
            'signatures' => array_map(fn($s) => [
                'signatory' => $s->getSignataire()->getName(),
                'signed_at' => $s->getSignedAt()->format('Y-m-d H:i:s'),
                'signature_hash' => $s->getSignatureHash(),
            ], $signatures),
            'issued_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        
        // Signer le certificat
        $certificateSignature = hash('sha256', json_encode($certificateData) . $publicKey);
        
        $issuedAt = new \DateTimeImmutable();
        $expiresAt = $issuedAt->modify('+' . self::SIGNATURE_VALIDITY_YEARS . ' years');
        
        $certificate = new Certificate(
            certificateNumber: $certificateNumber,
            issuer: self::CERTIFICATE_ISSUER,
            subject: sprintf('Contrat %s - %s', $contract->getNumeroContrat(), $contract->getType()),
            issuedAt: $issuedAt,
            expiresAt: $expiresAt,
            publicKey: $publicKey,
            signature: $certificateSignature,
            metadata: [
                'contract_type' => $contract->getType(),
                'contract_price' => $contract->getPrix(),
                'parties' => [
                    'artist' => $contract->getArtiste()->getName(),
                    'client' => $contract->getProducteur()->getName(),
                ],
                'signatures_count' => count($signatures),
            ]
        );
        
        // Stocker le certificat dans les signatures
        foreach ($signatures as $signature) {
            $signature->setCertificateData($certificate->toJson());
        }
        
        $this->em->flush();
        
        $this->logger->info('Certificat de signature généré', [
            'contract_id' => $contract->getId(),
            'certificate_number' => $certificateNumber
        ]);
        
        return $certificate;
    }

    /**
     * Révoque une signature
     */
    public function revokeSignature(Signature $signature, string $reason): void
    {
        if ($signature->isRevoked()) {
            throw new \RuntimeException('Cette signature est déjà révoquée.');
        }
        
        $signature->setStatus(Signature::STATUS_REVOKED);
        $signature->setRevocationReason($reason);
        $signature->setRevokedAt(new \DateTimeImmutable());
        
        // Mettre à jour le statut du contrat si nécessaire
        $contract = $signature->getContrat();
        if ($contract->getStatut() === Contrat::STATUT_SIGNE) {
            $contract->setStatut(Contrat::STATUT_EN_ATTENTE_SIGNATURE);
        }
        
        $this->em->flush();
        
        $this->logger->warning('Signature révoquée', [
            'signature_id' => $signature->getId(),
            'contract_id' => $contract->getId(),
            'reason' => $reason
        ]);
    }

    /**
     * Récupère l'historique des signatures d'un contrat
     */
    public function getSignatureHistory(Contrat $contract): array
    {
        $signatures = $this->signatureRepository->findByContrat($contract);
        
        return array_map(function(Signature $signature) {
            return [
                'id' => $signature->getId(),
                'signatory' => [
                    'id' => $signature->getSignataire()->getId(),
                    'name' => $signature->getSignataire()->getName(),
                    'email' => $signature->getSignataire()->getEmail(),
                ],
                'signed_at' => $signature->getSignedAt()?->format('Y-m-d H:i:s'),
                'ip_address' => $signature->getIpAddress(),
                'status' => $signature->getStatus(),
                'is_valid' => $signature->isValid(),
                'expires_at' => $signature->getExpiresAt()?->format('Y-m-d H:i:s'),
                'revoked_at' => $signature->getRevokedAt()?->format('Y-m-d H:i:s'),
                'revocation_reason' => $signature->getRevocationReason(),
                'metadata' => $signature->getMetadata(),
            ];
        }, $signatures);
    }

    /**
     * Envoie une demande de signature par email
     */
    public function sendSignatureRequest(Contrat $contract, User $user): bool
    {
        try {
            // Générer un lien de signature
            $signatureLink = sprintf(
                'https://plateforme-artistique.local/contrat/%d/signer',
                $contract->getId()
            );
            
            $email = (new Email())
                ->from('noreply@plateforme-artistique.com')
                ->to($user->getEmail())
                ->subject('Demande de signature électronique - Contrat ' . $contract->getNumeroContrat())
                ->html($this->generateSignatureRequestEmail($contract, $user, $signatureLink));
            
            $this->mailer->send($email);
            
            $this->logger->info('Demande de signature envoyée', [
                'contract_id' => $contract->getId(),
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de la demande de signature', [
                'contract_id' => $contract->getId(),
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Vérifie l'expiration des signatures d'un contrat
     */
    public function checkSignatureExpiration(Contrat $contract): bool
    {
        $signatures = $this->signatureRepository->findByContrat($contract);
        
        foreach ($signatures as $signature) {
            if ($signature->isExpired()) {
                $this->logger->warning('Signature expirée détectée', [
                    'signature_id' => $signature->getId(),
                    'contract_id' => $contract->getId(),
                    'expired_at' => $signature->getExpiresAt()->format('Y-m-d H:i:s')
                ]);
                
                $signature->setStatus(Signature::STATUS_EXPIRED);
            }
        }
        
        $this->em->flush();
        
        // Retourne true si au moins une signature est expirée
        return count(array_filter($signatures, fn($s) => $s->isExpired())) > 0;
    }

    /**
     * Génère les données de signature
     */
    private function generateSignatureData(Contrat $contract, User $user): array
    {
        return [
            'contract_id' => $contract->getId(),
            'contract_number' => $contract->getNumeroContrat(),
            'contract_type' => $contract->getType(),
            'contract_price' => $contract->getPrix(),
            'contract_conditions' => $contract->getConditionsTexte(),
            'contract_dates' => [
                'start' => $contract->getDateDebut()?->format('Y-m-d'),
                'end' => $contract->getDateFin()?->format('Y-m-d'),
            ],
            'signatory_id' => $user->getId(),
            'signatory_email' => $user->getEmail(),
            'signatory_name' => $user->getName(),
            'parties' => [
                'artist' => $contract->getArtiste()->getId(),
                'client' => $contract->getProducteur()->getId(),
            ],
        ];
    }

    /**
     * Hash les données de signature
     */
    private function hashSignatureData(array $data): string
    {
        $serialized = json_encode($data, JSON_UNESCAPED_UNICODE);
        return hash('sha512', $serialized);
    }

    /**
     * Met à jour le statut de signature du contrat
     */
    private function updateContractSignatureStatus(Contrat $contract, User $user): void
    {
        $isArtist = $contract->getArtiste()->getId() === $user->getId();
        
        if ($isArtist) {
            $contract->setSignatureArtist(true);
            $contract->setDateSignatureArtist(new \DateTimeImmutable());
        } else {
            $contract->setSignatureClient(true);
            $contract->setDateSignatureClient(new \DateTimeImmutable());
        }
        
        // Si les deux ont signé
        if ($contract->getSignatureArtist() && $contract->getSignatureClient()) {
            $contract->setStatut(Contrat::STATUT_SIGNE);
            $contract->setDateSignature(new \DateTimeImmutable());
        } else {
            $contract->setStatut(Contrat::STATUT_EN_ATTENTE_SIGNATURE);
        }
    }

    /**
     * Génère le contenu HTML de l'email de demande de signature
     */
    private function generateSignatureRequestEmail(Contrat $contract, User $user, string $signatureLink): string
    {
        return sprintf('
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #05468b; color: white; padding: 20px; text-align: center; }
                    .content { padding: 30px; background: #f9f9f9; }
                    .button { display: inline-block; padding: 12px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                    .contract-info { background: white; padding: 15px; border-left: 4px solid #05468b; margin: 20px 0; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>🔏 Demande de Signature Électronique</h1>
                    </div>
                    <div class="content">
                        <p>Bonjour <strong>%s</strong>,</p>
                        
                        <p>Vous êtes invité(e) à signer électroniquement le contrat suivant :</p>
                        
                        <div class="contract-info">
                            <strong>📄 Contrat N° :</strong> %s<br>
                            <strong>💰 Montant :</strong> %s €<br>
                            <strong>📅 Période :</strong> du %s au %s<br>
                            <strong>⚙️ Type :</strong> %s
                        </div>
                        
                        <p>Pour signer ce contrat de manière sécurisée, cliquez sur le bouton ci-dessous :</p>
                        
                        <div style="text-align: center;">
                            <a href="%s" class="button">✍️ Signer le Contrat</a>
                        </div>
                        
                        <p><small>⚠️ Ce lien de signature est personnel et ne doit pas être partagé.</small></p>
                        
                        <p>Cordialement,<br><strong>L\'équipe Plateforme Artistique</strong></p>
                    </div>
                    <div class="footer">
                        <p>Cet email a été généré automatiquement. Merci de ne pas y répondre.</p>
                        <p>© %d Plateforme Artistique - Tous droits réservés</p>
                    </div>
                </div>
            </body>
            </html>
        ',
            $user->getName(),
            $contract->getNumeroContrat(),
            number_format($contract->getPrix(), 2, ',', ' '),
            $contract->getDateDebut()->format('d/m/Y'),
            $contract->getDateFin()->format('d/m/Y'),
            $contract->getType() === 'publication_rights' ? 'Publication Rights' : 'Custom Order',
            $signatureLink,
            date('Y')
        );
    }
}
