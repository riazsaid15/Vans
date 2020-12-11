<?php
namespace Drupal\login_user\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Cache\Cache;

/**
 * Provides a block with a Login User Image.
 *
 * @Block(
 *   id = "login_user_block",
 *   admin_label = @Translation("Login User"),
 * )
 */

 class LoginUser extends BlockBase {

   /**
    * {@inheritdoc}
    */
  public function build() {
    // $displayImg = "";
    // $uid = "";
    $userinfo = [];
    $account = \Drupal::currentUser();
    if ($account->isAuthenticated()) {
      // Get site url
      $siteurl = \Drupal::urlGenerator()->generateFromRoute('<front>', [], ['absolute' => TRUE]);
      // Load current login user
      $user = User::load($account->id());
      $userinfo['image'] = '';
      if (!$user->user_picture->isEmpty()) {
        $userinfo['image'] = file_create_url($user->user_picture->entity->getFileUri());
      }
      $userinfo['uid'] = $user->get('uid')->value;
      $userinfo['email'] = $user->get('mail')->value;
      $userinfo['name'] = $user->get('name')->value;
    }

    $build = [
      '#user_id' => $userinfo['uid'],
      '#user_img' => $userinfo['image'],
      '#user_name' => ucwords($userinfo['name']),
      '#user_email' => $userinfo['email'],
      '#site_path' => $siteurl,
      '#theme' => 'login_user_template',
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['login_user_block_settings'] = $form_state->getValue('login_user_block_settings');
  }
 }
