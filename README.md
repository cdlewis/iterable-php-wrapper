# Iterable PHP Wrapper

## Getting Started

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
