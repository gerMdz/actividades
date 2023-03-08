<?php

namespace App\EventSubscriber;

use App\Entity\Activity;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

class AuditSubscriber implements \Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface
{
    private ActivityLogService $activityLogService;
    private SerializerInterface $serializer;
    private array $removals;


    /**
     * @param ActivityLogService $activityLogService
     * @param SerializerInterface $serializer
     * @param array $removals
     */
    public function __construct(ActivityLogService $activityLogService, SerializerInterface $serializer, array $removals)
    {
        $this->activityLogService = $activityLogService;
        $this->serializer = $serializer;
        $this->removals = $removals;
    }

    /**
     * @inheritDoc
     */
    public function getSubscribedEvents(): array
    {
        return [
            'postPersist',
            'postUpdate',
            'preRemove',
            'postRemove',
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();
        $this->log($entity, 'insert', $entityManager);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();
        $this->log($entity, 'update', $entityManager);
    }

    /**
     * @throws ExceptionInterface
     */
    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $this->removals[] = $this->serializer->normalize($entity);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();
        $this->log($entity, 'delete', $entityManager);
    }

    /**
     * @throws ExceptionInterface
     */
    private function log($entity, string $action, EntityManagerInterface $em): void
    {
        $entityClass = get_class($entity);
        // If the class is AuditLog entity, ignore. We don't want to audit our own audit logs!
        if ($entityClass === 'App\Entity\Activity') {
            return;
        }
        $entityId = $entity->getId();
        $entityType = str_replace('App\Entity\\', '', $entityClass);
        // The Doctrine unit of work keeps track of all changes made to entities.
        $uow = $em->getUnitOfWork();
        if ($action === 'delete') {
            // For deletions, we get our entity from the temporary array.
            $entityData = array_pop($this->removals);
            $entityId = $entityData['id'];
        } elseif ($action === 'insert') {
            // For insertions, we convert the entity to an array.
            $entityData = $this->serializer->normalize($entity);
        } else {
            // For updates, we get the change set from Doctrine's Unit of Work manager.
            // This gives an array which contains only the fields which have
            // changed. We then just convert the numerical indexes to something
            // a bit more readable; "from" and "to" keys for the old and new values.
            $entityData = $uow->getEntityChangeSet($entity);
            foreach ($entityData as $field => $change) {
                $entityData[$field] = [
                    'from' => $change[0],
                    'to' => $change[1],
                ];
            }
        }
        $this->activityLogService->log($entityType, $entityId, $action, $entityData);
    }
}