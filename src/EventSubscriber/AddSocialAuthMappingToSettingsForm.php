<?php

namespace Drupal\improve_social_auth\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\core_event_dispatcher\Event\Form\FormAlterEvent;
use Drupal\core_event_dispatcher\FormHookEvents;
use Drupal\social_auth\Form\SocialAuthSettingsForm;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Add Social Auth Mapping To Settings From Event Subscriber.
 */
class AddSocialAuthMappingToSettingsForm implements EventSubscriberInterface {

  use StringTranslationTrait;

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    protected $string_translation,
  ) {}

  /**
   * Alter the Social Auth Settings Form.
   */
  public function __invoke(FormAlterEvent $event): void {
    $form = &$event->getForm();
    $form_state = $event->getFormState();

    // Check if the form extends SocialAuthSettingsForm.
    if (is_subclass_of($form_state->getFormObject(), SocialAuthSettingsForm::class)) {
      $config = $this->configFactory->get('social_auth.settings');
      $default_value = $config->get('social_auth_mapping') ?: [];

      // Add the custom multi-value field.
      $form['social_auth_mapping'] = [
        '#type' => 'social_auth_mapping_element',
        '#title' => $this->t('Social Auth Mappings'),
        '#default_value' => $default_value,
      ];

      // Add the custom submit handler.
      $form['#submit'][] = [static::class, 'socialAuthSettingsSubmitHandler'];
    }
  }

  /**
   * Custom submit handler to process and store the social auth mappings.
   */
  public static function socialAuthSettingsSubmitHandler(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValue('social_auth_mapping');
    \Drupal::configFactory()->getEditable('social_auth.settings')
      ->set('social_auth_mapping', $values)
      ->save();
  }

  /**
   * Registers the event subscriber.
   *
   * @return array
   *   An array of subscribed events.
   */
  #[\Override] public static function getSubscribedEvents(): array {
    $events[FormHookEvents::FORM_ALTER][] = ['__invoke'];
    return $events;
  }

}
