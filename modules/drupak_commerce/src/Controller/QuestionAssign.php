<?php

namespace Drupal\drupak_commerce\Controller;

use Drupal\user\Entity\User;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\private_message\Entity\PrivateMessage;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

// Use Drupal\Core\Entity\EntityInterface;.
use Drupal\Component\Render\FormattableMarkup;

/**
 *
 */
class QuestionAssign extends ControllerBase {

  /**
   * @inherit
   *
   * @param $id
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function assignTo($id) {
    $build = [];
    $build['content'] = [
      '#markup' => '<div>' . $id . '</div>',
    ];

    $private_message = PrivateMessage::load($id);
    $user_id = $private_message->get("owner")->getValue()[0]['target_id'];
    $node_id = $private_message->get("field_node_reference")
      ->getValue()[0]['target_id'];
    $nodes = Node::load($node_id);
    $node_title = $nodes->getTitle();
    $delivery = $private_message->get("field_delivery")->getValue()[0]['value'];
    $logged_in = \Drupal::currentUser()->id();

    $date = strtotime("+$delivery day", strtotime(date('Y-m-d H:i:s')));
    $d_date = gmdate('Y-m-d\TH:i:s', $date);
    //
    // kint($delivery);
    // kint($logged_in = \Drupal::currentUser()->id());
    $n_title = 'Order = ' . $node_title;
    /* Creating relation node used for order */
    $node = Node::create([
      'type' => 'relation',
      'title' => $n_title,
      'langcode' => 'en',
      'uid' => $logged_in,
      'status' => 1,
      'field_applied_user' => [$user_id],
      'field_question_from' => [$node_id],
      'field_delivery' => [$delivery],
      'field_deliveryy' => [$d_date],
      'field_order_status' => ['Progress'],
      'field_deliver_now' => [
        'uri' => "internal:/deliver/$node_id",
        'title' => 'Deliver Now',
        'options' => [
          'attributes' => [
            'target' => '_blank',
          ],
        ],
      ],

    ]);
    $node->save();
    $new_node_id = $node->id();
    $user1 = User::load($logged_in);
    $user2 = User::load($user_id);
    $user1_name = $user1->getUsername();
    $members = [$user1, $user2];
    // kint($members);
    $service = \Drupal::service('private_message.service');

    // This will create a thread if one does not exist.
    $private_message_thread = $service->getThreadForMembers($members);

    // Add a Message to the thread.
    $private_message = PrivateMessage::create();
    $private_message->set('owner', $user2);
    // $linkText = 'Node Title';
    //    $linkMarkup = Markup::create($linkText);
    //    $link = Link::fromTextAndUrl($linkMarkup, Url::fromUri('entity:node/1'));
    //    $link = $link->toRenderable();
    // $form['#title'] = $this->t("'%name' block", array('%name' => $info[$block->delta]['info']));
    $link = Url::fromRoute('entity.node.canonical', ['node' => $new_node_id])
      ->toString();
    $formatted = new FormattableMarkup(
      '<a href=@link>Node</a>',
      [
        '@link' => $link,
      ]
    );

    // kint($link);
    $msg = t("Congrats you have got order from $user1_name on Question @formatted", ['@formatted' => $formatted]);
    // kint($msg);
    // $rendered_msg = render($msg);
    // $msg_markup = Markup::create($rendered_msg);
    // kint($msg_markup);
    // exit();
    $private_message->set('message', $msg);
    $private_message->save();
    $private_message_thread->addMessage($private_message)->save();

    $path = "internal:/node/$new_node_id";
    // Choose a path.
    $url = Url::fromUri($path);
    $destination = $url->toString();
    // ksm($destination);
    // We want to redirect user on login.
    $response = new RedirectResponse($destination, 301);
    $response->send();
    return;
  }

}
