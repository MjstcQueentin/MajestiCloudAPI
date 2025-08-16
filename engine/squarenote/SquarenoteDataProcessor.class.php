<?php
require_once(__DIR__ . "/../GlobalDataProcessor.class.php");

class SquarenoteDataProcessor extends GlobalDataProcessor
{
    function __construct(string $user_uuid)
    {
        parent::__construct("squarenote", $user_uuid);

        // Assurez-vous que le répertoire des notes existe
        $stickyNotesPath = $this->path_to("/stickynotes");
        if (!is_dir($stickyNotesPath)) {
            mkdir($stickyNotesPath, 0777, true);
        }
    }

    /**
     * Obtenir la liste complète des notes de l'utilisateur.
     */
    public function getStickyNotes(): array
    {
        $directory = $this->path_to("/stickynotes");
        if (!is_dir($directory)) {
            return [];
        }

        $notes = [];
        $files = scandir($directory) or throw new Exception("Unable to read directory: $directory");

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $file_path = $directory . '/' . $file;

            if (is_file($file_path)) {
                $content = file_get_contents($file_path);
                if ($content !== false) {
                    // Lire le contenu du fichier XML
                    // On suppose que le nom du fichier est l'ID de la note
                    $xml = simplexml_load_string($content);
                    if ($xml === false) {
                        continue; // Ignore les fichiers qui ne sont pas des XML valides
                    }
                    if ($xml->IsDeleted == "true") {
                        continue; // Ignore les notes supprimées
                    }

                    $notes[] = [
                        'id' => strval($xml->ID),
                        'body' => strval($xml->Body)
                    ];
                }
            }
        }

        return $notes;
    }

    public function getStickyNote(string $id): ?string
    {
        $directory = $this->path_to("/stickynotes");
        $file_path = $directory . '/' . $id . '.xml';

        if (!file_exists($file_path)) {
            return null; // Note not found
        }

        $content = file_get_contents($file_path);
        if ($content === false) {
            return null; // Error reading file
        }

        $xml = simplexml_load_string($content);
        if ($xml === false) {
            return null; // Invalid XML
        }

        if ($xml->IsDeleted == "true") {
            return null; // Note is deleted
        }

        return $content;
    }

    public function setStickyNote(string $content): bool
    {
        // Decode the XML content and find the sticky note ID
        $xml = simplexml_load_string($content);
        if ($xml === false || empty($xml->ID)) {
            return false; // Invalid XML or missing ID
        }

        $id = (string)$xml->ID;
        $directory = $this->path_to("/stickynotes");
        $file_path = $directory . '/' . $id . '.xml';

        // Save the XML content to a file
        if (file_put_contents($file_path, $content) === false) {
            return false; // Error writing file
        }

        return true;
    }
}
