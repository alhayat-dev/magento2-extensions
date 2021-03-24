<?php

declare(strict_types=1);

namespace Alhayat\TransactionalEmail\Plugin\Controller\Account;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class LoginPost
{

    /**
     * Recipient email config path
     */
    const XML_PATH_EMAIL_RECIPIENT = 'alhayat_transactional_email_customer_logged_in_email_template';

    /**
     * @var Session $customerSession
     */

    protected Session $customerSession;
    /**
     * @var CustomerRepositoryInterface $customerRepository
     */
    protected CustomerRepositoryInterface $customerRepository;
    /**
     * @var TransportBuilder
     */
    protected TransportBuilder $_transportBuilder;
    /**
     * @var StateInterface
     */
    protected StateInterface $_inlineTranslation;
    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $_scopeConfig;
    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $_storeManager;
    /**
     * @var Escaper
     */
    protected Escaper $_escaper;
    /**
     * @var LoggerInterface $_logger
     */
    private LoggerInterface $_logger;

    /**
     * LoginPost constructor.
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Escaper $escaper
     * @param CustomerRepositoryInterface $customerRepository
     * @param Session $customerSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Escaper $escaper,
        CustomerRepositoryInterface $customerRepository,
        Session $customerSession,
        LoggerInterface $logger
    ) {
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;

        $this->_transportBuilder = $transportBuilder;
        $this->_inlineTranslation = $inlineTranslation;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_escaper = $escaper;
        $this->_logger = $logger;
    }

    /**
     * @param \Magento\Customer\Controller\Account\LoginPost $subject
     * @param $result
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterExecute(\Magento\Customer\Controller\Account\LoginPost $subject, $result)
    {
        $customer = $this->customerSession->getCustomer();

        // If customer data is empty then doesn't need to process
        if (!$customer) {
            return $this;
        }

        $this->_inlineTranslation->suspend();

        /* Receiver Detail */
        $receiverInfo = [
            'name' => 'Mudasser',
            'email' => 'mudasser@royalbathrooms.co.uk'
        ];

        $store = $this->_storeManager->getStore();

        $templateParams = [
            'store' => $store,
            'customer' => $customer,
            'administrator_name' => $receiverInfo['name']
        ];

        $transport = $this->_transportBuilder->setTemplateIdentifier(
            self::XML_PATH_EMAIL_RECIPIENT
        )->setTemplateOptions([
            'area' => 'frontend',
            'store' => $store->getId()
        ])->addTo(
            $receiverInfo['email'],
            $receiverInfo['name']
        )->setTemplateVars(
            $templateParams
        )->setFrom(
            'general'
        )->getTransport();
        try {
            // Send an email
            $transport->sendMessage();
        } catch (\Exception $e) {
            // Write a log message whenever get errors
            $this->_logger->critical($e->getMessage());
        }
        return $result;
    }
}
