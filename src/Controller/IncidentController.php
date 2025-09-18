<?php

namespace App\Controller;

use App\Document\Review;
use App\Form\IncidentType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/incident')]
#[IsGranted('ROLE_EMPLOYE')]
class IncidentController extends AbstractController
{
    #[Route('/new/{tripId}', name: 'incident_new')]
    public function new(int $tripId, Request $request, DocumentManager $dm): Response
    {
        $incident = new Review();
        $user = $this->getUser();

        $incident->setUserId((string)$user->getId());
        $incident->setUserEmail($user->getEmail());
        $incident->setTripId($tripId);
        $incident->setReviewerId((int)$user->getId());
        $incident->setIsIncident(true); // C'est bien un incident
        $incident->setEtat('en_attente');

        $form = $this->createForm(IncidentType::class, $incident);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dm->persist($incident);
            $dm->flush();

            $this->addFlash('success', 'Incident signalé avec succès !');
            return $this->redirectToRoute('app_incident');
        }

        return $this->render('security/incident_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
