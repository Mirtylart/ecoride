<?php

namespace App\Controller\Api;

use App\Entity\Vehicule;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class VehiculeApiController extends AbstractController
{
    #[Route('/api/vehicules', name: 'api_vehicule_list', methods: ['GET'])]
    /**
     * @OA\Get(
     *     path="/api/vehicules",
     *     summary="Lister les véhicules de l'utilisateur",
     *     @OA\Response(response=200, description="Liste des véhicules")
     * )
     */
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $vehicules = $em->getRepository(Vehicule::class)->findBy(['user' => $user]);

        $data = [];
        foreach ($vehicules as $v) {
            $data[] = [
                'id' => $v->getId(),
                'marque' => $v->getMarque(),
                'modele' => $v->getModele(),
                'places' => $v->getPlaces(),
                'preferences' => $v->getPreferences(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/vehicules', name: 'api_vehicule_create', methods: ['POST'])]
    /**
     * @OA\Post(
     *     path="/api/vehicules",
     *     summary="Ajouter un véhicule pour l'utilisateur",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="marque", type="string"),
     *             @OA\Property(property="modele", type="string"),
     *             @OA\Property(property="places", type="integer"),
     *             @OA\Property(property="preferences", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Véhicule créé")
     * )
     */
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $vehicule = new Vehicule();
        $vehicule->setUser($user);
        $vehicule->setMarque($data['marque'] ?? '');
        $vehicule->setModele($data['modele'] ?? '');
        $vehicule->setPlaces($data['places'] ?? 0);
        $vehicule->setPreferences($data['preferences'] ?? '');

        $em->persist($vehicule);
        $em->flush();

        return $this->json(['id' => $vehicule->getId()], 201);
    }
}
