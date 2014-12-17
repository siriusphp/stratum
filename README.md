#Sirius-Decorators

`Sirius\Decorators` is a library that will allow you to create extensible systems without having to use:

1. **Deep inheritance**. Inherintance can take you not that far when it comes to create extensible classes.
2. **Traits**. Traits overcome the limitations of inherintance but they don't have an inheritance mechanism and you can't have multiple levels of traits on the same object (ie: on class `SomeClass` that uses `TraitA` and `TraitB` which both implement method `foo()` you cannot make `TraitA::foo()` call `TraitB::foo()`)
3. **Event systems**. Although very good at what they do event systems require you to write lots of code for everything that requires interaction with the event system.
4. **Command busses**. Command busses allow you to change the behaviour of the system by having different functions respond to a command but composing these function is very easy. Think of a `getLatestPosts` command  that should retrieve the posts from database; you attach a callback to that command but later you want to implement a cache mechanism (ie: query the cache first and delegate to the previous callback if items are not in cache)
5. **AOP** (Aspect Oriented Programming). AOP is difficult because of the terminology and implementations are "heavy". http://go.aopphp.com/ is on of such implementations

Having said that, I must warn you that the `Sirius\Decorators` is not flawless and has some trade-offs (very small).

## How does it work?

The project started with the question: how can you make a method of a class change its behaviour at run-time while preserving the inheritance? 
The obvious answer is wrapping an object inside other similar to an onion.
Imagine you have an ORM and you want to:

1. Log the calls (for benchmarking purposes)
2. Intercept exceptions to send notifications to the developer
3. Cache the results

```php
$orm = new CacheBehaviour(new LogBehaviour( new ExceptionNotifier(new ORM($dbConn))));
$orm->getLatestArticles(); 
```

I'm sure you can see the problems with such an implementation. Now enter **Sirius\Decorators**!

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

class ORM extends ORM {
	use \Sirius\Decorators\DecoratableTrait;
}
```

**Note!** For PHP5.3 you'll need to copy&paste the code from the `\Sirius\Decorators\DecoratableTrait` trait yourself. Sorry!

#### 2. Decide which methods you want to be extendable/decoratable

```php
class ORM extends ORM {
	use \Sirius\Decorators\DecoratableTrait;
	
	function getLatestArticles() {
		return $this->executeDecoratedMethod(__FUNCTION__, func_get_args());
	}
}
```

#### 3. Instruct the Decorator Manager how you want to decorate the target class

```php
$manager = Sirius\Decorator\Manager::getInstance();
// I know... singleton... bad-practice... but this is a system-wide component and I think it's acceptable 
$manager->add('CacheBehaviour', 'ORM', 1000); // 1000 is the priority (not mandatory though)
$manager->add('LogBehaviour', 'ORM', 999); // 1000 is the priority (not mandatory though)
$manager->add('ExceptionNotifier', 'ORM', 998); // 1000 is the priority (not mandatory though)
```

Decorator classes must extend the `Sirius\Decorators\Decorator` class.

### That's it!


## FAQ?

#### 1. Am I limited to classes for behaviour?

No. You can add an object to as a decorator (the object will be cloned whenever needed by that class though) or a callback/function that returns a decorator

```php
$manager->add($someAlreadyInstanciatedDecorator, 'ORM');
$manager->add($someFunctionOrCallbackThatReturnsADecorator, 'ORM');
```

#### 2. What happens if the decorators have the same priority?

They will be called in the reverse order they where added (ie: the last will wrap around the first).

```php
$manager->add('DecoratorA', 'DecoratedClass', 100);
$manager->add('DecoratorB', 'DecoratedClass', 100);

$decoratedClassObject->foo();
```

Assuming those are the only decorators `DecoratorB::foo()` will be called first which might call `DecoratorA::foo()` which might call `DecoratedClass::foo()`

#### 3. Can I add a decorator to an entire inheritance chain?

Given `ClassC` inherits from `ClassB` which inherits form `ClassA` at the moment there is no mechanism to add a decorator to `ClassA` and propagate to `ClassC`. This is tricky, but if you send a PR I will be happy to consider it.

#### 4. Can I add a decorator multiple times?

Yes. The manager doesn't check if a decorator is attached to a class so be careful.

#### 5. Can I still use events?

Yes. You can have a decorator that will emit events. It might even make your life easier (use the same decorator on ALL the classes where you need that).

```php

class EventsDecorator extends Sirius\Decorators\Decorator {

	function foo() {
		$this->emit('before_foo', func_get_args());
		return $this->callNext(__FUNCTION__, func_get_args();
	}

}
```

#### 6. Since I like decorators so much can I decorate a decorator?

Haven't tested it yet but I don't see why not. 