<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Admin;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class JwtAuthenticationSuccessSubscriber implements EventSubscriberInterface
{
    /** @return array<string, string> */
    public static function getSubscribedEvents(): array
    {
        return [
            'lexik_jwt_authentication.on_authentication_success' => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $admin = $event->getUser();

        if (!$admin instanceof Admin) {
            return;
        }

        $data = $event->getData();
        $data['admin'] = [
            'id' => $admin->getId(),
            'firstName' => $admin->getFirstName(),
            'lastName' => $admin->getLastName(),
            'email' => $admin->getEmail(),
        ];

        $event->setData($data);
    }
}
