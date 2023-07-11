<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace App\Controller;

use App\Ecommerce\CheckoutManager\Confirm;
use App\Website\Navigation\BreadcrumbHelperService;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\PayPalSmartPaymentButton;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentRequest\AbstractRequest;
use Pimcore\Controller\FrontendController;
use Pimcore\Translation\Translator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use MercadoPago\Preference;
use MercadoPago\Item;
use MercadoPago\SDK;

class PaymentController extends FrontendController
{
    /**
     * @Route("/checkout-payment", name="shop-checkout-payment")
     *
     * @param Factory $factory
     * @param BreadcrumbHelperService $breadcrumbHelperService
     *
     * @return Response
     */
    public function checkoutPaymentAction(Factory $factory, BreadcrumbHelperService $breadcrumbHelperService)
    {

        SDK::setAccessToken('TEST-5044442275686658-050119-ff6feb38f6a3cfe3f9713cdaaf79e638-1364836314');
        $preference = new Preference();

        $cartManager = $factory->getCartManager();
        $breadcrumbHelperService->enrichCheckoutPage();

        $cart = $cartManager->getOrCreateCartByName('cart');

        if ($cart->isEmpty()) {
            return $this->redirectToRoute('shop-cart-detail');
        }

        // Obtener los productos del carrito para SDK MercadoPago
        $cartItems = $cart->getItems();

        // Iterar sobre los productos del carrito
        foreach ($cartItems as $cartItem) {
            $item = new Item();
            $product = $cartItem->getProduct(); 
            $item->title = $product->getName();
            $item->description = $product->getShortDescription();
            $item->quantity = $cartItem->getCount();
            $item->currency_id = "MXN";
            $item->unit_price = $product->getPrice();
            
            $products[] = $item;
        }

        // Crear preferencia MP
        $preference->items = $products;
        $preference->save();


        $checkoutManager = $factory->getCheckoutManager($cart);
        $paymentProvider = $checkoutManager->getPayment();

        $SDKUrl = '';
        if ($paymentProvider instanceof PayPalSmartPaymentButton) {
            $SDKUrl = $paymentProvider->buildPaymentSDKLink();
        }

        $trackingManager = $factory->getTrackingManager();
        $trackingManager->trackCheckoutStep(new Confirm($cart), $cart, 2);

        return $this->render('payment/checkout_payment.html.twig', [
            'cart' => $cart,
            'preference' => $preference,
            'item' => $item,
            'SDKUrl' => $SDKUrl
        ]);
    }

    /**
     * @Route("/checkout-start-payment", name="shop-checkout-start-payment")
     *
     * @param Request $request
     * @param Factory $factory
     *
     * @return JsonResponse
     */
    public function startPaymentAction(Request $request, Factory $factory, LoggerInterface $logger)
    {
        $cartManager = $factory->getCartManager();
        $cart = $cartManager->getOrCreateCartByName('cart');

        $checkoutManager = Factory::getInstance()->getCheckoutManager($cart);
        $paymentInformation = $checkoutManager->initOrderPayment();
        $order = $paymentInformation->getObject();

        $config = [
                'return_url' => '',
                'cancel_url' => 'https://demo.pimcore.fun/payment-error',
                'OrderDescription' => 'Mi orden ' . $order->getOrdernumber() . ' en gepyxis.com',
                'InternalPaymentId' => $paymentInformation->getInternalPaymentId()
            ];

        $paymentConfig = new AbstractRequest($config);

        $response = $checkoutManager->startOrderPaymentWithPaymentProvider($paymentConfig);

        return new JsonResponse($response->getJsonString(), 200, [], true);
    }

    /**
     * @Route("/payment-error", name = "shop-checkout-payment-error" )
     *
     * @return RedirectResponse
     */
    public function paymentErrorAction(Request $request, LoggerInterface $logger)
    {
        $this->addFlash('danger', 'Payment error');

        return $this->redirectToRoute('shop-checkout-payment');
    }

    /**
     * @Route("/payment-commit-order", name="shop-commit-order")
     *
     * @param Request $request
     * @param Factory $factory
     * @param LoggerInterface $logger
     * @param Translator $translator
     * @param SessionInterface $session
     *
     * @return RedirectResponse
     *
     */
    public function commitOrderAction(Request $request, Factory $factory, LoggerInterface $logger, Translator $translator, SessionInterface $session)
    {
        $cartManager = $factory->getCartManager();
        $cart = $cartManager->getOrCreateCartByName('cart');

        $checkoutManager = $factory->getCheckoutManager($cart);
        $params = array_merge($request->query->all(), $request->request->all());

        $order = $checkoutManager->handlePaymentResponseAndCommitOrderPayment($params);

        if (!$session->isStarted()) {
            $session->start();
        }

        $session->set('last_order_id', $order->getId());

        return $this->redirectToRoute('shop-checkout-completed');
    }
}
