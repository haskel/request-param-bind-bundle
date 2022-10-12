## Query
`name=John&surname=Smith`
```php
class Controller 
{

    public function getPerson(#[FromQuery] string $name, #[FromQuery] string $surname)
    {
        //...
    }
}
```


`name=John&surname=Smith&middleName=Dude`
```php
class Person 
{
    public ?string $name;
    public ?string $surname;
    public ?string $middleName;
}

class Controller 
{

    public function getPerson(#[FromQuery] Person $person)
    {
        //...
    }
}
```


`filter[price][from]=10&filter[price][to]=300`
```php
class Filter 
{
    public ?PriceRange $price;
}

class PriceRange 
{
    public ?float $from;
    public ?float $to;
}

class Controller 
{

    public function filter(#[FromQuery] Filter $filter)
    {
        //...
    }
}
```

`page=3&itemsPerPage=100&filter[price][from]=10&filter[price][to]=300`

```php
class Filter 
{
    public ?PriceRange $price;
}

class PriceRange 
{
    public ?float $from;
    public ?float $to;
}

class Pagination 
{
    public int $page = 1;
    public int $itemsPerPage = 10;
}

class Controller 
{

    public function filter(#[FromQuery] Filter $filter, #[FromQuery] Pagination $pagination)
    {
        //...
    }
}
```

`filter[name][0]=location&filter[value][0]=California&filter[name][1]=maxPrice&filter[value][1]=300`

```php
class Filter 
{
    public string $name;
    public $value;
}

class Controller 
{

    public function filter(#[FromQuery] Filter ...$filters)
    {
        //...
    }
}
```


## Body
same as Query

## Header
## Cookie
## File

## Name Converter
