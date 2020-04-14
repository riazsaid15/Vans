<?php

namespace Drupal\drupak_commerce\EventSubscriber;


use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_product\Entity\Product;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\commerce_funds\Entity\Transaction;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Class QuestionPremiumOrderComplete
 *
 * @package Drupal\drupak_commerce\EventSubscriber
 */
class QuestionPremiumOrderComplete implements EventSubscriberInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['commerce_order.place.post_transition'] = ['orderCompleteHandler'];

    return $events;
  }


  /**
   * This method is called whenever the commerce_order.place.post_transition
   * event is dispatched.
   *
   * @param WorkflowTransitionEvent $event
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function orderCompleteHandler(WorkflowTransitionEvent $event) {
    $loginUserID = User::load(\Drupal::currentUser()->id());
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();    // Order items in the cart.
    $items = $order->getItems();
    //
    foreach ($order->getItems() as $key => $order_item) {
      $product_variation = $order_item->getPurchasedEntity();
      $products = $product_variation->get('product_id')->referencedEntities();
      foreach ($products as $prod) {
        if ($prod->bundle() == 'default') {
          $ques_product = Product::load($prod->id());
          // set featured field yes.
          $ask_ques_node = Node::load($ques_product->get('field_question_reference_to')
            ->referencedEntities()[0]->id());
         $nodePrice = $ask_ques_node->get('field_product_price')->getValue()['0'];

          $ask_ques_node->set('field_featured', 'yes');
          $ask_ques_node->save();

//          $fee_applied = \Drupal::service('commerce_funds.fees_manager')
//            ->calculateTransactionFee($amount, $currency, 'transfer');
//          // Create a new transaction.
//          $transaction = Transaction::create([
//            'issuer' => $loginUserID,
//            'recipient' => 1,
//            'type' => 'transfer',
//            'method' => 'internal',
//            'brut_amount' => $nodePrice['number'],
//            'net_amount' => $nodePrice['number'],
//            'fee' => $fee_applied['fee'],
//            'currency' => $nodePrice['currency_code'],
//            'status' => 'Completed',
//            'notes' => t('Transaction done'),
//          ]);
//          // Save the transaction to the db.
//          $transaction->save();
//
//          // Perform the transaction to update the differents balances.
//          \Drupal::service('commerce_funds.transaction_manager')
//            ->performTransaction($transaction);
        }
      }
    }




  }
}
