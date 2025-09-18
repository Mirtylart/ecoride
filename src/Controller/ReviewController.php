<?php
namespace App\Controller;

use App\Document\Review;
use App\Form\ReviewType;
use App\Form\IncidentType;
use App\Entity\Trip;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReviewController extends AbstractController
{
    #[Route('/reviews', name: 'app_review')]
    #[IsGranted('ROLE_EMPLOYE')]
    public function index(DocumentManager $dm): Response
    {
        $reviews = $dm->getRepository(Review::class)->findAll();
        $reviewsIncident = $dm->getRepository(Review::class)->findBy(['isIncident' => true]);

        return $this->render('security/employe.html.twig', [
            'reviews'   => $reviews,
            'incidents' => $reviewsIncident,
        ]);
    }

    #[Route('/review/new/{tripId}', name: 'review_new')]
    public function new(
        int $tripId,
        Request $request,
        DocumentManager $dm,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Utilisateur non connecté');
        }

        $trip = $em->getRepository(Trip::class)->find($tripId);
        if (!$trip) {
            throw $this->createNotFoundException('Trajet introuvable');
        }

        $driver = $trip->getDriver();
        if (!$driver) {
            throw new \LogicException('Le trajet n’a pas de chauffeur associé.');
        }

        $review = new Review();
        $review->setUserId((string) $user->getId())
               ->setUserEmail($user->getEmail())
               ->setTripId($tripId)
               ->setReviewerId((int) $user->getId())
               ->setDriverId($driver->getId());

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        $incident = new Review();
        $incident->setUserId((string) $user->getId())
                 ->setUserEmail($user->getEmail())
                 ->setTripId($tripId)
                 ->setReviewerId((int) $user->getId())
                 ->setDriverId($driver->getId())
                 ->setIsIncident(true);

        $incidentForm = $this->createForm(IncidentType::class, $incident);
        $incidentForm->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $review->setIsIncident($form->get('isIncident')->getData() ?? false);
            $dm->persist($review);
            $dm->flush();

            $this->addFlash('success', 'Votre avis a été envoyé !');
            return $this->redirectToRoute('app_home');
        }

        if ($incidentForm->isSubmitted() && $incidentForm->isValid()) {
            $dm->persist($incident);
            $dm->flush();

            $this->addFlash('success', 'Incident signalé avec succès !');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/review.html.twig', [
            'form'         => $form->createView(),
            'incidentForm' => $incidentForm->createView(),
        ]);
    }
}
