<?php

namespace App\Controller;

use App\Entity\Listing;
use App\Entity\Photo;
use App\Security\Voter\ListingVoter;
use App\Security\Voter\PhotoVoter;
use App\Service\PhotoManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/api', name: 'app_photos_', format: 'json')]
final class PhotoController extends AbstractController
{
    private const GROUPS = ['listing:read'];

    /**
     * Uploads one photo (multipart/form-data, field "photo"). It goes to the end of the gallery.
     */
    #[Route('/listings/{id}/photos', name: 'create', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted(ListingVoter::EDIT, subject: 'listing')]
    public function create(
        Listing $listing,
        #[MapUploadedFile([
            new Assert\Image(
                maxSize: '5M',
                mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                mimeTypesMessage: 'Formats acceptés : JPEG, PNG ou WebP.',
                maxSizeMessage: 'L\'image ne doit pas dépasser {{ limit }} {{ suffix }}.',
            ),
        ], name: 'photo')]
        ?UploadedFile $photo,
        PhotoManager $photoManager,
    ): JsonResponse {
        // Nullable on purpose: without a file, #[MapUploadedFile] skips validation and gives null.
        if (null === $photo) {
            throw new UnprocessableEntityHttpException('Envoyez une image dans le champ "photo" (multipart/form-data).');
        }

        return $this->json($photoManager->add($listing, $photo), Response::HTTP_CREATED, context: ['groups' => self::GROUPS]);
    }

    #[Route('/photos/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted(PhotoVoter::EDIT, subject: 'photo')]
    public function delete(Photo $photo, PhotoManager $photoManager): Response
    {
        $photoManager->remove($photo);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * Makes this photo the cover (first of the gallery).
     */
    #[Route('/photos/{id}/cover', name: 'cover', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted(PhotoVoter::EDIT, subject: 'photo')]
    public function cover(Photo $photo, PhotoManager $photoManager): JsonResponse
    {
        $photoManager->makeCover($photo);

        return $this->json($photo->getListing()->getPhotoList(), context: ['groups' => self::GROUPS]);
    }
}
