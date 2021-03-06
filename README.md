# Cosmos

*A library for representing and manipulating PHP class names.*

[![Build Status]](http://travis-ci.org/eloquent/cosmos)
[![Test Coverage]](http://eloquent-software.com/cosmos/artifacts/tests/coverage/)

## Installation

Available as [Composer](http://getcomposer.org/) package
[eloquent/cosmos](https://packagist.org/packages/eloquent/cosmos).

## What is Cosmos?

Cosmos is a library for representing and manipulating PHP class names. It
includes a `ClassName` object, as well as tools to help in resolving class names
against a set of `namespace` and `use` statements.

## ClassName object

This object is designed to represent any valid PHP class or namespace name.
There are two ways to create a new `ClassName` object. From a string or from an
array representing the parts of the class name, or 'atoms'. `ClassName` objects
cannot be instatiated directly.

The two methods are demostrated below:

```php
use Eloquent\Cosmos\ClassName;

$earth = ClassName::fromString('\MilkyWay\SolarSystem\Earth');
$mars = ClassName::fromAtoms(array('MilkyWay', 'SolarSystem', 'Mars'), true);
```

Note the second parameter in the `fromAtoms()` method. This boolean value
determines whether the class name is absolute (starts with a namespace
separator).

From here the `ClassName` object can be used to manipulate the class name or
extract information about its constituent parts.

### ClassName::atoms()

Returns the parts of the class name as an array of strings.

### ClassName::isAbsolute()

Returns boolean true if the class name is absolute (begins with a namespace
separator) or boolean false if it does not.

### ClassName::isShortName()

Returns boolean true if the class name is not absolute and has only one atom. If
either of these conditions is not met, false is returned.

### ClassName::isEqualTo(ClassName $className)

Returns boolean true if the supplied class name is exactly equal to this class
name. The supplied class name must have identical atoms and match the
absolute-ness of this class name.

### ClassName::isRuntimeEquivalentTo(ClassName $className)

Returns boolean true if the supplied class name is equivalent to this class
name in a runtime context. The supplied class name must have identical atoms but
absolute and relative class names are treated identically by PHP at runtime.

### ClassName::join(ClassName $className)

Appends `$className` to the end of this class name and returns the result as a
new `ClassName` object. Note that absolute class names cannot be joined.

### ClassName::joinAtoms($atom, ...)

Appends the supplied atoms to the end of this class name and returns the result
as a new `ClassName` object.

### ClassName::joinAtomsArray(array $atoms)

Appends the supplied array of atoms to the end of this class name and returns
the result as a new `ClassName` object.

### ClassName::hasParent()

Returns boolean true if the class name has a parent namespace, or boolean false
if it does not.

### ClassName::parent()

Returns the parent namespace of this class name as a new `ClassName` object.

### ClassName::shortName()

Returns the last atom of this class name as a new `ClassName` object.

### ClassName::toAbsolute()

Returns the absolute version of this class name as a `ClassName` object. If this
class name is already absolute, it will simply return itself.

### ClassName::toRelative()

Returns the relative version of this class name as a `ClassName` object. If this
class name is already relative, it will simply return itself.

### ClassName::hasDescendant(ClassName $className)

Returns boolean true if this class name is one of the parent namespaces of
`$className` or boolean false if it is not.

### ClassName::stripNamespace(ClassName $namespaceName)

Strips `$namespaceName` from this class name and returns the result as a new,
`ClassName` object relative to the supplied namespace name.

### ClassName::exists($useAutoload = true)

Returns boolean true if the class name exists. Note that this does not take into
account if the class name is absolute or relative. The `$useAutoload` parameter
can be specified to prevent autoloading if necessary.

### ClassName::string()

Returns a string representation of this class name. `ClassName` also implements
`__toString()` which simply returns the result of this method.

## Class name resolver

To use the class name resolver, first create a new resolver to represent the set
of `namespace` and `use` statements to resolve against.

The first parameter represents the `namespace` statement. It must be supplied as
a fully-qualified `ClassName` object. The second parameter is an array of tuples
representing the `use` statements.

If a 1-tuple is supplied, it represents a `use` statement without an `as`
clause. A 2-tuple represents a use statement with an attached `as` clause. The
first element of the tuple is always a fully-qualified `ClassName` object.
If present, the second element must be a short `ClassName` object. That is, one
without any namespace separators.

```php
use Eloquent\Cosmos\ClassName;
use Eloquent\Cosmos\ClassNameResolver;

$resolver = new ClassNameResolver(
    ClassName::fromString('\MilkyWay\SolarSystem'), // namespace
    array(
        array(
            ClassName::fromString('\MilkyWay\AlphaCentauri\ProximaCentauri'), // use
        ),
        array(
            ClassName::fromString('\Andromeda\GalacticCenter'), // use
            ClassName::fromString('Andromeda'), // as
        ),
    )
);
```

The above resolver is analogous to the following PHP code:

```php
namespace MilkyWay\SolarSystem;

use MilkyWay\AlphaCentauri\ProximaCentauri;
use Andromeda\GalacticCenter as Andromeda;
```

The created resolver can now be used to determine the canonical version of any
class name. Note that in the example below, `ClassName` objects are returned,
not plain strings.

```php
echo $resolver->resolve(ClassName::fromString('Earth'));
// outputs '\MilkyWay\SolarSystem\Earth'

echo $resolver->resolve(ClassName::fromString('ProximaCentauri'));
// outputs '\MilkyWay\AlphaCentauri\ProximaCentauri'

echo $resolver->resolve(ClassName::fromString('Andromeda'));
// outputs '\Andromeda\GalacticCenter'

echo $resolver->resolve(ClassName::fromString('TNO\Pluto'));
// outputs '\MilkyWay\SolarSystem\TNO\Pluto'

echo $resolver->resolve(ClassName::fromString('\Betelgeuse'));
// outputs '\Betelgeuse'
```

<!-- references -->
[Build Status]: https://raw.github.com/eloquent/cosmos/gh-pages/artifacts/images/icecave/regular/build-status.png
[Test Coverage]: https://raw.github.com/eloquent/cosmos/gh-pages/artifacts/images/icecave/regular/coverage.png
