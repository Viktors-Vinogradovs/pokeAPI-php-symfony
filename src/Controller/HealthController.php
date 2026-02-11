<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/health', name: 'app_health', methods: ['GET'])]
    public function check(): Response
    {
        return new Response('OK', Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    }
}
