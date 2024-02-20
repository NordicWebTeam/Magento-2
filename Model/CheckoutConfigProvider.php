<?php
/**
 * Copyright © Qliro AB. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Qliro\QliroOne\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Store\Model\StoreManagerInterface;
use Qliro\QliroOne\Model\Security\AjaxToken;
use Qliro\QliroOne\Model\Management\CountrySelect;

/**
 * QliroOne Cehckout config provider class
 */
class CheckoutConfigProvider implements ConfigProviderInterface
{
    /**
     * @var \Magento\Quote\Model\Quote
     */
    private $quote;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Qliro\QliroOne\Model\Security\AjaxToken
     */
    private $ajaxToken;

    /**
     * @var \Qliro\QliroOne\Model\Config
     */
    private $qliroConfig;

    /**
     * @var Fee
     */
    private $fee;

    /**
     * @var CountrySelect
     */
    private CountrySelect $countrySelect;


    /**
     * Inject dependencies
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Qliro\QliroOne\Model\Security\AjaxToken $ajaxToken
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Qliro\QliroOne\Model\Config $qliroConfig
     * @param \Qliro\QliroOne\Model\Fee $fee
     * @param CountrySelect $countrySelect
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        AjaxToken $ajaxToken,
        Session $checkoutSession,
        Config $qliroConfig,
        \Qliro\QliroOne\Model\Fee $fee,
        CountrySelect $countrySelect
    ) {
        $this->quote = $checkoutSession->getQuote();
        $this->storeManager = $storeManager;
        $this->ajaxToken = $ajaxToken;
        $this->qliroConfig = $qliroConfig;
        $this->fee = $fee;
        $this->countrySelect = $countrySelect;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        $config = [
            'qliro' => [
                'enabled' => $this->qliroConfig->isActive(),
                'isDebug' => $this->qliroConfig->isDebugMode(),
                'isEagerCheckoutRefresh' => $this->qliroConfig->isEagerCheckoutRefresh(),
                'checkoutTitle' => $this->qliroConfig->getTitle(),
                'securityToken' => $this->ajaxToken->setQuote($this->quote)->getToken(),
                'updateQuoteUrl' => $this->getUrl('checkout/qliro_ajax/updateQuote'),
                'updateCustomerUrl' => $this->getUrl('checkout/qliro_ajax/updateCustomer'),
                'updateShippingMethodUrl' => $this->getUrl('checkout/qliro_ajax/updateShippingMethod'),
                'updateShippingPriceUrl' => $this->getUrl('checkout/qliro_ajax/updateShippingPrice'),
                'updatePaymentMethodUrl' => $this->getUrl('checkout/qliro_ajax/updatePaymentMethod'),
                'pollPendingUrl' => $this->getUrl('checkout/qliro_ajax/pollPending'),
                'qliroone_fee' => []
            ],
        ];

        // If country selector is enabled, add available countries to config
        if ($this->qliroConfig->isUseCountrySelector()) {
            $config['qliro']['countrySelector'] = [
               'updateCountryUrl' => $this->getUrl('checkout/qliro_ajax/updateCountry'),
               'availableCountries' => $this->qliroConfig->getAvailableCountries(),
               'selectedCountry' => $this->getSelectedCountry()
            ];
        }

        $feeSetup = $this->fee->getFeeSetup($this->quote->getStoreId());
        if (empty($feeSetup)) {
            return $config;
        }

        $config['qliro']['qliroone_fee']['fee_setup'] =
            $this->fee->applyDisplayFlagsToFeeArray($this->quote, $feeSetup);

        return $config;
    }

    /**
     * Get a store-specific URL with provided path
     *
     * @param string $path
     * @return string
     */
    private function getUrl($path)
    {
        $store = $this->storeManager->getStore();

        return $store->getUrl($path);
    }

    /**
     * @return string
     */
    private function getSelectedCountry(): string
    {
        $selectedCountry = $this->countrySelect->getSelectedCountry();
        if (!!$selectedCountry) {
            return $selectedCountry;
        }

        $quote = $this->quote;
        $mainAddress = $quote->getShippingAddress();
        if ($quote->isVirtual()) {
            $mainAddress = $quote->getBillingAddress();
        }

        $addressCountry = $mainAddress->getCountryId();
        if (!$addressCountry) {
            return $this->qliroConfig->getDefaultCountry();
        }
        return $addressCountry;
    }
}
