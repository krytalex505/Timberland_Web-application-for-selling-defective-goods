<?php
require '../modules/db_connection.php';
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ===== ОБРАБОТКА AJAX-ЗАПРОСА ДЛЯ СТАТИСТИКИ =====
if (isset($_GET['action']) && $_GET['action'] == 'getStats') {
    header('Content-Type: application/json');
    
    $totalResult = $connection->query("SELECT COUNT(*) as total FROM kronospan");
    $total = $totalResult->fetch_assoc()['total'];
    
    // Активные записи (где заполнена номенклатура)
    $activeResult = $connection->query("SELECT COUNT(*) as active FROM kronospan WHERE Nomenclature IS NOT NULL AND Nomenclature != ''");
    $active = $activeResult->fetch_assoc()['active'];
    
    echo json_encode([
        'success' => true,
        'total' => (int)$total,
        'active' => (int)$active
    ]);
    exit;
}

// ======= СОЗДАНИЕ ЗАПИСИ =======
if (isset($_POST['create'])) {
    // Только 6 полей
    $Nomenclature = $_POST['Nomenclature'] ?? '';
    $DecorNumber = $_POST['DecorNumber'] ?? '';
    $Character = $_POST['Character'] ?? '';
    $Complaint = $_POST['Complaint'] ?? '';
    $Size = $_POST['Size'] ?? '';
    $Basic = $_POST['Basic'] ?? '';

    // Остальные поля - пустые значения
    $Group = '';
    $Unit = '';
    $accounting = '';
    $Sheets = '';
    $Cost = '';
    $Date = null;
    $FromWho = '';
    $DateComplaint = '';
    $Percent = '';
    $Compensation = '';
    $Sum = '';

    $imagePaths = [];

    // Загрузка изображений
    if (isset($_FILES['ImagePath']) && is_array($_FILES['ImagePath']['name'])) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        foreach ($_FILES['ImagePath']['name'] as $key => $name) {
            if ($_FILES['ImagePath']['error'][$key] === UPLOAD_ERR_OK) {
                $imagePath = uniqid('img_', true) . '_' . basename($name);
                $uploadFile = $uploadDir . $imagePath;
                if (move_uploaded_file($_FILES['ImagePath']['tmp_name'][$key], $uploadFile)) {
                    $imagePaths[] = $imagePath;
                } else {
                    die("Ошибка загрузки изображения: " . $name);
                }
            }
        }
    }

    $stmt = $connection->prepare("INSERT INTO `kronospan` 
        (Nomenclature, DecorNumber, `Character`, `Complaint`, `Size`, `Basic`,
         `Group`, `Unit`, `accounting`, `Sheets`, `Cost`, `Date`, `FromWho`, `DateComplaint`, `Percent`, `Compensation`, `Sum`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("sssssssssssssssss", 
        $Nomenclature, $DecorNumber, $Character, $Complaint, $Size, $Basic,
        $Group, $Unit, $accounting, $Sheets, $Cost, $Date, $FromWho, $DateComplaint, $Percent, $Compensation, $Sum);

    if ($stmt->execute()) {
        $kronospan_id = $stmt->insert_id;

        if (!empty($imagePaths)) {
            $imgStmt = $connection->prepare("INSERT INTO images_kronospan (kronospan_id, image_path) VALUES (?, ?)"); 
            foreach ($imagePaths as $path) {
                $imgStmt->bind_param("is", $kronospan_id, $path);
                $imgStmt->execute();
            }
            $imgStmt->close();
        }

        header("Location: ../List/KronospanTable.php");
        exit();
    } else {
        die("Ошибка создания записи: " . $stmt->error);
    }
}

// ======= УДАЛЕНИЕ =======
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $imageQuery = $connection->query("SELECT image_path FROM images_kronospan WHERE kronospan_id = $id");
    if ($imageQuery) {
        while ($img = $imageQuery->fetch_assoc()) {
            $imagePath = '../uploads/' . $img['image_path'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
    }
    
    $connection->query("DELETE FROM images_kronospan WHERE kronospan_id = $id");
    $connection->query("DELETE FROM kronospan WHERE id = $id");

    header("Location: ../List/KronospanTable.php");
    exit;
}

// ======= РЕДАКТИРОВАНИЕ =======
if (isset($_POST['edit'])) {
    if (!isset($_POST['Id']) || !is_numeric($_POST['Id'])) {
        die("Ошибка: Некорректный ID.");
    }

    $id = intval($_POST['Id']);
    
    // Только 6 полей
    $Nomenclature = $_POST['Nomenclature'] ?? '';
    $DecorNumber = $_POST['DecorNumber'] ?? '';
    $Character = $_POST['Character'] ?? '';
    $Complaint = $_POST['Complaint'] ?? '';
    $Size = $_POST['Size'] ?? '';
    $Basic = $_POST['Basic'] ?? '';

    // Обновляем только эти 6 полей
    $query = "UPDATE `kronospan` SET 
        Nomenclature = ?, 
        DecorNumber = ?, 
        `Character` = ?, 
        `Complaint` = ?, 
        `Size` = ?, 
        `Basic` = ? 
        WHERE Id = ?";
    
    $stmt = $connection->prepare($query);
    $stmt->bind_param("ssssssi", $Nomenclature, $DecorNumber, $Character, $Complaint, $Size, $Basic, $id);

    // Загрузка новых изображений
    if (isset($_FILES['ImagePath']) && is_array($_FILES['ImagePath']['name'])) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        foreach ($_FILES['ImagePath']['name'] as $key => $name) {
            if ($_FILES['ImagePath']['error'][$key] === UPLOAD_ERR_OK) {
                $imagePath = time() . '_' . basename($name);
                $uploadFile = $uploadDir . $imagePath;
                if (move_uploaded_file($_FILES['ImagePath']['tmp_name'][$key], $uploadFile)) {
                    $imgStmt = $connection->prepare("INSERT INTO images_kronospan (kronospan_id, image_path) VALUES (?, ?)");     
                    $imgStmt->bind_param("is", $id, $imagePath);
                    $imgStmt->execute();
                    $imgStmt->close();
                }
            }
        }
    }

    if ($stmt->execute()) {
        header("Location: ../List/KronospanTable.php");
        exit();
    } else {
        die("Ошибка обновления: " . $stmt->error);
    }
}
?>