male(luke).
male(anakin).

not(Call) :- call(Call), !, fail.
not(Call).

female(X) :- not(male(X)).

determinant(la).
determinant(le).
nom(souris).
nom(chat).
adjectif(blanc).
adjectif(rouge).
adjectif(blanche).
genre(la,feminin).
genre(le,masculin).
genre(souris,feminin).
genre(chat,masculin).
genre(blanc,masculin).
genre(blanche,feminin).
genre(rouge,_).

accord(X, Y) :- genre(X,Z), genre(Y, Z).
sn(X, Y) :- determinant(X), nom(Y), accord(X, Y).
sn(X, Y) :- nom(X), adjectif(Y), accord(X, Y).
p(snm(determinant(X), nom(Y), G), X, Y) :- sn(X, Y), genre(X, G).

factorial(0, 1).
factorial(N, X) :- N > 0, N1 is N - 1, factorial(N1, P), X is N * P.

qsort([], []).
qsort([X|Rest], Answer) :- partition(X, Rest, [], Before, [], After), qsort(Before, Bsort), qsort(After, Asort), append(Bsort, [X | Asort], Answer).

partition(X, [], Before, Before, After, After).
partition(X, [Y | Rest], B, [Y | Brest], A, Arest) :- Y <= X, partition(X, Rest, B, Brest, A, Arest).
partition(X, [Y | Rest], B, Brest, A, [Y | Arest]) :- Y > X, partition(X, Rest, B, Brest, A, Arest).

append([], Z, Z).
append([A|B], Z, [A|ZZ]) :- append(B, Z, ZZ).

reverse([], []).
reverse([A|B], Z) :- reverse(B, Brev), append(Brev, [A], Z).

length([], 0).
length([H|T], N) :- length(T, M), N is M + 1.
