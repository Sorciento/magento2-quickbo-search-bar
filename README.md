# Quick Back-Office Search Bar

[![License: MIT](https://img.shields.io/github/license/sorciento/magento2-quickbo-search-bar.svg?style=flat-square)](./LICENSE)
[![Latest Stable Version](https://img.shields.io/packagist/v/sorciento/module-quickbo-search-bar.svg?style=flat-square)](https://packagist.org/packages/magento2-quickbo-search-bar.svg)
[![Packagist](https://img.shields.io/packagist/dt/sorciento/module-quickbo-search-bar.svg?style=flat-square)](https://packagist.org/packages/opengento/module-snowflake/stats)
[![Packagist](https://img.shields.io/packagist/dm/sorciento/module-quickbo-search-bar.svg?style=flat-square)](https://packagist.org/packages/opengento/module-snowflake/stats)

Add a quick search bar in back-office dashboard to look for products, orders, customers, CMS pages or blocks

- [Setup](#setup)
    - [Composer installation](#composer-installation)
    - [Setup the module](#setup-the-module)
- [License](#license)

## Setup

Magento 2 Open Source or Commerce edition is required.

### Composer installation

Run the following composer command:

```shell
composer require sorciento/magento2-quickbosearchbar
```

### Setup the module

Run the following magento command:

```shell
bin/magento setup:upgrade
```

### Enable the module

In the Back-office, go to `Stores > Configuration > Sorciento > Quick BO Search Bar` and set `Status` to `Enabled`.

Don't forget to clean caches if necessary.

## How to use

A "Quick search" area will appear in top of you back-office dashboard. 

Enter any data you wish to find through following fields. You can search a specific info by adding a filter separated by ":" (double-point character).

Search format: `{searched data}:{filter}`
Example : `MB:sku` 

* Product (in parentheses, the filter key)
    - entity_id (`prod`) 
    - sku (`sku`)
* Order
    - order_id (`inc`)
    - increment_id (`ord@`)
    - customer_email (`order`)
    - grand_total (`total`)
* Customer
    - entity_id (`cust`)
    - email (`cus@`)
    - first_name (`lname`)
    - last_name (`fname`)
    - date of birth (`dob`)
* CMS Page
    - page_id (`cmspid`)
    - identifier (`cmspkey`)
    - title (`cmspti`)
    - content (`cmspco`)
* CMS Block
    - block_id (`cmsbid`)
    - identifier (`cmsbkey`)
    - title (`cmsbti`)
    - content (`cmsbco`)

## License

This project is licensed under the MIT License - see the [LICENSE](./LICENSE) details.

***That's all folks!***
