<?php

namespace Drupal\helper\Commands;

use Drupal\Core\Cache\Cache;
use Drush\Commands\core\CacheCommands as DrushCacheCommands;
use Drush\Commands\DrushCommands;

class CacheCommands extends DrushCommands {

  /**
   * Add additional options to the drush cache:clear command.
   *
   * @hook on-event cache-clear
   */
  public function alterCacheTypes(array &$types, $include_bootstrapped_types) {
    if ($include_bootstrapped_types) {
      $types['libraries'] = [$this, 'clearLibraries'];
      $types['bootstrap'] = [$this, 'clearBootstrap'];
    }
  }

  /**
   * Clears the libraries (and CSS/JS) cache.
   */
  public static function clearLibraries() {
    Cache::invalidateTags(['library_info']);
    DrushCacheCommands::clearCssJs();
  }

  /**
   * Clears the bootstrap cache.
   */
  public static function clearBootstrap() {
    \Drupal::cache('bootstrap')->deleteAll();
  }

}
