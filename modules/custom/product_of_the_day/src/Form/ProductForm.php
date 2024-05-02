<?php

// In your custom module's src/Form/ProductForm.php file.

namespace Drupal\product_of_the_day\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for adding/editing products.
 */
class ProductForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'product_of_the_day_product_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Title field.
    $form['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#required' => TRUE,
      ];
  
      // Image field.
      $form['image'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Image'),
        '#upload_location' => 'public://images/products',
        '#description' => $this->t('Upload an image for the product.'),
      ];
  
      // Summary field.
      $form['summary'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Summary'),
        '#description' => $this->t('Enter a summary for the product.'),
      ];
  
      // Product of the Day checkbox.
      $form['product_of_the_day'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Product of the Day'),
      ];
  
      // Submit button.
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      ];
  

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle form submission to create/update product nodes.
    $values = $form_state->getValues();

    // Prepare node fields.
    $node_fields = [
        'type' => 'products',
        'title' => $values['title'],
        'status' => 1,
        'uid' => \Drupal::currentUser()->id(),
    ];

    // If an image is uploaded, save it and attach it to the node.
    if (!empty($values['image'])) {
        $file = File::load($values['image'][0]);
        $file->setPermanent();
        $file->save();
        $node_fields['field_product_image'] = [
        'target_id' => $file->id(),
        ];
    }

    // Add the summary to the node.
    $node_fields['field_summary'] = [
        'value' => $values['summary'],
        'format' => 'basic_html', // Adjust the text format as needed.
    ];

    // Check if the "Product of the Day" checkbox is checked.
    $is_product_of_the_day = !empty($values['product_of_the_day']);

    // Check if this is a new node creation or an update.
    if ($form_state->getFormObject()->getEntity()->isNew()) {
        // Create a new node.
        $node = \Drupal\node\Entity\Node::create($node_fields);
    } else {
        // Load the existing node.
        $node = $form_state->getFormObject()->getEntity();
        // Update node fields.
        foreach ($node_fields as $field_name => $field_value) {
        $node->set($field_name, $field_value);
        }
    }

    // Save the node.
    $node->save();

    // Set message.
    if ($node->isNew()) {
        drupal_set_message($this->t('Product created successfully.'));
    } else {
        drupal_set_message($this->t('Product updated successfully.'));
    }

    // Redirect to a specific page after form submission.
    $form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);

  }

}
