<?php

namespace Drupal\helper\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use Symfony\Component\Console\Input\InputInterface;

class ModuleCommands extends DrushCommands {

  /**
   * The module handler.
   */
  protected $moduleHandler;

  /**
   * SchemaCommands constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   */
  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Gets the schema version for a module.
   *
   * @command module:schema-version:get
   * @validate-module
   * @param string $module The module name, for example "system".
   * @usage drush module:schema-version:get system
   *   Displays the current schema version for the system module.
   * @aliases msvg
   */
  public function getSchemaVersion($module) {
    return (string) drupal_get_installed_schema_version($module);
  }

  /**
   * Sets the schema version for a module.
   *
   * @command module:schema-version:set
   * @validate-module
   * @param string $module The module name, for example "system".
   * @param int|string $version The version to set or the string
   *   "current[+-]number" to set a relative value to the current version.
   * @usage drush module:schema-version:set system 8701
   *   Sets the current schema version for the system module to 8701.
   * @usage drush module:schema-version:set system current-3
   *   Sets the current schema version for the system module to three minus the current schema.
   * @usage drush module:schema-version:set system current+1
   *   Sets the current schema version for the system module to one plus the current schema.
   * @aliases msvs
   *
   * @throws \InvalidArgumentException
   * @throws \Drush\Exceptions\UserAbortException
   * @throws \RuntimeException
   */
  public function setSchemaVersion($module, $version) {
    include_once DRUPAL_ROOT . '/core/includes/install.inc';
    drupal_load_updates();

    $current_version = (int) drupal_get_installed_schema_version($module);
    $last_removed = $this->moduleHandler->invoke($module, 'update_last_removed');
    $lowest = 8000;

    $args = [
      '!module' => $module,
      '!version' => &$version,
      '!current' => $current_version,
      '!removed' => $last_removed,
      '!lowest' => $lowest,
    ];

    if (preg_match('/(current([\+\-]))?(\d+)/', $version, $matches)) {
      switch ($matches[2]) {
        case '+':
          $version = $current_version + (int) $matches[3];
          break;
        case '-':
          $version = $current_version - (int) $matches[3];
          break;
        case '':
          $version = (int) $matches[3];
          break;
      }
    }
    else {
      throw new \InvalidArgumentException(dt('The schema version !version is not valid.', $args));
    }

    if ($version < $lowest) {
      throw new \InvalidArgumentException(dt('The schema version !version cannot be lower than !lowest.', $args));
    }
    elseif ($version === $current_version) {
      return dt('The !module module schema is already at !current.', $args);
    }
    elseif (!empty($last_removed) && $version < $last_removed) {
      $this->io()->caution(dt('The schema version !version is lower than the !module_update_last_removed() value of !removed. This will prevent module updates from running.', $args));
    }

    if (!$this->io()->confirm(dt('Do you want to set the schema for !module module from !current to !version?', $args))) {
      throw new UserAbortException();
    }

    if (!$this->getConfig()->simulate()) {
      drupal_set_installed_schema_version($module, $version);
      if (drupal_get_installed_schema_version($module, TRUE) !== $version) {
        throw new \RuntimeException(dt('Unable to update schema for !module module from !current to !version.', $args));
      }
    }

    return dt('Updated schema for !module module from !current to !version.', $args);
  }

  /**
   * @hook interact module:schema-version:set
   */
  public function interactSchemaVersion(InputInterface $input) {
    if ($input->getArgument('version') === NULL) {
      $module = $input->getArgument('module');
      $versions = $this->getAvailableSchemaVersions($module);
      if (empty($versions)) {
        throw new \RuntimeException(dt('No possible schema versions for !module module.', ['!module' => $module]));
      }
      $current_version = $this->getSchemaVersion($module);
      $this->io()->note(dt('The current schema version for !module module is !version.', ['!module' => $module, '!version' => $current_version]));
      $selected = $this->io()->choice(dt('Choose a schema version to set'), $versions);
      $input->setArgument('version', $versions[$selected]);
    }
  }

