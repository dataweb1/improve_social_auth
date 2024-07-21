<?php

namespace Drupal\improve_social_auth\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\social_auth\Event\SocialAuthEvents;
use Drupal\social_auth\Event\UserFieldsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Social Auth User Values Mapping on User PreSave Event Subscriber.
 *
 * @package Drupal\trip_newsletter\EventSubscriber
 */
readonly class SocialAuthUserValuesMappingOnUserPreSave implements EventSubscriberInterface {

  public function __construct(
    private ConfigFactoryInterface $configFactory
  ) {}

  /**
   * Adds first name and last name fields to user fields.
   *
   * @param \Drupal\social_auth\Event\UserFieldsEvent $event
   *   The user fields event.
   */
  public function __invoke(UserFieldsEvent $event) {
    $fields = $event->getUserFields();
    $user = $event->getSocialAuthUser();

    $config = $this->configFactory->get('social_auth.settings');
    $mappings = $config->get('social_auth_mapping') ?: [];
    if (empty($mappings)) {
      return;
    }

    foreach ($mappings as $mapping) {
      $social_auth_user_key = $mapping['social_auth_user_key'];
      switch ($mapping['social_auth_user_key']) {
        case 'email':
          $to_map_value = $user->getEmail();
          break;

        case 'full_name':
          $to_map_value = $user->getName();
          break;

        case 'first_name':
          $to_map_value = $user->getFirstName();
          break;

        case 'last_name':
          $to_map_value = $user->getLastName();
          break;

        case 'picture_url':
          $to_map_value = $user->getPictureUrl();
          break;

        default:
          $additional_data = $user->getAdditionalData();
          $to_map_value = $additional_data[$social_auth_user_key];
      }

      if (!empty($to_map_value)) {
        $fields[$mapping['user_mapping_field']] = $to_map_value;
      }
    }

    // Update the fields in the event.
    $event->setUserFields($fields);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SocialAuthEvents::USER_FIELDS][] = ['__invoke'];
    return $events;
  }

}
