<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact')]
    public function index(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $email = $request->request->get('email');
            $message = $request->request->get('message');

            $this->addFlash('success', 'Merci, votre message a bien été envoyé !');

            return $this->redirectToRoute('contact');
        }

        return $this->render('security/contact.html.twig');
    }
}
