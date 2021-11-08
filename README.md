# Dummy data generator on EC-CUBE2

The `eccube:fixtures:generate` command generate of dummy data.

## Installation

```
composer require ec-cube2/cli --dev
composer require nanasess/eccube2-fixture-generator --dev
```

## Usage

```
php data/vendor/bin/eccube eccube:fixtures:generate [options]
```

## Options

```
      --with-locale=WITH-LOCALE  Set to the locale. [default: "ja_JP"]
      --products=PRODUCTS        Number of Products. [default: 100]
      --orders=ORDERS            Number of Orders. [default: 10]
      --customers=CUSTOMERS      Number of Customers. [default: 100]
  -h, --help                     Display this help message
  -q, --quiet                    Do not output any message
  -V, --version                  Display this application version
      --ansi                     Force ANSI output
      --no-ansi                  Disable ANSI output
  -n, --no-interaction           Do not ask any interactive question
  -v|vv|vvv, --verbose           Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```
