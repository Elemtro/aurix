<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Keeps authenticated users inside enrollment or the voice step until done.
 */
#[AsEventListener(event: KernelEvents::REQUEST)]
final class VoiceAccessGuard
{
    private const ALLOWED_ROUTES = [
        'app_login',
        'app_logout',
        'app_register',
        'app_register_voice',
        'api_enroll',
        'app_login_voice',
        'api_login_voice',
    ];

    public function __construct(
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (!is_string($route) || str_starts_with($route, '_') || in_array($route, self::ALLOWED_ROUTES, true)) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        if (!$user->isEnrolled()) {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_register_voice')));

            return;
        }

        if (!$request->getSession()->get('voice_verified', false)) {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_login_voice')));
        }
    }
}
