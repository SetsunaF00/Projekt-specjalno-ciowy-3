<?php
session_start();

# Sprawdzamy, czy formularz został wysłany
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['button'])) {

    # Początek kodu dołączającego się do bazy danych
    include 'app/db.conn.php';

    # Pobieramy dane o pliku
    $file_name  = $_FILES['file']['name'];
    $tmp_name   = $_FILES['file']['tmp_name'];
    $error      = $_FILES['file']['error'];

    # Sprawdzamy, czy plik nie zawiera błędów
    if ($error === 0) {

        # Pobieramy rozszerzenie pliku
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);

        # Konwertujemy rozszerzenie na małe litery
        $file_ext_lc = strtolower($file_ext);

        # Dozwolone rozszerzenia plików
        $allowed_exts = array("jpg", "jpeg", "png", "pdf", "doc", "docx", "txt");

        # Sprawdzamy, czy rozszerzenie pliku jest dozwolone
        if (in_array($file_ext_lc, $allowed_exts)) {

            # Tworzymy unikalną nazwę pliku
            $new_file_name = uniqid('', true) . '.' . $file_ext_lc;

            # Ścieżka przesyłania pliku
            $file_upload_path = 'app/files/' . $new_file_name;

            # Przenosimy przesłany plik do katalogu ./uploads
            move_uploaded_file($tmp_name, $file_upload_path);

            try {
                # Dodajemy wpis do bazy danych
                $sql = "INSERT INTO files (file_name, uploaded_by, upload_time, conversation_id) VALUES (?, ?, NOW(), ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$new_file_name, $_SESSION['username'], $_POST['conversation_id']]);

                # Pobieramy identyfikator ostatnio dodanego pliku
                $file_id = $conn->lastInsertId();

                # Dodajemy informację o pliku do wiadomości w tabeli chats
                $sqlInsertChat = "INSERT INTO chats (from_id, to_id, message, created_at, file_id) VALUES (?, ?, ?, NOW(), ?)";
                $stmtInsertChat = $conn->prepare($sqlInsertChat);
                $stmtInsertChat->execute([$_SESSION['user_id'], $_POST['conversation_id'], "File: $new_file_name", $file_id]);

                # Dodaj ten echo lub var_dump
                echo "File added to the database successfully";

                # Sukces
                $sm = "File uploaded successfully";

                # Po obsłużeniu przesyłania pliku, wróć do czatu z zachowaniem bieżącej konwersacji
                header("Location: chat.php?user={$_POST['conversation_id']}&success=$sm");
                exit;

            } catch (PDOException $e) {
                # Błąd bazy danych
                $em = "Database error: " . $e->getMessage();

                # Po obsłużeniu błędu, wróć do czatu z zachowaniem bieżącej konwersacji
                header("Location: chat.php?user={$_POST['conversation_id']}&error=$em");
                exit;
            } catch (Exception $e) {
                # Inny błąd
                $em = "Error: " . $e->getMessage();

                # Po obsłużeniu błędu, wróć do czatu z zachowaniem bieżącej konwersacji
                header("Location: chat.php?user={$_POST['conversation_id']}&error=$em");
                exit;
            }

        } else {
            # Błąd - nieprawidłowe rozszerzenie pliku
            $em = "You can't upload files of this type";

            # Po obsłużeniu błędu, wróć do czatu z zachowaniem bieżącej konwersacji
            header("Location: chat.php?user={$_POST['conversation_id']}&error=$em");
            exit;
        }
    }
} else {
    # Handle case when the form was not submitted
    $conversation_id = "SELECT * FROM conversations
                        WHERE user_1=? OR user_2=?
                        ORDER BY conversation_id DESC";
    # Jeżeli formularz nie został wysłany, przekierowujemy na stronę z formularzem
    header("Location: chat.php?user=$conversation_id");
    exit;
}
?>
