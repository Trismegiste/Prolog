mouv(0, A, _ , C) :- !.
mouv(N, A, B, C) :-  K is N-1, mouv(K, A, C, B), write('transport de '), write(A), write(' sur '), write(C), nl, mouv(K, B, A, C).
hanoi(N) :-  mouv(N, gauche, milieu, droite).