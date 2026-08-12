<?php

namespace App\EventListener;

use App\Entity\ActionLog;
use App\Entity\User;
use App\Security\RequiresPermission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * The single place every route's access requirement is enforced and
 * logged — see App\Security\RequiresPermission for the three states a
 * route can declare. Runs on kernel.controller, after routing resolves
 * which action will handle the request but before it executes.
 */
#[AsEventListener(event: KernelEvents::CONTROLLER)]
class PermissionCheckListener
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $em,
    ) {}

    public function __invoke(ControllerEvent $event): void
    {
        $controller = $event->getController();
        if (!is_array($controller)) {
            return;
        }
        [$controllerObject, $methodName] = $controller;

        $reflection = new \ReflectionMethod($controllerObject, $methodName);
        $attributes = $reflection->getAttributes(RequiresPermission::class);
        if (empty($attributes)) {
            return;
        }

        /** @var RequiresPermission $requirement */
        $requirement = $attributes[0]->newInstance();

        /** @var User|null $user */
        $user = $this->security->getUser();

        $granted = $requirement->public
            || ($user !== null && ($requirement->permission === null
                || $this->security->isGranted('ROLE_' . strtoupper($requirement->permission))));

        $log = new ActionLog();
        $log->setUser($user);
        $log->setPermission($requirement->public ? null : $requirement->permission);
        $log->setPath($event->getRequest()->getPathInfo());
        $log->setMethod($event->getRequest()->getMethod());
        $log->setGranted($granted);
        $this->em->persist($log);
        $this->em->flush();

        if (!$granted) {
            throw new AccessDeniedHttpException($user === null ? 'Authentication required.' : 'Missing permission.');
        }
    }
}
