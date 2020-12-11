<?php

namespace Drupal\helper;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Template\Attribute;
use Drupal\link\Plugin\Field\FieldType\LinkItem;

class Menu {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $menuTree;

  /**
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * File constructor.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   The entity type manager.
   * @param MenuActiveTrailInterface $menu_active_trail
   *   The active trail service.
   */
  public function __construct(MenuLinkTreeInterface $menu_tree, MenuActiveTrailInterface $menu_active_trail) {
    $this->menuTree = $menu_tree;
    $this->menuActiveTrail = $menu_active_trail;
  }

  /**
   * Builds a render array of a menu and its links.
   *
   * @param $menu_name
   *   The machine name of the menu.
   * @param int $level
   *   Initial visibility level. The menu is only visible if the menu item for
   *   the current page is at this level or below it. Use level 1 to always
   *   display this menu.
   * @param int $depth
   *   Number of levels to display, includes the initial level. Zero is
   *   unlimited depth.
   * @param bool $expand_all_items
   *   Override the option found on each menu link used for expanding children
   *   and instead display the whole menu tree as expanded.
   *
   * @see \Drupal\system\Plugin\Block\SystemMenuBlock::build
   *
   * @return array
   *   The render array.
   */
  public function buildMenu($menu_name, $level = 1, $depth = 0, $expand_all_items = FALSE) {
    if ($expand_all_items) {
      $parameters = new MenuTreeParameters();
      $active_trail = $this->menuActiveTrail->getActiveTrailIds($menu_name);
      $parameters->setActiveTrail($active_trail);
    }
    else {
      $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);
    }

    // Adjust the menu tree parameters based on the level and depth.
    $parameters->setMinDepth($level);
    // When the depth is configured to zero, there is no depth limit. When depth
    // is non-zero, it indicates the number of levels that must be displayed.
    // Hence this is a relative depth that we must convert to an actual
    // (absolute) depth, that may never exceed the maximum depth.
    if ($depth > 0) {
      $parameters->setMaxDepth(min($level + $depth - 1, $this->menuTree->maxDepth()));
    }

    // For menu blocks with start level greater than 1, only show menu items
    // from the current active trail. Adjust the root according to the current
    // position in the menu in order to determine if we can show the subtree.
    if ($level > 1) {
      if (count($parameters->activeTrail) >= $level) {
        // Active trail array is child-first. Reverse it, and pull the new menu
        // root based on the parent of the configured start level.
        $menu_trail_ids = array_reverse(array_values($parameters->activeTrail));
        $menu_root = $menu_trail_ids[$level - 1];
        $parameters->setRoot($menu_root)->setMinDepth(1);
        if ($depth > 0) {
          $parameters->setMaxDepth(min($level - 1 + $depth - 1, $this->menuTree->maxDepth()));
        }
      }
      else {
        return [];
      }
    }

    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);
    return $this->menuTree->build($tree);
  }

  /**
   * Builds a render array of a set of links like a menu.
   *
   * @param $menu_name
   *   The menu name (any value can be provided).
   * @param \Drupal\Core\Link[] $links
   *   An array of links.
   *
   * @return array
   *   The render array.
   */
  public function buildLinksAsMenu($menu_name, array $links) {
    return [
      '#theme' => 'menu__' . strtr($menu_name, '-', '_'),
      '#menu_name' => $menu_name,
      '#items' => $this->convertLinksToMenuLinks($links),
      // Make sure drupal_render() does not re-order the links.
      '#sorted' => TRUE,
    ];
  }

  /**
   * Convert a link field to an array of menu links.
   *
   * @param \Drupal\Core\Link[] $links
   *   The links to convert.
   *
   * @return array
   *   The menu link items, ready for rendering in a menu.
   */
  public function convertLinksToMenuLinks(array $links) {
    $menu_links = [];
    // Generate a menu-link-style key for each link.
    $key_prefix = 'helper_link:';
    foreach ($links as $delta => $link) {
      $menu_link = $this->convertLinkToMenuLink($link);
      $menu_links[$key_prefix . md5(serialize($menu_link))] = $menu_link;
    }
    return $menu_links;
  }

  /**
   * Convert a link into a menu link.
   *
   * @param \Drupal\Core\Link $link
   *   The link to convert.
   *
   * @return array
   *   The menu link, ready for rendering in a menu.
   */
  public function convertLinkToMenuLink(Link $link) {
    // @todo Should this abstracted to its own formatter for link field types?
    return [
      'is_expanded' => FALSE,
      'is_collapsed' => FALSE,
      'in_active_trail' => FALSE,
      'attributes' => new Attribute(),
      'title' => $link->getText(),
      'url' => $link->getUrl(),
      'below' => [],
      // @todo This should be a MenuLinkContent object.
      'original_link' => NULL,
    ];
  }

  /**
   * Builds a render array of a set of links like a menu.
   *
   * @param $menu_name
   *   The menu name (any value can be provided).
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   A link field item list.
   *
   * @return array
   *   The render array.
   */
  public function buildLinkFieldAsMenu($menu_name, FieldItemListInterface $items) {
    assert($items->getFieldDefinition()->getType() === 'link');

    $build = [
      '#theme' => 'menu__' . strtr($menu_name, '-', '_'),
      '#menu_name' => $menu_name,
      '#items' => $this->convertLinkItemsToMenuLinks($items),
      // Make sure drupal_render() does not re-order the links.
      '#sorted' => TRUE,
    ];

    $cache = CacheableMetadata::createFromObject($items->getEntity());
    $cache->applyTo($build);

    return $build;
  }

  /**
   * Convert a link field to an array of menu links.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The link field items to convert.
   *
   * @return array
   *   The menu link items, ready for rendering in a menu.
   */
  public function convertLinkItemsToMenuLinks(FieldItemListInterface $items) {
    $menu_links = [];
    // Generate a menu-link-style key for each link.
    $key_prefix = $items->getEntity()->getEntityTypeId() . ':' . $items->getEntity()->id() . ':' . $items->getFieldDefinition()->getName() . ':';
    foreach ($items as $delta => $item) {
      $link = $this->convertLinkItemToLink($item);
      $menu_links[$key_prefix . $delta] = $this->convertLinkToMenuLink($link);
    }
    return $menu_links;
  }

  /**
   * Convert a link field item into a link.
   *
   * @param \Drupal\link\Plugin\Field\FieldType\LinkItem $item
   *   The link field item to convert.
   *
   * @return \Drupal\Core\Link
   *   The link.
   */
  public function convertLinkItemToLink(LinkItem $item) {
    // Render the link item using the default formatter, because the title may
    // contain tokens.
    $item_build = $item->view();
    // Create a link object from the rendered item.
    return Link::fromTextAndUrl($item_build['#title'], $item_build['#url']);
  }

}
