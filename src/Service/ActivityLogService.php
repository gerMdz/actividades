<?php

namespace App\Service;

use App\Entity\Activity;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class ActivityLogService
{
    private EntityManagerInterface $em;
    private Security $security;
    private RequestStack $requestStack;

    public function __construct(EntityManagerInterface $entityManager, Security $security, RequestStack $requestStack)
    {
        $this->em = $entityManager;
        $this->security = $security;
        $this->requestStack = $requestStack;
    }

    public function log(string $entityType, string $entityId, string $extent, array $eventData): void
    {
        $user = $this->security->getUser();
        $request = $this->requestStack->getCurrentRequest();
        $log = new Activity();
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);
        $log->setExtent($extent);
        $log->setEventData($eventData);
        $log->setUser($user);
        $log->setRequestRoute($request->get('_route'));
        $log->setIpAddress($request->getClientIp());
        $log->setCreatedAt(new DateTimeImmutable);
        $this->em->persist($log);
        $this->em->flush();
    }
}