<?php

namespace Irs\Klevu\Controller\Product;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\MessageQueue\PublisherInterface;

class Index extends Action
{
    protected $publisher;

    public function __construct(Context $context, PublisherInterface $publisher)
    {
        parent::__construct($context);

        $this->publisher = $publisher;
    }

    public function execute()
    {
        $this->publisher->publish('klevu.product.post', $this->getRequest()->getContent());

        return $this->resultFactory->create(ResultFactory::TYPE_RAW)
            ->setHttpResponseCode(200);
    }
}
