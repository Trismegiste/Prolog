father(anakin, luke).
father(medichlorian, anakin).
mother(shmi, anakin).
mother(padme, luke).
mother(ruwee, padme).
father(jobal, padme).
mother(padme, leia).
father(anakin, leia).

grandfather(X, Y) :- father(X, Z) , father(Z,Y).
grandfather(X, Y) :- father(X, Z) , mother(Z,Y).

grandmother(X, Y) :- mother(X, Z) , mother(Z,Y).
grandmother(X, Y) :- mother(X, Z) , father(Z,Y).

parent(X, Y) :- father(X, Y).
parent(X, Y) :- mother(X, Y).

brother(X, Y) :- parent(Z, X) , parent(Z, Y), X != Y.

equal(X, X).