  /**
   * Deletes the schema version for a module.
   *
   * This is useful for removing leftover schemas of deleted modules.
   *
   * @command module:schema-version:delete
   * @validate-module
   * @param string $module The module name, for example "system".
   * @usage drush module:schema-version:delete system
   *   Deletes the system module schema version.
   * @aliases msvd
   */
  public function deleteSchemaVersion($module) {
    include_once DRUPAL_ROOT . '/core/includes/install.inc';
    drupal_load_updates();

    $args = [
      '!module' => $module,
    ];

    if ($this->moduleHandler->moduleExists($module)) {
      $this->io()->caution(dt('You should uninstall the !module module instead of deleting its schema.', $args));
    }

    if (!$this->io()->confirm(dt('Do you want to delete the schema for !module module?', $args))) {
      throw new UserAbortException();
    }

    if (!$this->getConfig()->simulate()) {
      \Drupal::keyValue('system.schema')->delete($module);
      if (drupal_get_installed_schema_version($module, TRUE) !== SCHEMA_UNINSTALLED) {
        throw new \RuntimeException(dt('Unable to delete schema for !module module.', $args));
      }
    }

    return dt('Deleted schema for !module module.', $args);
  }

  /**
   * Cleans up the schema versions for deleted modules.
   *
   * @command module:schema-version:cleanup
   * @usage drush module:schema-version:cleanup
   *   Cleans up any missing module schemas.
   * @aliases msvc
   */
  public function cleanupSchemaVersion() {
    include_once DRUPAL_ROOT . '/core/includes/install.inc';
    drupal_load_updates();

    $list = $this->moduleHandler->getModuleList();
    $schemas = drupal_get_installed_schema_version(NULL, FALSE, TRUE);
    $missing = array_keys(array_diff_key($schemas, $list));
    if (empty($missing)) {
      return dt('No deleted module schemas to cleanup.');
    }

    $this->io()->note(dt('Found the following deleted modules with schemas to clean up:'));
    $this->io()->listing($missing);

    foreach ($missing as $module) {
      $this->io()->success($this->deleteSchemaVersion($module));
    }
  }

  /**
   * Validate that a config name is valid.
   *
   * If the argument to be validated is not named $config_name, pass the
   * argument name as the value of the annotation.
   *
   * @hook validate @validate-module
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *
   * @return \Consolidation\AnnotatedCommand\CommandError|null
   */
  public function validateModule(CommandData $commandData) {
    $arg_name = $commandData->annotationData()->get('validate-module-name', null) ?: 'module';
    $module = $commandData->input()->getArgument($arg_name);
    if (\Drupal::keyValue('system.schema')->get($module) === NULL) {
      throw new \InvalidArgumentException(dt('The !module module is not installed.', ['!module' => $module]));
    }
  }

  /**
   * Returns a list of possible schema versions for a module.
   *
   * @param string $module
   *   The module name.
   *
   * @return int[]
   *   An array of schema versions.
   */
  protected function getAvailableSchemaVersions($module) {
    include_once DRUPAL_ROOT . '/core/includes/install.inc';
    drupal_load_updates();

    $versions = drupal_get_schema_versions($module);
    if (!$versions) {
      $versions = [];
    }

    // Add the minimum schema version available, which is either the value from
    // hook_update_last_removed(), or the n000 where n is the major Drupal
    // version number, since that is the schema version modules receive by
    // default when they are installed.
    $minimum_version = $this->moduleHandler->invoke($module, 'update_last_removed') ?? drush_drupal_major_version() * 1000;
    array_unshift($versions, $minimum_version);

    // Add the current version minus one as an option.
    $current_version = drupal_get_installed_schema_version($module);
    if ($current_version > 0 && ($current_version - 1) > $minimum_version) {
      $versions[] = $current_version - 1;
    }

    // Filter out the current version as well as any versions below the last
    // removed version.
    $versions = array_filter($versions, function ($version) use ($current_version, $minimum_version) {
      return $version != $current_version && $version >= $minimum_version;
    });

    // Unique and sort the options.
    $versions = array_unique($versions);
    sort($versions);

    return $versions;
  }

}
