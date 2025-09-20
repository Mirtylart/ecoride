<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

class RegistrationApiController extends AbstractController
{
    #[Route('/api/registration', name: 'api_registration', methods: ['POST'])]
    #[OA\Post(
        path: '/api/registration',
        summary: 'Inscription d\'un nouvel utilisateur (API)',
        tags: ['Registration'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Données d\'inscription',
            content: new OA\JsonContent(
                type: 'object',
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'utilisateur@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'MotDePasse123')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Utilisateur inscrit avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'user', type: 'string', example: 'utilisateur@example.com'),
                        new OA\Property(property: 'username', type: 'string', example: 'user_123456'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string', example: 'ROLE_USER')),
                        new OA\Property(property: 'message', type: 'string', example: 'Inscription réussie, veuillez confirmer votre email.')
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Requête invalide',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Email et mot de passe requis')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Erreur serveur',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Erreur lors de la création de l\'utilisateur')
                    ]
                )
            )
        ]
    )]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['email'], $data['password'])) {
                return new JsonResponse(['error' => 'Email et mot de passe requis'], 400);
            }

            $user = new User();
            $user->setEmail($data['email']);
            
            // Générer un username unique automatiquement
            $username = $this->generateUniqueUsername($data['email'], $entityManager);
            $user->setUsername($username);
            
            $user->setPassword(
                $passwordHasher->hashPassword($user, $data['password'])
            );
            $user->setRoles(['ROLE_USER']);

            $entityManager->persist($user);
            $entityManager->flush();

            return new JsonResponse([
                'user' => $user->getEmail(),
                'username' => $user->getUsername(),
                'roles' => $user->getRoles(),
                'message' => 'Inscription réussie, veuillez confirmer votre email.'
            ], 201);

        } catch (\Exception $e) {
            // Log l'erreur pour debug
            error_log('Erreur inscription API: ' . $e->getMessage());
            
            return new JsonResponse([
                'error' => 'Erreur lors de la création de l\'utilisateur'
            ], 500);
        }
    }

    /**
     * Génère un username unique basé sur l'email
     */
    private function generateUniqueUsername(string $email, EntityManagerInterface $entityManager): string
    {
        $baseUsername = explode('@', $email)[0]; // Prend la partie avant @
        $baseUsername = preg_replace('/[^a-zA-Z0-9_]/', '_', $baseUsername); // Nettoie le username
        $baseUsername = substr($baseUsername, 0, 40); // Limite la longueur
        
        $username = $baseUsername;
        $suffix = 1;
        
        // Vérifie si le username existe déjà
        while ($entityManager->getRepository(User::class)->findOneBy(['username' => $username])) {
            $username = $baseUsername . '_' . $suffix;
            $suffix++;
            
            if ($suffix > 100) {
                // Fallback: timestamp random
                $username = 'user_' . time() . '_' . rand(1000, 9999);
                break;
            }
        }
        
        return $username;
    }
}