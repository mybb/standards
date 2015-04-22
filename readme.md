# MyBB 2.0 Coding Standard
This repository contains the coding standard for MyBB 2.0. These files are supposed to be used as a standard for [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) and are run automatically against all repositories related to MyBB 2.0.

## Standard

PHP code must follow the [PSR-2](http://www.php-fig.org/psr/psr-2/) coding style guide. [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) will be ran against all contributions to ensure that code follows this standard. 

In addition to the PSR-2 standard, we have other standards and best practices that must be ahered to:

- All interface names MUST be suffixed with `Interface`. (e.g. `ForumInterface`).
- All abstract class names MUST be prefixed with `Abstract` (e.g. `AbstractForum`).
- All repository class names MUST be suffixed with `Repository` (e.g. `ForumRepository`).
- All factory class names MUST be suffixed with `Factory` (e.g. `ForumFactory`).
- The `Interface` suffix MUST take priority over other suffixes. (e.g. `ForumRepositoryInterface`, `ForumFactoryInterface`.
- Getters MUST be used when retrieving the property of a non-Eloquent object.
- Setters MUST be used when manipulating the property of a non-Eloquent object.
- Properties on an object SHOULD have `protected` or `private` visibility.

```php
/**
 * @property string magic
 */
class Foo
{
    /**
     * @var string
     */
    protected $bar;
    
    /**
     * @return string;
     */
    public function getBar()
    {
        return $this->bar;
    }
    
    /**
     * @param string $bar
     */
    public function setBar($bar)
    {
        $this->bar = $bar;
    }
    
    /**
     * @param string $name
     */
    public function __get($name)
    {
        return 'magic';
    }
}
```

- Methods with a return value and/or arguments MUST have a document block.
- Object properties MUST have a document block with `@var` tag denoting their type.
- Magic properties on an object MUST be declared in a doc block at the top of the class using the `@property` tag.
- Method arguments that are required MUST NOT have a default value.