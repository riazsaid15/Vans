<?php

namespace Drupal\drupak_commerce\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;


use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Class DeliverNow.
 *
 * @package Drupal\drupak_commerce\Controller
 */
class DeliverNow extends ControllerBase {

  /**
   * @inherit
   *
   * @param $id
   *
   * @return array
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deliver($id) {
    $build = [];
    $build['content'] = [
      '#markup' => '<div>' . $id . '</div>',
    ];

    $node = Node::load($id);
    // Comment 2 means open.
    $node->set("comment", 2);
    $node->save();

    $path = "entity:node/$id";
    $url = Url::fromUri($path);
    // Choose a path.
    $destination = $url->toString();
    // ksm($destination);
    // We want to redirect user on login.
    $response = new RedirectResponse($destination, 301);
    $response->send();

    return $build;
  }

}
