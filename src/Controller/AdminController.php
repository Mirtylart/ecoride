<?php

namespace App\Controller;

use App\Document\Review;
use App\Entity\User;
use App\Entity\Trip;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(EntityManagerInterface $em, DocumentManager $dm): Response
    {
        $employes = $em->getRepository(User::class)->findAll();
        $reviews = $dm->getRepository(Review::class)->findBy(['etat' => 'en_attente']);
        $incidents = $dm->getRepository(Review::class)->findBy([
            'etat' => 'en_attente',
            'isIncident' => true,
        ]);

        $conn = $em->getConnection();
        $sql = "SELECT MONTH(date_depart) as mois, COUNT(*) as total FROM trip GROUP BY mois";
        $stmt = $conn->prepare($sql);
        $trips = $stmt->executeQuery()->fetchAllAssociative();

        return $this->render('security/admin.html.twig', [
            'employes' => $employes,
            'reviews' => $reviews,
            'incidents' => $incidents,
            'trips' => $trips,
        ]);
    }

    #[Route('/admin/suspendre/{id}', name: 'admin_suspendre')]
    public function suspendre(int $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $user->setIsSuspended(true);
        $em->flush();

        $this->addFlash('success', 'Compte suspendu avec succès !');
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/reactiver/{id}', name: 'admin_reactiver')]
    public function reactiver(int $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $user->setIsSuspended(false);
        $em->flush();

        $this->addFlash('success', 'Compte réactivé avec succès !');
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/role/employe/{id}', name: 'admin_role_employe')]
    public function assignEmployeRole(int $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $roles = $user->getRoles();
        if (!in_array('ROLE_EMPLOYE', $roles, true)) {
            $roles[] = 'ROLE_EMPLOYE';
            $user->setRoles(array_unique($roles));
            $em->flush();
            $this->addFlash('success', 'Utilisateur promu en employé avec succès !');
        } else {
            $this->addFlash('info', 'Cet utilisateur est déjà employé.');
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/role/remove-employe/{id}', name: 'admin_remove_role_employe')]
    public function removeEmployeRole(int $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $roles = $user->getRoles();
        if (in_array('ROLE_EMPLOYE', $roles, true)) {
            $roles = array_filter($roles, fn($role) => $role !== 'ROLE_EMPLOYE');
            $user->setRoles(array_values($roles));
            $em->flush();
            $this->addFlash('success', 'Le rôle employé a été retiré à cet utilisateur.');
        } else {
            $this->addFlash('info', 'Cet utilisateur n\'est pas employé.');
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/review/valider/{id}', name: 'admin_valider_review')]
    public function validerReview(string $id, DocumentManager $dm): Response
    {
        $review = $dm->getRepository(Review::class)->find($id);

        if (!$review) {
            throw $this->createNotFoundException('Review introuvable.');
        }

        $review->setEtat('valide');
        $dm->flush();

        $this->addFlash('success', 'Review validée avec succès !');
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/review/supprimer/{id}', name: 'admin_supprimer_review')]
    public function supprimerReview(string $id, DocumentManager $dm): Response
    {
        $review = $dm->getRepository(Review::class)->find($id);

        if (!$review) {
            throw $this->createNotFoundException('Review introuvable.');
        }

        $dm->remove($review);
        $dm->flush();

        $this->addFlash('success', 'Review supprimée avec succès !');
        return $this->redirectToRoute('admin_dashboard');
    }
}
