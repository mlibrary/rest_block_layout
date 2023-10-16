<?php

namespace Drupal\rest_block_layout\Normalizer;

use Drupal\Core\Block\MainContentBlockPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\serialization\Normalizer\ConfigEntityNormalizer;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Normalizes/denormalizes main content block objects into an array structure.
 */
class BlockNormalizer extends ConfigEntityNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var array
   */
  protected $supportedInterfaceOrClass = ['Drupal\block\BlockInterface'];

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              EntityTypeRepositoryInterface $entity_type_repository=NULL,
                              EntityFieldManagerInterface $entity_field_manager=NULL,
                              RequestStack $request_stack
                             ) {
    parent::__construct($entity_type_manager, $entity_type_repository, $entity_field_manager);
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = array()): \ArrayObject|array|string|int|float|bool|null {
    $normalized = parent::normalize($object, $format, $context);

    // Ensure this is a main content block.
    if (!$object->getPlugin() instanceof MainContentBlockPluginInterface) {
      return $normalized;
    }

    // Get the current request.
    $request = $this->requestStack->getCurrentRequest();

    if ($route_name = $request->attributes->get('_block_layout_route')) {
      $normalized['route_name'] = $route_name;
    }

    if ($entity = $request->attributes->get('_block_layout_entity')) {
      if ($access = $request->attributes->get('_block_layout_access')) {
        if ($access->isAllowed()) {
          $normalized['entity_type'] = $entity->getEntityTypeId();
          $normalized['entity'] = $this->serializer->normalize($entity, $format, $context);
        }
      }
    }

    return $normalized;
  }

}
