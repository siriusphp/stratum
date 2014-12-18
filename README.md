#Sirius Stratum

[![Build Status](https://travis-ci.org/siriusphp/stratum.svg)](https://travis-ci.org/siriusphp/stratum)
[![Coverage Status](https://coveralls.io/repos/siriusphp/stratum/badge.png)](https://coveralls.io/r/siriusphp/stratum)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/siriusphp/stratum/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/siriusphp/stratum/?branch=master)

`Sirius\Stratum` is a library that will allow you to create extensible systems without having to use:

1. **Deep inheritance**. Inherintance can take you not that far when it comes to create extensible classes.
2. **Traits**. Traits overcome the limitations of inherintance but they require to define the structure up-front (you cannot add traits at run-time)
3. **Event systems**. Event systems require you to write lots of code for everything that requires interaction with the event system.
4. **Command busses**. Command busses allow you to change the behaviour of the system by having different functions respond to a command but composing these function is very easy. Think of a `getLatestPosts` command  that should retrieve the posts from database; you attach a callback to that command but later you want to implement a cache mechanism (ie: query the cache first and delegate to the previous callback if items are not in cache)
5. **AOP** (Aspect Oriented Programming). AOP is difficult because of the terminology and implementations are "heavy". http://go.aopphp.com/ is ones of such implementations

Having said that, I must warn you that the `Sirius\Stratum` is not flawless and has some trade-offs (very small).

## How does it work?

The project started with the question: how can you make a method of a class change its behaviour at run-time while preserving the inheritance? 
The obvious answer is wrapping an object inside other similar to an onion. From this point of view `Sirius\Stratum` is similar to the decorator pattern.
Imagine you have an ORM and you want to:

1. Log the calls (for benchmarking purposes)
2. Intercept exceptions to send notifications to the developer
3. Cache the results

```php
$orm = new CacheBehaviour(new LogBehaviour( new ExceptionNotifier(new ORM($dbConn))));
$orm->getLatestArticles(); 
```

This is how one would implement this using the decorator pattern. There are some limitations/issues with this approach

1. If at the bottom of the callstack the `ORM` object calls another of its methods (eg: `getLatestArticles()` calls `$this->executeQuery()`) the call to that method will not go through the layers above
2. Your decorators will have to implement the same interface of the decorated class. Granted this can be automated (ie: have a mechanism to automatically create the decorator class on disk)

Now enter **Sirius\Stratum**!

#### 1. Move your code into a "Base" class

```php

class ORMBase {
	
	function __construct($dbConn) {
		// whatever...
	}
	
	function getLatestArticles() {
		// query the database, map the results, return a collection
	}
}

class ORM extends ORMBase {
	use \Sirius\Stratum\DecoratableTrait;
}
```

**Note!** For PHP5.3 you'll need to copy&paste the code from the `\Sirius\Stratum\DecoratableTrait` trait yourself. Sorry!

#### 2. Decide which methods you want to be extendable/decoratable

```php
class ORM extends ORMBase {
	use \Sirius\Stratum\DecoratableTrait;
	
	function getLatestArticles() {
		return $this->executeDecoratedMethod(__FUNCTION__, func_get_args());
	}
}
```

#### 3. Instruct the Decorator Manager how you want to decorate the target class

```php
$manager = Sirius\Stratum\Manager::getInstance();
// I know... singleton... bad-practice... but this is a system-wide component and I think it's acceptable 
$manager->add('CacheBehaviour', 'ORM', 1000); // 1000 is the priority (not mandatory though)
$manager->add('LogBehaviour', 'ORM', 999); // 1000 is the priority (not mandatory though)
$manager->add('ExceptionNotifier', 'ORM', 998); // 1000 is the priority (not mandatory though)
```

Decorator classes must extend the `Sirius\Stratum\Decorator` class.

### That's it!


## FAQ?

#### 1. What are the trade-offs?

1. It is not a "pure" implementation of a pattern. [People complained](http://www.reddit.com/r/PHP/comments/2pke2j/aop_without_aop/) that it is either the Mediator pattern, the Chain of Responsibility pattern or a disquised Event pattern.
2. You have global state (ie: a singleton manager of the "strata" for each class). I consider the implementation to be similar to having traits (at the global level you define the traits) and I am not a purist.
3. Since the "decorators" are not required to implement the interface of the decorated object the library relies on `__call()` to pass the calls to the next layers, which come with a performance penalty. I will try to address this issue in the future though.

#### 2. Am I limited to classes for behaviour?

No. You can add an object as a decorator (the object will be cloned whenever needed by that class though, so keep that in mind) or a callback/function that returns a decorator.

```php
$manager->add($someAlreadyInstanciatedDecorator, 'ORM');
$manager->add($someFunctionOrCallbackThatReturnsADecorator, 'ORM');
```

#### 1. What happens if the decorators have the same priority?

They will be called in the reverse order they where added (ie: the last will wrap around the first).

```php
$manager->add('DecoratorA', 'DecoratedClass', 100);
$manager->add('DecoratorB', 'DecoratedClass', 100);

$decoratedClassObject->foo();
```

Assuming those are the only decorators `DecoratorB::foo()` will be called first which might call `DecoratorA::foo()` which might call `DecoratedClass::foo()`

#### 4. Can I add a decorator to an entire inheritance chain?

Given `ClassC` inherits from `ClassB` which inherits form `ClassA` at the moment there is no mechanism to add a decorator to `ClassA` and propagate to `ClassC`. This is tricky, but if you send a PR I will be happy to consider it.

#### 5. Can I add a decorator multiple times?

Yes. The manager doesn't check if a decorator is attached to a class so be careful.

#### 6. Can I still use events?

Yes. You can have a decorator that will emit events. It might even make your life easier (use the same decorator on ALL the classes where you need that).

```php

class EventsDecorator extends Sirius\Stratum\Decorator {

	function foo() {
		$this->emit('before_foo', func_get_args());
		return $this->callNext(__FUNCTION__, func_get_args());
	}

}
```

#### 7. Since I like decorators so much can I decorate a decorator?

Haven't tested it yet but I don't see why not. 
