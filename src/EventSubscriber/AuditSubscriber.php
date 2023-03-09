<?php

namespace App\EventSubscriber;

use App\Entity\Activity;
use App\Service\ActivityLogService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class AuditSubscriber implements \Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface
{
    private ActivityLogService $activityLogService;
    private SerializerInterface $serializer;
    private array $removals;

    const IGNORED_ATTRIBUTES = [
        'ignored_attributes' =>
            [
                'password',
                'userIdentifier',
                'detalles',
                '__initializer__',
                '__cloner__',
                '__isInitialized__',
            ]
    ];

    const IGNORED_ATTRIBUTES_ALT = [

                'password',
                'userIdentifier',
                'detalles',
                '__initializer__',
                '__cloner__',
                '__isInitialized__',

    ];


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
//        $this->removals[] = $this->serializer->normalize($entity);
        $this->removals[] = $this->serializer->normalize($entity, null, self::IGNORED_ATTRIBUTES);
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
//            $entityData = $this->serializer->normalize($entity);
            $entityData = $this->serializer->normalize($entity, null, self::IGNORED_ATTRIBUTES_CONTEXT);
        } else {
            // For updates, we get the change set from Doctrine's Unit of Work manager.
            // This gives an array which contains only the fields which have
            // changed. We then just convert the numerical indexes to something
            // a bit more readable; "from" and "to" keys for the old and new values.
//            $entity = $this->serializer->normalize($entity, null, self::IGNORED_ATTRIBUTES_CONTEXT);
            $entityData = $uow->getEntityChangeSet($entity);
            $entityData = $this->serializer->normalize($entityData, null, [self::IGNORED_ATTRIBUTES]);
//            dd($entityData, '116');
            foreach ($entityData as $key => $value) {

                if(in_array($key, self::IGNORED_ATTRIBUTES_ALT)){
                    $entityData[$key][0]= '***';
                    $entityData[$key][1]= '***';
                };
////                dd($key, '118');
////                if (is_object($value[0])) {
////                    dd($entityData);
////                    dd($this->serializer->normalize($value[0], null, [AbstractNormalizer::IGNORED_ATTRIBUTES => ['password']]));
//                    $entityData[$key][0]= $this->serializer->normalize($value[0], null, [AbstractNormalizer::IGNORED_ATTRIBUTES => ['password']]);
//                    $entityData[$key][1]= $this->serializer->normalize($value[1], null, [self::IGNORED_ATTRIBUTES]);
////                    dd($entityData[$key][0]);
////                }
////                if (is_object($value[1])) {
////                    dd($entityData);
////                    dd($this->serializer->normalize($value[0], null, [AbstractNormalizer::IGNORED_ATTRIBUTES => ['password']]));
////                    $entityData[$key][1] = $this->serializer->normalize($value[1], null, self::IGNORED_ATTRIBUTES_CONTEXT);
////                    dd($entityData[$key][0]);
////                }
//
            }


//            dd($entityData, '128');
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