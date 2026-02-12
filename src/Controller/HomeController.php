<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(SessionInterface $session): Response
    {
        $favorites = $session->get('favorites', []);

        return $this->render('home/index.html.twig', [
            'favoritesCount' => count($favorites),
        ]);
    }
}
