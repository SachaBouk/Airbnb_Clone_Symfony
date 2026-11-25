<?php

namespace App\Controller;

use App\Entity\User;
use App\Model\PaginationDTO;
use App\Model\Paginator;
use App\Repository\UserRepository;
use App\Service\FollowManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api', format: 'json')]
final class UserController extends AbstractController
{
    private const LIST_GROUPS = ['paginate', 'user:public'];

    /**
     * Returns the user identified by the JWT sent in "Authorization: Bearer <token>".
     */
    #[Route('/user', name: 'app_user_example', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function user(#[CurrentUser] ?User $user): JsonResponse
    {
        return $this->json($user, context: ['groups' => ['user:read']]);
    }

    /**
     * The logged-in user starts following user {id}.
     */
    #[Route('/users/{id}/follow', name: 'app_users_follow', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function follow(User $followed, #[CurrentUser] User $user, FollowManager $followManager): Response
    {
        $followManager->follow($user, $followed);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * The logged-in user stops following user {id}.
     */
    #[Route('/users/{id}/follow', name: 'app_users_unfollow', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function unfollow(User $followed, #[CurrentUser] User $user, FollowManager $followManager): Response
    {
        $followManager->unfollow($user, $followed);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * Paginated list of the users who follow user {id}.
     */
    #[Route('/users/{id}/followers', name: 'app_users_followers', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function followers(
        User $user,
        UserRepository $userRepository,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        PaginationDTO $pagination = new PaginationDTO(),
    ): JsonResponse {
        $page = (new Paginator())->paginate($userRepository->followersQuery($user), $pagination);

        return $this->json($page, context: ['groups' => self::LIST_GROUPS]);
    }

    /**
     * Paginated list of the users that user {id} follows.
     */
    #[Route('/users/{id}/following', name: 'app_users_following', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function following(
        User $user,
        UserRepository $userRepository,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        PaginationDTO $pagination = new PaginationDTO(),
    ): JsonResponse {
        $page = (new Paginator())->paginate($userRepository->followingQuery($user), $pagination);

        return $this->json($page, context: ['groups' => self::LIST_GROUPS]);
    }
}
