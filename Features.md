# Features

I have to cut it down to maintain readability and performance.

## Parameter as predicate

You can pass predicate as parameter, example :
```prolog
equal(X, X).
equal(molecule(carbon, oxygen), molecule(carbon, X)).
```

## Metalogic

There are some metalogic like :

* call
* cut a.k.a "!"
* assert
* retract (limited on last predicate)
* write/read

## Missing features

There are no findall nor bagof nor other shiny metalogic. Mainly for two reasons :

* Since it is a embedded DSL in PHP, I don't think it is really usefull
* I would have to break some speed optimizations in the WAM compiler
