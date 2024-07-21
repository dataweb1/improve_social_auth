<?php

namespace Drupal\improve_social_auth\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for social_auth_mapping.
 *
 * @FormElement("social_auth_mapping_element")
 */
class SocialAuthMappingElement extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      '#input' => TRUE,
      '#process' => [
        [static::class, 'processSocialAuthMappingElement'],
      ],
      '#element_validate' => [
        [static::class, 'validateSocialAuthMappingElement'],
      ],
      '#value_callback' => [static::class, 'valueCallback'],
      '#theme' => 'social_auth_mapping_element',
      '#theme_wrappers' => ['fieldset'],
      '#default_value' => [],
    ];
  }

  /**
   * Process callback for the social_auth_mapping_element.
   */
  /*
  public static function processSocialAuthMappingElement(&$element, FormStateInterface $form_state, &$complete_form) {
  // Add a container to hold multiple values.
  $element['values'] = [
  '#type' => 'container',
  '#attributes' => ['id' => 'social-auth-mappings-wrapper'],
  '#tree' => TRUE,
  ];

  // Get the number of items already in the form state or default to 1.
  $num_items = $form_state->get('num_social_auth_mappings');
  if ($num_items === NULL) {
  $num_items = count($element['#default_value']);
  $form_state->set('num_social_auth_mappings', $num_items);
  }

  // Add each item.
  for ($i = 0; $i < $num_items; $i++) {
  $element['values'][$i] = [
  'social_auth_user_key' => [
  '#type' => 'textfield',
  '#title' => t('Social Auth User Key'),
  '#default_value' => $element['#value'][$i]['social_auth_user_key'] ?? '',
  '#description' => t('Specify the Social Auth User key of the value you want to assign.<br>The basic values come with the keys: <code>email</code>, <code>first_name</code>, <code>last_name</code>, <code>full_name</code> and <code>picture_url</code>. Extra keys can be obtained via a scope extension.'),
  ],
  'user_mapping_field' => [
  '#type' => 'select',
  '#title' => t('User Mapping Field'),
  '#options' => static::getUserFieldOptions(),
  '#default_value' => $element['#value'][$i]['user_mapping_field'] ?? '',
  '#description' => t('Specify the user field to assign the value to.'),
  ],
  ];
  }

  // Add the "add more" button.
  $element['add_more'] = [
  '#type' => 'submit',
  '#value' => t('Add another mapping'),
  '#submit' => [[static::class, 'addMoreSubmit']],
  "#element_parents" => $element['#parents'],
  '#ajax' => [
  'callback' => [static::class, 'addMoreAjax'],
  'wrapper' => 'social-auth-mappings-wrapper',
  ],
  ];

  return $element;
  }
   */

  /**
   * Process callback for the social_auth_mapping_element.
   */
  public static function processSocialAuthMappingElement(&$element, FormStateInterface $form_state, &$complete_form) {
    $triggering_element = $form_state->getTriggeringElement();
    // Add a container to hold multiple values.
    $element['table'] = [
      '#type' => 'table',
      '#header' => [
        t('Social Auth User Key'),
        t('User Mapping Field'),
        t('Operations'),
      ],
      '#attributes' => ['id' => 'social-auth-mappings-wrapper'],
      '#tree' => TRUE,
    ];

    // Get the number of items already in the form state or default to 1.
    $num_items = $form_state->get('num_social_auth_mappings');
    if ($num_items === NULL) {
      $num_items = count($element['#default_value']);
      $form_state->set('num_social_auth_mappings', $num_items);
    }

    if (in_array($triggering_element['#op'], ['add', 'remove'])) {
      $element['#value'] = $form_state->getValue($triggering_element['#element_parents']);
    }

    // Add each item.
    for ($i = 0; $i < $num_items; $i++) {
      $element['table'][$i]['social_auth_user_key'] = [
        '#type' => 'textfield',
        '#title' => t('Social Auth User Key'),
        '#title_display' => 'none',
        '#default_value' => $element['#default_value'][$i]['social_auth_user_key'] ?? '',
        '#value' => $element['#value'][$i]['social_auth_user_key'] ?? '',
        '#description' => t('Specify the Social Auth User key of the value you want to assign.<br>The basic values come with the keys: <code>email</code>, <code>first_name</code>, <code>last_name</code>, <code>full_name</code> and <code>picture_url</code>. Extra keys can be obtained via a scope extension.'),
      ];
      $element['table'][$i]['user_mapping_field'] = [
        '#type' => 'select',
        '#title' => t('User Mapping Field'),
        '#title_display' => 'none',
        '#options' => static::getUserFieldOptions(),
        '#default_value' => $element['#default_value'][$i]['user_mapping_field'] ?? '',
        '#value' => $element['#value'][$i]['user_mapping_field'] ?? '',
        '#description' => t('Specify the user field to assign the value to.'),
      ];
      $element['table'][$i]['remove'] = [
        '#type' => 'submit',
        '#value' => t('Remove'),
        '#submit' => [[static::class, 'removeItemSubmit']],
        '#name' => 'remove_' . $i,
        '#op' => 'remove',
        '#element_parents' => $element['#parents'],
        '#ajax' => [
          'callback' => [static::class, 'ajaxCallback'],
          'wrapper' => 'social-auth-mappings-wrapper',
        ],
      ];
    }

    // Add the "add more" button.
    $element['add_more'] = [
      '#type' => 'submit',
      '#value' => t('Add another mapping'),
      '#element_parents' => $element['#parents'],
      '#submit' => [[static::class, 'addMoreSubmit']],
      '#op' => 'add',
      '#ajax' => [
        'callback' => [static::class, 'ajaxCallback'],
        'wrapper' => 'social-auth-mappings-wrapper',
      ],
    ];

    return $element;
  }

  /**
   * AJAX callback to refresh the element.
   */
  public static function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, $triggering_element['#element_parents']);
    return $element['table'];
  }

  /**
   * Submit handler to add a new item.
   */
  public static function addMoreSubmit(array &$form, FormStateInterface $form_state) {
    $num_items = $form_state->get('num_social_auth_mappings');
    $form_state->set('num_social_auth_mappings', $num_items + 1);
    $form_state->setRebuild();
  }

  /**
   * Submit handler to remove an item.
   */
  public static function removeItemSubmit(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $name = $triggering_element['#name'];
    $index = str_replace('remove_', '', $name);
    $num_items = $form_state->get('num_social_auth_mappings');
    $values = NestedArray::getValue($form_state->getValues(), $triggering_element['#element_parents']);
    unset($values[$index]);
    $form_state->setValue($triggering_element['#element_parents'], $values);
    $form_state->set('num_social_auth_mappings', $num_items - 1);
    $form_state->setRebuild();
  }

  /**
   * Validation callback for the element.
   */
  public static function validateSocialAuthMappingElement(&$element, FormStateInterface $form_state, &$complete_form) {
    // Add custom validation if needed.
  }

  /**
   * Value callback for the element.
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state): void {
    $values = $form_state->getValue($element['#parents']);
    if (is_null($values)) {
      $values = $form_state->getUserInput()['table'];
    }

    $final_values = [];
    foreach ($values as $value) {
      if (empty($value['social_auth_user_key'])) {
        continue;
      }
      $final_values[] = $value;
    }

    $form_state->setValue($element['#parents'], $final_values);
    $form_state->unsetValue('table');
  }

  /**
   * AJAX callback for the "add more" button.
   */
  /*
  public static function addMoreAjax(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, $triggering_element['#element_parents']);
    return $element['values'];
  }
  */
  /**
   * Submit handler for the "add more" button.
   */
  /*
  public static function addMoreSubmit(array &$form, FormStateInterface $form_state) {
    $num_items = $form_state->get('num_social_auth_mappings');
    $form_state->set('num_social_auth_mappings', $num_items + 1);
    $form_state->setRebuild();
  }
  */
  /**
   * Validation callback for the element.
   */
  /*
  public static function validateSocialAuthMappingElement(&$element, FormStateInterface $form_state, &$complete_form) {
    // Add custom validation if needed.
  }
  */
  /**
   * Value callback for the element.
   */
  /*
  public static function valueCallback(&$element, $input, FormStateInterface $form_state): void {
    $final_values = [];
    $values = $form_state->getUserInput()['values'];
    foreach ($values as $value) {
      if (empty($value['social_auth_user_key'])) {
        continue;
      }
      $final_values[] = $value;
    }
    $form_state->setValue($element['#parents'], $final_values);
    $form_state->unsetValue('values');
  }
  */

  /**
   * Helper function to get user field options.
   */
  protected static function getUserFieldOptions(): array {
    $user_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('user', 'user');
    $options = [];
    foreach ($user_fields as $field_name => $field_definition) {
      $options[$field_name] = $field_definition->getLabel();
    }
    return $options;
  }

}
