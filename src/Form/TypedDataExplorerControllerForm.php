<?php

namespace Drupal\typed_data_explorer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TypedDataExplorerControllerForm.
 *
 * @package Drupal\typed_data_explorer\Form
 */
class TypedDataExplorerControllerForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'typed_data_explorer_controller_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['entity_type'] = [
      '#type' => 'select',
      '#options' => ['node' => 'node', 'user' => 'user'],
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#size' => 10,
    ];

    $form['explore'] = [
      '#type' => 'submit',
      '#value' => $this->t('Explore'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('typed_data_explorer.entity_explore', [
      'entity_type' => $form_state->getValue('entity_type'),
      'id' => $form_state->getValue('id')
    ]);
  }
  
}
