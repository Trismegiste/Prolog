male(luke).
female(leia).
father(anakin, luke).

donothing(X) :- assert(X), fail.
remove(X) :- retract(X), fail.

unif1(F, P) :- bound(F), call(F(P)).
unif2(F, P1, P2) :- bound(F), call(F(P1, P2)).
