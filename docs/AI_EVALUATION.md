# BugHunt VR: how we evaluate the AI part

Goal: show with evidence (not opinion) that the AI features work and help students.
There are two kinds of evidence. Use both in the thesis.

## 1. Simulation (already built, needs no students)

| Command | What it shows |
|---|---|
| `php artisan learner:simulate` | The learner model predicts whether a student will solve the next puzzle better than a simple running average (Brier score: lower is better). |
| `php artisan learner:evaluate` | Choosing puzzles with the learner model (adaptive) teaches more concepts than a fixed order or random order, with a 95% confidence interval. |

Limit to state in the thesis: both are simulations. They show the mechanism works if students learn the way we assume. They are not proof that real students learn more.

## 2. Pilot with real students (needs 10 to 20 people)

### Protocol
1. Each participant does the **pre-test** (below), on paper or a form. 10 minutes.
2. They play 2 or 3 rooms, one session at a time, on separate days if possible.
3. They do the **post-test** (same questions in a different order, or a parallel version). 10 minutes.
4. They fill in the **survey**. 5 minutes.
5. Run `php artisan evaluation:export` and keep the CSV files (they are anonymous).

Optional fair comparison: half the group gets AI-chosen puzzles, half gets the normal order. If you only have one group, say so as a limitation.

### Pre/post test (6 questions, same concepts as the game)
Mark each question 1 or 0. Score = number correct out of 6.
1. A door opens for "10 or higher". Which test input is the best boundary test? (5, 10, 20)
2. Code says `if x > 10`, rule says "10 or higher". Which value shows the bug? 
3. Show a 5-line function with an off-by-one bug. Which line is wrong?
4. For "age 18 or over can vote", name two boundary values to test.
5. Why is testing 50 less useful than testing 10 for the rule above?
6. What is the difference between "higher than 10" and "10 or higher"?
(Write your own parallel versions with different numbers for the post-test.)

Report: mean pre, mean post, mean gain, and a paired t-test or Wilcoxon signed-rank test. With 10 to 20 people, also say the sample is small.

### Survey (1 = strongly disagree, 5 = strongly agree)
1. The puzzles felt right for my level.
2. The hints helped me when I was stuck.
3. The "Mistakes to fix" advice was useful.
4. I understood why my answer was wrong.
5. I would play again.
Instructor only:
6. The Class briefing helped me decide what to teach.
7. I approved most of the AI-written puzzles without changing them.

### Numbers the system gives you
`php artisan evaluation:export` prints and saves:
- average score gain from the first to the last session per student
- how many results have recorded wrong answers
- AI puzzles approved / rejected / pending and the **instructor approval rate** (a direct quality measure for the AI generator)

### AI puzzle quality checklist (for each generated puzzle, tick when approving)
- [ ] The story is clear and suitable for children
- [ ] The bug or the answer is really correct
- [ ] Only one answer is correct
- [ ] The hints help without giving the answer
- [ ] It is on the right concept and difficulty
Report: how many puzzles were generated, how many passed the automatic checks, how many an instructor approved.

## What to write about limits (be honest, it scores better)
- The simulation uses assumptions; real learning can differ.
- Small pilot, no long-term follow-up.
- The AI can still write a wrong puzzle, so an instructor approves every puzzle.
- The AI only ever sees concepts and mistakes, never names or emails.
