<?php

namespace Drupal\digitalia_muni_query_modifications\EventSubscriber;

use Drupal\search_api_solr\Event\PreQueryEvent;
use Drupal\search_api_solr\Event\SearchApiSolrEvents;
use Drupal\search_api\Query\QueryInterface as SapiQueryInterface;
use Solarium\Core\Query\QueryInterface as SolariumQueryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\search_api\Event\SearchApiEvents;

/**
* Alters the query where necessary to implement business logic.
*
* @package Drupal\digitalia_muni_query_modifications\EventSubscriber
*/

class SolrQueryAlterEventSubscriber implements EventSubscriberInterface {

/**
* {@inheritdoc}
*/
  public static function getSubscribedEvents(): array {
    return [
      SearchApiEvents::QUERY_PRE_EXECUTE => 'processResults',
    ];
  }

/**
* {@inheritdoc}
*/

  public function processResults($event): void {

    $query = $event->getQuery();
    $key = $query->getKeys();

    \Drupal::logger('query modifications')->debug("key: " . print_r($key, true));

    if (!is_null($key)) {
      $replace = false;
    // If key is enclosed in quotes, replace partial search fulltext field with exact search fulltext field (fulltext edge with fulltext).
      if (is_array($key)) {
        $replace = sizeof($key) >= 2 && $key[0] == '"' && $key[sizeof($key) - 1] == '"';
      }
      if (is_string($key)) {
        $replace = strlen($key) > 2 && substr($key, 0, 1) === '"' && substr($key, -1) === '"';
      }
    
      if ($replace) {
        $fulltext_fields = $query->getFulltextFields();
        $altered_fields = [];
        $replacements = [];

        \Drupal::logger('query modifications')->debug("fulltext_fields: " . print_r($fulltext_fields, TRUE));

        //$replacements['title_fulltext'] = 'title';
        $replacements['rendered_item_metadata'] = 'rendered_item_metadata_exact';
        $replacements['rendered_item_all'] = 'rendered_item_all_exact';

        foreach ($fulltext_fields as $field) {
          if (array_key_exists($field, $replacements)) {
            $field = $replacements[$field];
          }
          $altered_fields[] = $field;
        }

        $event->getQuery()->setFulltextFields($altered_fields);
      }
    }
  }
}
