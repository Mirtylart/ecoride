<?php

namespace App\Controller;

use App\Document\Review;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EMPLOYE')]
class EmployeController extends AbstractController
{
    #[Route('/employe', name: 'app_employe')]
    public function dashboard(DocumentManager $dm): Response
    {
        // Récupérer les reviews et incidents en attente
        $reviews = $dm->getRepository(Review::class)->findBy(['etat' => 'en_attente']);
        $incidents = $dm->getRepository(Review::class)->findBy([
            'etat' => 'en_attente',
            'isIncident' => true,
        ]);

        // Filtrer uniquement les reviews avec les champs obligatoires initialisés
        $reviews = array_filter($reviews, fn($r) => $r->getDriverId() && $r->getUserEmail());
        $incidents = array_filter($incidents, fn($r) => $r->getDriverId() && $r->getUserEmail());

        return $this->render('security/employe.html.twig', [
            'reviews' => $reviews,
            'incidents' => $incidents,
        ]);
    }

    #[Route('/employe/review/{id}/valider', name: 'app_review_valider')]
    public function validerReview(string $id, DocumentManager $dm): Response
    {
        $review = $dm->getRepository(Review::class)->find($id);
        if (!$review) {
            throw $this->createNotFoundException('Avis introuvable.');
        }

        $review->setEtat('valide');
        $dm->flush();

        $this->addFlash('success', 'Avis validé avec succès.');
        return $this->redirectToRoute('app_employe');
    }

    #[Route('/employe/review/{id}/refuser', name: 'app_review_refuser')]
    public function refuserReview(string $id, DocumentManager $dm): Response
    {
        $review = $dm->getRepository(Review::class)->find($id);
        if (!$review) {
            throw $this->createNotFoundException('Avis introuvable.');
        }

        $review->setEtat('refuse');
        $dm->flush();

        $this->addFlash('danger', 'Avis refusé.');
        return $this->redirectToRoute('app_employe');
    }

    #[Route('/employe/incident/{id}/traiter', name: 'app_incident_traiter')]
    public function traiterIncident(string $id, DocumentManager $dm): Response
    {
        $incident = $dm->getRepository(Review::class)->find($id);
        if (!$incident) {
            throw $this->createNotFoundException('Incident introuvable.');
        }

        $incident->setEtat('traite');
        $dm->flush();

        $this->addFlash('success', 'Incident marqué comme traité.');
        return $this->redirectToRoute('app_employe');
    }
}
