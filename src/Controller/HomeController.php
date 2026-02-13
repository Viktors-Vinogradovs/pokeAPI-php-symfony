<?php

namespace App\Controller;

use App\Repository\FavoriteRepository;
use App\Service\ClientIdResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(Request $request, ClientIdResolver $clientIdResolver, FavoriteRepository $favoriteRepo): Response
    {
        $clientId = $clientIdResolver->getClientId($request);
        $favoritesCount = $favoriteRepo->countByClientId($clientId);

        $response = $this->render('home/index.html.twig', [
            'favoritesCount' => $favoritesCount,
        ]);

        if (!$clientIdResolver->hasClientIdCookie($request)) {
            $clientIdResolver->ensureClientIdCookie($response, $clientId);
        }

        return $response;
    }
}
