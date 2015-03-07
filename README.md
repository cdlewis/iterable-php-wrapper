# Iterable PHP Wrapper

[![Build Status](https://travis-ci.org/cdlewis/iterable-php-wrapper.svg?branch=master)](https://travis-ci.org/cdlewis/iterable-php-wrapper)
[![Coverage Status](https://coveralls.io/repos/cdlewis/iterable-php-wrapper/badge.svg?branch=master)](https://coveralls.io/r/cdlewis/iterable-php-wrapper?branch=master)

## Getting Started

[![Coverage Status](https://coveralls.io/repos/cdlewis/iterable-php-wrapper/badge.svg)](https://coveralls.io/r/cdlewis/iterable-php-wrapper)
Clone the repository
```
git clone https://github.com/cdlewis/iterable-php-wrapper.git
```

Include the wrapper
```
require_once( 'iterable-php-wrapper/iterable.php' );
```

Instantiate the class with your API key
```
$iterable = new Iterable( 'YOUR API KEY' );
```

## Examples

```
$iterable->list_subscribe( $list_id, array(
	array( 'email' => john@example.com ),
	array( 'email' => harry@example.com )
);
```
