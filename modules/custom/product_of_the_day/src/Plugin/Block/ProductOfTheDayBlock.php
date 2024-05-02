<?php
// In your custom module's src/Plugin/Block/ProductOfTheDayBlock.php file.

namespace Drupal\product_of_the_day\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Product of the Day' block.
 *
 * @Block(
 *   id = "product_of_the_day_block",
 *   admin_label = @Translation("Product of the Day"),
 * )
 */
class ProductOfTheDayBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ProductOfTheDayBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // Fetch nodes of the "Products" content type where field_product_of_the_day is true.
    $query = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'products')
      ->condition('status', 1)
      ->condition('field_product_of_the_day', 1);

    $nids = $query->execute();

    // Load the nodes.
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    // Build output for each node.
    foreach ($nodes as $node) {
      $image_url = '';
      // Get the image URL if an image is attached to the node.
      if ($node->hasField('field_product_image') && !$node->get('field_product_image')->isEmpty()) {
        $image_entity = $node->get('field_product_image')->entity;
        $image_url = $image_entity->createFileUrl();
      }

      // Get the summary field.
      $summary = '';
      if ($node->hasField('field_summary') && !$node->get('field_summary')->isEmpty()) {
        $summary = $node->get('field_summary')->value;
      }

      // Build the CTA link to the product detail page.
      $cta_link = [
        '#type' => 'link',
        '#title' => $this->t('View Details'),
        '#url' => '/admin/content/products_of_the_day',
      ];

      $build[] = [
        '#theme' => 'product_of_the_day_block_item',
        '#title' => $node->getTitle(),
        '#url' => $node->toUrl(),
        '#image_url' => $image_url,
        '#summary' => $summary,
      ];
    }

    return $build;
  }

}
