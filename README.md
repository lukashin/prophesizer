## Prophesizer

Generates a [Prophecy](https://github.com/phpspec/prophecy) 
[Dummy](https://github.com/phpspec/prophecy#dummies) for class/interface 
you want to prophesize

Prophesizer inspects `@docComment` blocks in your code and creates 
method prophecies according to specified `@return`, `@throws` and `@param` docTags

`@return` and `@throws` samples generated in `->will(){...}` closure

`@param int $id` used to check parameters with `Argument::type('int')` etc.

## Example
```
public function someUnitTest()
{
    $service = $this->prophesize('Service\SomeService'); ///
}    
```
Press `Ctrl+S` and line with `///` will be transformed into
```
public function someUnitTest()
{
    // $service = $this->prophesize('Service\SomeService');
    $service = $this->getSomeServiceDouble(); // todo: edit predictions!
}
```

```
/**
 * @return \Service\SomeService
 */
private function getSomeServiceDouble()
{
    $someServiceProphecy = $this->prophesize('Service\SomeService');
    
    /** @noinspection PhpUndefinedMethodInspection */
    $someServiceProphecy
        ->createSomething(
            Argument::type('int'),
            Argument::type('string'),
            Argument::allOf(Argument::type('DateTime'), Argument::type('null'))
        )
        ->will(function (array $args) {
            // todo: modify generated method double
            // throw new \SomeService\Exception('Thrown in SomeService::createSomething()');
            // return 999;
        })
        ->shouldBeCalled();
        //->shouldNotBeCalled();

    return $someServiceProphecy->reveal();
}

```
## Why?

I'm tired of manual Dummies writing 

## Installation

`composer require prophesizer/prophesizer`

**PhpStorm** Settings / Tools / File Watchers / `+`

```
-Watcher-
Name: prophesizer
-Options-
Show console : Error
[ ] Immediate file synchronization
[Watcher Settings]
File type    : PHP
Scope        : VCS / Changed Files
Prograp      : 
Arguments    : $FilePath$ $ProjectFileDir$ 
[x] Create output file from stdout 
```

[PhpStorm / Edit watcher dialog](https://www.jetbrains.com/help/phpstorm/2016.2/new-watcher-dialog.html)

<img src="https://raw.githubusercontent.com/lukashin/prophesizer/master/resources/images/prophesizer-watcher-setup.png" alt="prophesizer-watcher-setup" />
