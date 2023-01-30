# Quick Back-Office Search Bar

[![License: MIT](https://img.shields.io/github/license/sorciento/magento2-quickbo-search-bar.svg?style=flat-square)](./LICENSE)

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

## License

This project is licensed under the MIT License - see the [LICENSE](./LICENSE) details.

***That's all folks!***
