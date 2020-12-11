<?php

namespace Drupal\helper\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class ToolbarController extends ControllerBase {

  /**
   * A request stack symfony instance.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a ToolbarController object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   A request stack symfony instance.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Reload the previous page.
   */
  public function reloadPage() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request->server->get('HTTP_REFERER')) {
      return $request->server->get('HTTP_REFERER');
    }
    else {
      return '/';
    }
  }

  /**
   * Flush the PHP caches.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function flushPhp() {
    if (function_exists('opcache_reset')) {
      opcache_reset();
      $this->messenger()->addMessage($this->t('PHP opcache cleared using opcache_reset().'));
    }

    if (function_exists('apc_cache_clear')) {
      apc_cache_clear();
      apc_clear_cache('user');
      apc_clear_cache('opcode');
      $this->messenger()->addMessage($this->t('PHP APC cache cleared using apc_cache_clear().'));
    }

    clearstatcache();
    $this->messenger()->addMessage($this->t('File status cache cleared using clearstatcache().'));

    return new RedirectResponse($this->reloadPage());
  }

  /**
   * Flush the libraries (and CSS/JS) cache.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function flushLibraries() {
    Cache::invalidateTags(['library_info']);
    \Drupal::service('asset.css.collection_optimizer')->deleteAll();
    \Drupal::service('asset.js.collection_optimizer')->deleteAll();
    _drupal_flush_css_js();

    $this->messenger()->addMessage($this->t('Libraries cache cleared.'));
    return new RedirectResponse($this->reloadPage());
  }

  /**
   * Flush the bootstrap cache.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function flushBootstrap() {
    \Drupal::cache('bootstrap')->deleteAll();

    $this->messenger()->addMessage($this->t('Bootstrap cache cleared.'));
    return new RedirectResponse($this->reloadPage());
  }

}
