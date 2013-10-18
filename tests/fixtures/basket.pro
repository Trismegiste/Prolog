% This is an dummy example of rules for a basket in e-commerce

% recursion for summing the basket
total([], 0).
total([P|Basket], T) :- price(P, PP), total(Basket, ST), T is ST + PP.

% The set <First arg> contains the element <Second arg>
contains([S|_], S).
contains([S|SS], I) :- contains(SS, I).

% The set <First arg> includes the subset <Second arg>
includes(S, []).
includes(S, [A|B]) :- contains(S, A), includes(S, B).

% the catalog. Of course in a real example, you have to narrow this set
price(wow, 60).
price(diablo3, 70).
price(starwars_box, 60).
price(ultrabook, 1200).
price(bike, 650).
price(geforce, 550).
price(tyre, 25).

% some rules for gift
gift(B, tyre) :- contains(B, bike).
gift(B, lightsaber) :- contains(B, starwars_box).
gift(B, keychain) :- total(B, X), X > 200.
gift(B, life) :- includes(B, [diablo3, wow]).

% some rules for discount
discount(B, T) :- total(B, X), X > 1500, T is X / 30, !.
discount(B, 50) :- total(B, X), X > 1000, !.
discount(B, 20) :- includes(B, [geforce, wow]), !.
discount(B, 5) :- total(B, X), X > 100.
