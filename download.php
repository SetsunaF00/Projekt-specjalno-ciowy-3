<?php
// Połączenie z bazą danych
include 'app/db.conn.php';

// Pobranie identyfikatora pliku z parametru URL
if (isset($_GET['file_id'])) {
    $fileId = $_GET['file_id'];

    // Pobranie informacji o pliku z bazy danych
    $sql = "SELECT file_name FROM files WHERE file_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$fileId]);
    $fileInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($fileInfo) {
        // Ścieżka do katalogu, w którym są przechowywane pliki
        $filePath = 'app/files/' . $fileInfo['file_name'];

        // Sprawdzenie, czy plik istnieje
        if (file_exists($filePath)) {
            // Ustawienia nagłówków do poprawnego pobierania pliku
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Length: ' . filesize($filePath));

            // Odczytanie pliku i wysłanie go do użytkownika
            readfile($filePath);
            exit;
        } else {
            echo 'Plik nie istnieje.';
        }
    } else {
        echo 'Nieprawidłowy identyfikator pliku.';
    }
} else {
    echo 'Brak podanego identyfikatora pliku.';
}
?>
