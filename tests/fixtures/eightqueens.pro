solution([]).
solution([reine(X,Y)|Rs]) :- solution(Rs), element(Y,[1,2,3,4,5,6,7,8]), nonattaque(reine(X,Y),Rs).

nonattaque(Reine, []).
nonattaque(Reine, [R|Rs]) :- nondiagouligne(Reine, R), nonattaque(Reine, Rs).

nondiagouligne(reine(I,J), reine(I1,J1)) :- J != J1, N1 is I+J1, N2 is I1+J, N1 != N2, N3 is I+J, N4 is I1+J1, N3 != N4.

element(X, [X|_]).
element(X, [_|L]) :- element(X, L).

soluce([reine(1,_),reine(2,_),reine(3,_),reine(4,_),reine(5,_),reine(6,_),reine(7,_),reine(8,_)]).