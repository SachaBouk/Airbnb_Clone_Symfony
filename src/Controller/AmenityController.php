<?php

namespace App\Controller;

use App\Repository\AmenityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/amenities', name: 'app_amenities_', format: 'json')]
final class AmenityController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(AmenityRepository $amenityRepository): JsonResponse
    {
        return $this->json($amenityRepository->findBy([], ['name' => 'ASC']), context: ['groups' => ['amenity:read']]);
    }
}
