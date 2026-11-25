<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class AuthenticationSuccessListener
{
    public function __construct(
        private readonly NormalizerInterface $normalizer,
        private readonly ParameterBagInterface $params,
    ) {
    }

    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        $data['ttl'] = $this->params->get('lexik_jwt_authentication.token_ttl');
        $data['user'] = $this->normalizer->normalize($user, null, ['groups' => ['user:read']]);

        $event->setData($data);
    }
}
