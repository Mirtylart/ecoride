<?php

namespace App\Controller\Api;

use App\Entity\Trip;
use App\Entity\Participation;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TripApiController extends AbstractController
{
    #[Route('/api/trips', name: 'api_trip_list', methods: ['GET'])]
    /**
     * @OA\Get(
     *     path="/api/trips",
     *     summary="Lister les trajets",
     *     @OA\Response(response=200, description="Liste des trajets")
     * )
     */
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $trips = $em->getRepository(Trip::class)->findAll();
        $data = [];

        foreach ($trips as $trip) {
            $data[] = [
                'id' => $trip->getId(),
                'depart' => $trip->getVilleDepart(),
                'arrivee' => $trip->getVilleArrivee(),
                'date' => $trip->getDateDepart()?->format('Y-m-d H:i'),
                'places_dispo' => $trip->getPlacesDispo(),
                'prix' => $trip->getPrix(),
                'driver' => $trip->getDriver()?->getEmail(),
                'status' => $trip->getStatus(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/trips/{id}/reserve', name: 'api_trip_reserve', methods: ['POST'])]
    /**
     * @OA\Post(
     *     path="/api/trips/{id}/reserve",
     *     summary="Réserver un trajet",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Réservation confirmée"),
     *     @OA\Response(response=400, description="Impossible de réserver")
     * )
     */
    public function reserve(Trip $trip, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if (!$user || $trip->getPlacesDispo() <= 0) {
            return $this->json(['error' => 'Impossible de réserver'], 400);
        }

        foreach ($trip->getParticipations() as $p) {
            if ($p->getUser() === $user) {
                return $this->json(['error' => 'Vous avez déjà réservé ce trajet'], 400);
            }
        }

        if ($user->getCredits() < $trip->getPrix()) {
            return $this->json(['error' => 'Crédits insuffisants'], 400);
        }

        $participation = new Participation();
        $participation->setTrip($trip);
        $participation->setUser($user);
        $participation->setDateParticipation(new \DateTime());
        $participation->setStatut('confirmé');

        $trip->setPlacesDispo($trip->getPlacesDispo() - 1);
        $user->removeCredits($trip->getPrix());
        $trip->getDriver()->addCredits($trip->getPrix());

        $em->persist($participation);
        $em->flush();

        return $this->json(['success' => 'Réservation confirmée']);
    }
}
