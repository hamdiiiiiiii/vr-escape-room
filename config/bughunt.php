<?php

return [
    // When true, POST /api/sessions/{id}/results must carry the player's final "answer" for every puzzle
    // that has an answer key, and the server decides "solved". Default false so the current Unity build keeps working.
    'require_answer' => (bool) env('REQUIRE_ANSWER_CHECK', false),
];
