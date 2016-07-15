<?php

class Scriptkid_Setshippingincart_Model_Observer
{

  public function setShippingAddressData($observer)
  {
      $_quote = Mage::getSingleton('checkout/session')->getQuote();

      if (! $_quote->hasItems()) {
          return;
      }

      $billingAddress = $_quote->getBillingAddress();
      $shippingAddress = $_quote->getShippingAddress();
      $country = Mage::getStoreConfig('shipping/origin/country_id');
      $state = Mage::getStoreConfig('shipping/origin/region_id');
      $postcode = Mage::getStoreConfig('shipping/origin/postcode');

      if(!$billingAddress || !$billingAddress->getCountry())
      {
        $_quote->getBillingAddress()
          ->setCountryId($country)
          ->setRegionId($state)
          ->setPostcode($postcode)
          ->setCollectShippingRates(true);
      }

      if(!$shippingAddress || !$shippingAddress->getCountry())
      {
        $_quote->getShippingAddress()
          ->setCountryId($country)
          ->setRegionId($state)
          ->setPostcode($postcode)
          ->setCollectShippingRates(true);
      }

      //$_quote->save();

      return $_quote;
  }

  public function addShippingToCart($observer)
  {
    $_session = Mage::getSingleton('checkout/session');
    $_quote = $_session->getQuote();
    if (! $_quote->hasItems()) {
        return;
    }
    $shippingAddress = $_quote->getShippingAddress();
    $shippingAddress->setCollectShippingRates(true)->collectShippingRates();
    $rates = $shippingAddress->getGroupedAllShippingRates();

    if (count($rates)) {
      $topRates = reset($rates);
      foreach($topRates as $topRate) {
        /** @var Mage_Sales_Model_Quote_Address_Rate $topRate */
        try {
          $shippingAddress->setShippingMethod($topRate->getCode());
          $shippingDescription = $topRate->getCarrierTitle() . ' - ' . $topRate->getMethodTitle();
          $shippingAddress->setShippingAmount($topRate->getPrice());
          $shippingAddress->setBaseShippingAmount($topRate->getPrice());
          $shippingAddress->setShippingDescription(trim($shippingDescription, ' -'));
          $_quote->save();
          $_session->resetCheckout();
          $_session->setAutoShippingMethod($topRate->getCode());
        } catch (Mage_Core_Exception $e) {
          $_session->addError($e->getMessage());
        }
        catch (Exception $e) {
          $_session->addException(
              $e, Mage::helper('checkout')->__('Load customer quote error')
            );
          }
          return;
        }
      }
  }

}
