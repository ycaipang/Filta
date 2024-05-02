<?php

// In your custom module's src/Controller/ProductAdminController.php file.

namespace Drupal\product_of_the_day\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element\File;

/**
 * Controller for the product admin page.
 */
class ProductAdminController extends ControllerBase {

  /**
   * Displays the product admin page.
   */
  public function content() {
    $header = [
        'image' => $this->t('Image'),
        'title' => $this->t('Title'),
        'summary' => $this->t('Summary'),
        'product_of_the_day' => $this->t('Product of the Day'),
        'edit' => $this->t('Edit'),
        'delete' => $this->t('Delete'),
      ];
    
    $rows = [];
    // Load existing products.
    $query = \Drupal::entityQuery('node')
      ->accessCheck(TRUE)
      ->condition('type', 'products') // Replace with your content type machine name.
      ->sort('created', 'DESC');
    $nids = $query->execute();
    $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);
    
    $product_of_the_day_count = 0;

    // Build rows for the table.
    foreach ($nodes as $node) {
      $image_entity = $node->get('field_product_image')->entity;
      $image_path = $image_entity->createFileUrl();
      $image_markup = '<img src="' . $image_path .'" alt="picture" width="100px" height="auto"/>';
      
      $product_of_the_day_value = $node->get('field_product_of_the_day')->value;

      // Check if the product is marked as "Product of the Day".
      if ($product_of_the_day_value && $product_of_the_day_count < 5) {
        $product_of_the_day_count++;
        $row['image'] = [
          'data' => [
            '#markup' => $image_markup,
          ],
        ];
        $row['title'] = $node->getTitle();
        $row['summary'] = $node->get('field_summary')->value;
        $row['product_of_the_day'] = $node->get('field_product_of_the_day')->value ? $this->t('Yes') : $this->t('No');
        // Edit link.
        $row['edit'] = \Drupal\Core\Link::createFromRoute(
          $this->t('Edit'),
          'entity.node.edit_form',
          ['node' => $node->id()]
        );
        // Delete link.
        $row['delete'] = \Drupal\Core\Link::createFromRoute(
          $this->t('Delete'),
          'product_of_the_day.admin',
          ['nid' => $node->id()],
          ['attributes' => ['onclick' => 'return confirm("' . $this->t('Are you sure you want to delete this product?') . '");']]
        );
        $rows[] = $row;
      }

      // Break the loop if 5 products have been found.
      if ($product_of_the_day_count >= 5) {
        break;
      }
    }
  
    $output['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No products found.'),
    ];

    $output['add_product'] = [
      '#type' => 'link',
      '#title' => $this->t('Add Product'),
      '#url' => Url::fromRoute('product_of_the_day.product_form'),
    ];
  
    // Add a link to add a new product.
    // $output['add_product'] = \Drupal::formBuilder()->getForm('Drupal\product_of_the_day\Form\ProductForm');
  
    return $output;
  }

  /**
   * Build the form element for editing the "Product of the Day" field.
   */
  private function buildProductOfTheDayForm($node) {
    $form = \Drupal::formBuilder()->getForm('\Drupal\product_of_the_day\Form\ProductForm.php', $node);
    return $form;
  }

  /**
   * Deletes a product.
   *
   * @param int $nid
   *   The ID of the product to delete.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects to the product admin page after deletion.
   */
  public function delete($nid) {
    $node = \Drupal\node\Entity\Node::load($nid);
  
    if ($node) {
        // Delete the node.
        $node->delete();
        // Set message.
        drupal_set_message($this->t('Product deleted successfully.'));
    }
    else {
        // Set error message if the node does not exist.
        drupal_set_message($this->t('Product not found.'), 'error');
    }

    // Redirect back to the product admin page after deletion.
    return $this->redirect('product_of_the_day.admin');
  }

}
