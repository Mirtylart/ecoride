<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserApiController extends AbstractController
{
    #[Route('/api/profile', name: 'api_user_profile', methods: ['GET'])]
    /**
     * @OA\Get(
     *     path="/api/profile",
     *     summary="Récupérer le profil de l'utilisateur connecté",
     *     @OA\Response(
     *         response=200,
     *         description="Profil de l'utilisateur",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function profile(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/api/profile', name: 'api_user_update', methods: ['PUT'])]
    /**
     * @OA\Put(
     *     path="/api/profile",
     *     summary="Mettre à jour le profil de l'utilisateur connecté",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="email", type="string", example="newemail@ecoride.fr"),
     *             @OA\Property(property="password", type="string", example="nouveaumdp123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Profil mis à jour"),
     *     @OA\Response(response=400, description="Données invalides")
     * )
     */
    public function update(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (!empty($data['password'])) {
            $user->setPassword($hasher->hashPassword($user, $data['password']));
        }

        $em->persist($user);
        $em->flush();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }
}
