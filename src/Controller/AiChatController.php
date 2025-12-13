<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AiChatController extends AbstractController
{
    /**
     * Simple IA-like reply endpoint.
     * This is a placeholder implementation that returns a heuristic response.
     * Replace with a call to a real AI service if desired.
     */
    #[Route('/ai/chat', name: 'ai_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');

        if ($message === '') {
            return $this->json(['reply' => 'Je n\'ai pas reçu de message. Pose une question.']);
        }

        // Very simple heuristic replies
        $lower = mb_strtolower($message, 'UTF-8');

        if (str_contains($lower, 'bonjour') || str_contains($lower, 'salut')) {
            $reply = 'Bonjour ! Comment puis-je t\'aider pour créer ta communauté ?';
        } elseif (str_contains($lower, 'nom')) {
            $reply = 'Choisis un nom court, mémorable et qui reflète l\'identité (ex: "Collectif Lumière"). Veux-tu des propositions ?';
        } elseif (str_contains($lower, 'description') || str_contains($lower, 'présentation')) {
            $reply = 'Rédige une description en 2–3 phrases : mission, public visé, activités. Je peux te proposer un brouillon si tu veux.';
        } elseif (str_contains($lower, 'type') || str_contains($lower, 'membre')) {
            $reply = 'Définis les types principaux (artistes, organisateurs, galeries). Ça aidera au filtrage et aux invitations.';
        } else {
            // fallback: echo with suggestion
            $reply = 'Voici une suggestion basée sur ta question : "' . htmlspecialchars($message) . '". Souhaites-tu un exemple concret (oui/non) ?';
        }

        return $this->json(['reply' => $reply]);
    }
}
