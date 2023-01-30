<?php

declare(strict_types=1);

namespace Sorciento\QuickBOSearchBar\Controller\Adminhtml\Request;

use Magento\Backend\App\Action;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NotFoundException;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * @author    Sorciento <contact@sorciento.com>
 * @copyright 2023-present Sorciento
 * @license   https://opensource.org/licenses/MIT The MIT License
 * @link      https://www.sorciento.com/
 */
class Index extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = '';

    private const PRODUCT_QUERY_FIELDS = [
        'prod' => 'entity_id',
        'sku' => ProductInterface::SKU,
    ];

    private const ORDER_QUERY_FIELDS = [
        'inc' => OrderInterface::INCREMENT_ID,
        'ord@' => OrderInterface::CUSTOMER_EMAIL,
        'order' => OrderInterface::ENTITY_ID,
        'total' => OrderInterface::GRAND_TOTAL,
    ];

    private const CUSTOMER_QUERY_FIELDS = [
        'cust' => CustomerInterface::ID,
        'cus@' => CustomerInterface::EMAIL,
        'dob' => CustomerInterface::DOB,
    ];

    private const CMS_PAGE_QUERY_FIELDS = [
        'cmspid' => PageInterface::PAGE_ID,
        'cmspkey' => PageInterface::IDENTIFIER,
        'cmspti' => PageInterface::TITLE,
        'cmspco' => PageInterface::CONTENT,
    ];

    private const CMS_BlOCK_QUERY_FIELDS = [
        'cmsbid' => BlockInterface::BLOCK_ID,
        'cmsbkey' => BlockInterface::IDENTIFIER,
        'cmsbti' => BlockInterface::TITLE,
        'cmsbco' => BlockInterface::CONTENT,
    ];

    const PRODUCT_COLLECTION_FIELDS_TO_FILTER = [
        'entity_id',
        ProductInterface::SKU,
        ProductInterface::TYPE_ID,
        ProductInterface::NAME,
    ];

    const ORDER_COLLECTION_FIELDS_TO_FILTER = [
        OrderInterface::ENTITY_ID,
        OrderInterface::INCREMENT_ID,
        OrderInterface::CUSTOMER_EMAIL,
        OrderInterface::CREATED_AT,
        OrderInterface::GRAND_TOTAL,
    ];

    const CUSTOMER_COLLECTION_FIELDS_TO_FILTER = [
        'entity_id',
        CustomerInterface::FIRSTNAME,
        CustomerInterface::LASTNAME,
        CustomerInterface::EMAIL,
    ];

    const CMS_BLOCK_COLLECTION_FIELDS_TO_FILTER = [
        BlockInterface::BLOCK_ID,
        BlockInterface::IDENTIFIER,
        BlockInterface::TITLE,
        BlockInterface::CONTENT,
    ];

    const CMS_PAGE_COLLECTION_FIELDS_TO_FILTER = [
        PageInterface::PAGE_ID,
        PageInterface::IDENTIFIER,
        PageInterface::TITLE,
        PageInterface::CONTENT,
    ];

    protected JsonFactory $resultJsonFactory;

    protected ProductRepositoryInterface $productRepositoryInterface;
    protected OrderRepositoryInterface $orderRepositoryInterface;
    protected CustomerRepositoryInterface $customerRepositoryInterface;
    protected BlockRepositoryInterface $blockRepository;
    protected PageRepositoryInterface $pageRepository;

    protected SearchCriteriaBuilder $searchCriteriaBuilder;
    protected FilterBuilder $filterBuilder;

    protected Image $catalogImage;
    protected AreaList $areaList;

    protected Escaper $escaper;

    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepositoryInterface,
        OrderRepositoryInterface $orderRepositoryInterface,
        CustomerRepositoryInterface $customerRepositoryInterface,
        BlockRepositoryInterface $blockRepository,
        PageRepositoryInterface $pageRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        JsonFactory $resultJsonFactory,
        Image $catalogImage,
        AreaList $areaList,
        Escaper $escaper
    ) {
        parent::__construct($context);
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->blockRepository = $blockRepository;
        $this->pageRepository = $pageRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->catalogImage = $catalogImage;
        $this->areaList = $areaList;
        $this->escaper = $escaper;
    }

    /**
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute(): Json
    {
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();
        $query = explode(":", $this->getRequest()->getParam('query-input'),2);

        // If query directly starts with ":" return error
        if (count($query) > 1 && $query[0] === '') {
            $result->setData(
                [
                    'dataList' => [],
                    'success'  => false,
                ]);

            return $result;
        }

        $queryHasNoFilter = (count($query) === 1);

        $collectionResult = [];
        if ($queryHasNoFilter
            || (!$queryHasNoFilter && in_array($query[1], array_keys(self::PRODUCT_QUERY_FIELDS)))
        ) {
            $productResults = $this->getProductCollection(trim($query[0]),
                $queryHasNoFilter ? '' : self::PRODUCT_QUERY_FIELDS[$query[1]]);
            if ($productResults) {
                $collectionResult['product'] = $productResults;
            }
        }

        if ($queryHasNoFilter
            || (!$queryHasNoFilter && in_array($query[1], array_keys(self::ORDER_QUERY_FIELDS)))
        ) {
            $orderResults = $this->getOrderCollection(trim($query[0]),
                $queryHasNoFilter ? '' : self::ORDER_QUERY_FIELDS[$query[1]]);
            if ($orderResults) {
                $collectionResult['order'] = $orderResults;
            }
        }

        if ($queryHasNoFilter
            || (!$queryHasNoFilter && in_array($query[1], array_keys(self::CUSTOMER_QUERY_FIELDS)))
        ) {
            $customerResults = $this->getCustomerCollection(trim($query[0]),
                $queryHasNoFilter ? '' : self::ORDER_QUERY_FIELDS[$query[1]]);
            if ($customerResults) {
                $collectionResult['customer'] = $customerResults;
            }
        }

        if ($queryHasNoFilter
            || (!$queryHasNoFilter && in_array($query[1], array_keys(self::CMS_BlOCK_QUERY_FIELDS)))
        ) {
            $cmsBlockResults = $this->getCmsBlockCollection(trim($query[0]),
                $queryHasNoFilter ? '' : self::CMS_BlOCK_QUERY_FIELDS[$query[1]]);
            if ($cmsBlockResults) {
                $collectionResult['cms block'] = $cmsBlockResults;
            }
        }

        if ($queryHasNoFilter
            || (!$queryHasNoFilter && in_array($query[1], array_keys(self::CMS_PAGE_QUERY_FIELDS)))
        ) {
            $cmsPageResults = $this->getCmsPageCollection(trim($query[0]),
                $queryHasNoFilter ? '' : self::CMS_PAGE_QUERY_FIELDS[$query[1]]);
            if ($cmsPageResults) {
                $collectionResult['cms page'] = $cmsPageResults;
            }
        }

        $result->setData(
            [
                'dataList' => $collectionResult,
                'success'  => true,
            ]);

        return $result;
    }

    private function getProductCollection(string $query, string $searchKey = ''): array
    {
        $attributeToFilter = $searchKey ? [$searchKey] : self::PRODUCT_COLLECTION_FIELDS_TO_FILTER;

        $filters = $this->getFilters($attributeToFilter, $query);

        // To get product images correct URL
        $area = $this->areaList->getArea(Area::AREA_FRONTEND);
        $area->load(Area::PART_DESIGN);

        $collection = $this->productRepositoryInterface->getList($this->getSearchCriteria($filters))->getItems();

        $collectionResult = [];
        foreach ($collection as $product) {
            $collectionResult[$product->getId()] = [
                ProductInterface::SKU     => $product->getSku(),
                ProductInterface::TYPE_ID => $product->getTypeId(),
                ProductInterface::NAME    => $product->getName(),
                'thumbnail_url'           => $this->catalogImage->init($product, 'product_page_image_small')->resize(
                    40)->getUrl(),
                'full_line'               => implode(
                    ", ", [$product->getSku(), $product->getTypeId(), $product->getName()]),
                'link'                    => $this->_backendUrl
                    ->getUrl('catalog/product/edit', ['id' => $product->getId()]),
            ];
        }

        return $collectionResult;
    }

    private function getOrderCollection(string $query, string $searchKey = OrderInterface::INCREMENT_ID): array
    {
        $attributeToFilter = $searchKey ? [$searchKey] : self::ORDER_COLLECTION_FIELDS_TO_FILTER;

        $filters    = $this->getFilters($attributeToFilter, $query);
        $collection = $this->orderRepositoryInterface->getList($this->getSearchCriteria($filters))->getItems();

        $collectionResult = [];

        /** @var OrderInterface $order */
        foreach ($collection as $order) {
            $collectionResult[$order->getId()] = [
                OrderInterface::INCREMENT_ID   => $order->getIncrementId(),
                OrderInterface::STORE_ID       => $order->getStoreId(),
                OrderInterface::CUSTOMER_EMAIL => $order->getCustomerEmail(),
                OrderInterface::CREATED_AT     => $order->getCreatedAt(),
                OrderInterface::STATUS         => $order->getStatus(),
                OrderInterface::GRAND_TOTAL    => $order->getGrandTotal(),
                'full_line'                    => "[{$order->getIncrementId()}] - {$order->getEntityId()} - Store: {$order->getStoreId()} - {$order->getCustomerEmail()} - Total: {$order->getGrandTotal()} - {$order->getStatus()} - {$order->getCreatedAt()}",
                'link'                         => $this->_backendUrl
                    ->getUrl('sales/order/view', ['order_id' => $order->getId()]),
            ];
        }

        return $collectionResult;
    }

    private function getCustomerCollection(string $query, string $searchKey = ''): array
    {
        $attributeToFilter = $searchKey ? [$searchKey] : self::CUSTOMER_COLLECTION_FIELDS_TO_FILTER;
        $filters           = $this->getFilters($attributeToFilter, $query);
        $collection        = $this->customerRepositoryInterface->getList(
            $this->getSearchCriteria($filters))->getItems();

        $collectionResult = [];
        /** @var CustomerInterface $customer */
        foreach ($collection as $customer) {
            $collectionResult[$customer->getId()] = [
                CustomerInterface::ID         => $customer->getId(),
                CustomerInterface::FIRSTNAME  => $customer->getFirstname(),
                CustomerInterface::LASTNAME   => $customer->getLastname(),
                CustomerInterface::EMAIL      => $customer->getEmail(),
                CustomerInterface::CREATED_AT => $customer->getCreatedAt(),
                'full_line'                   => "[{$customer->getId()}] {$customer->getFirstname()} {$customer->getLastname()}, {$customer->getEmail()}, DOB: {$customer->getDob()}, Account created: {$customer->getCreatedAt()}",
                'link'                        => $this->_backendUrl
                    ->getUrl('customer/index/view', ['entity_id' => $customer->getId()]),
            ];
        }

        return $collectionResult;
    }

    private function getCmsBlockCollection(string $query, string $searchKey = ''): array
    {
        $attributeToFilter = $searchKey ? [$searchKey] : self::CMS_BLOCK_COLLECTION_FIELDS_TO_FILTER;
        $filters           = $this->getFilters($attributeToFilter, $query);
        $collection        = $this->blockRepository->getList($this->getSearchCriteria($filters))->getItems();

        $collectionResult = [];
        /** @var BlockInterface $block */
        foreach ($collection as $block) {
            $startPos = ($query !== '') ? (int) strpos($block->getContent(), $query) - 60 : 0;
            $content  = ($searchKey === BlockInterface::CONTENT)
                ? substr($block->getContent(), $startPos, 120)
                : substr($block->getContent(), 0, 60);

            $collectionResult[$block->getId()] = [
                BlockInterface::BLOCK_ID   => $block->getId(),
                BlockInterface::IDENTIFIER => $block->getIdentifier(),
                BlockInterface::TITLE      => $block->getTitle(),
                BlockInterface::CONTENT    => $block->getContent(),
                'full_line'                => "[{$block->getId()}] - {$block->getIdentifier()} - {$block->getTitle()} - <pre>{$this->escaper->escapeHtml($content)}</pre>",
                'link'                     => $this->_backendUrl
                    ->getUrl('cms/block/edit', ['block_id' => $block->getId()]),
            ];
        }

        return $collectionResult;
    }

    private function getCmsPageCollection(string $query = '', string $searchKey = ''): array
    {
        $attributeToFilter = $searchKey ? [$searchKey] : self::CMS_PAGE_COLLECTION_FIELDS_TO_FILTER;
        $filters           = $this->getFilters($attributeToFilter, $query);
        $collection        = $this->pageRepository->getList($this->getSearchCriteria($filters))->getItems();

        $collectionResult = [];
        /** @var PageInterface $page */
        foreach ($collection as $page) {
            $startPos = ($query !== '') ? (int) strpos($page->getContent(), $query) - 60 : 0;
            $content  = ($searchKey === PageInterface::CONTENT)
                ? substr($page->getContent(), $startPos, 120)
                : substr($page->getContent(), 0, 60);

            $collectionResult[$page->getId()] = [
                PageInterface::PAGE_ID    => $page->getId(),
                PageInterface::IDENTIFIER => $page->getIdentifier(),
                PageInterface::TITLE      => $page->getTitle(),
                PageInterface::CONTENT    => $page->getContent(),
                'full_line'               => "[{$page->getId()}] - {$page->getIdentifier()} - {$page->getTitle()} - <pre>{$this->escaper->escapeHtml($content)}</pre>",
                'link'                    => $this->_backendUrl
                    ->getUrl('cms/block/edit', ['block_id' => $page->getId()]),
            ];
        }

        return $collectionResult;
    }

    private function getFilters(array $attributeToFilter, string $query): array
    {
        $filters = [];
        foreach ($attributeToFilter as $attribute) {
            $filters[] = $this->filterBuilder
                ->setField($attribute)
                ->setConditionType('like')
                ->setValue("%{$query}%")
                ->create();
        }

        return $filters;
    }

    private function getSearchCriteria(array $filters): SearchCriteria
    {
        $this->searchCriteriaBuilder->addFilters($filters);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchCriteria->setPageSize(10);

        return $searchCriteria;
    }
}

