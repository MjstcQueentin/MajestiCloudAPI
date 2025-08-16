<?php

require_once(__DIR__ . "/../../engine/squarenote/SquarenoteEngine.class.php");
$engine = new SquarenoteEngine(true);

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $id = $_GET['id'] ?? null;

    if (empty($id)) {
        // Pas d'ID fourni : on retourne toutes les notes de l'utilisateur
        $notes = $engine->getStickyNotes();
        $engine->echo_response([
            "status" => true,
            "notes" => $notes
        ], 200);
    } else {
        // ID fourni : on retourne la note correspondante
        $note = $engine->getStickyNote($id);

        if ($note === null) {
            $engine->echo_response([
                "status" => false,
                "message" => "Note not found."
            ], 404);
        }

        header("Content-Type: application/xml");
        echo $note;
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Si le contenu n'est pas du XML, on rejette la requête
    if (empty($_SERVER["CONTENT_TYPE"]) || $_SERVER["CONTENT_TYPE"] != "application/xml") {
        $engine->echo_response([
            "status" => false,
            "message" => "This endpoint requires XML content."
        ], 415);
    }

    $raw_content = file_get_contents("php://input");
    if (empty($raw_content)) {
        $engine->echo_response([
            "status" => false,
            "message" => "Empty content."
        ], 400);
    }

    if (!$engine->setStickyNote($raw_content)) {
        $engine->echo_response([
            "status" => false,
            "message" => "Failed to create or update the sticky note."
        ], 500);
    }

    $engine->echo_response([
        "status" => true,
        "message" => "Sticky note created or updated successfully.",
        "content" => $raw_content
    ], 201);
} else {
    $engine->echo_response([
        "status" => false,
        "message" => "This endpoint requires GET or POST."
    ], 405);
}
