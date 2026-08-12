<?php
require '../modules/db_connection.php';
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Получение статистики (общее количество и активные записи)
if (isset($_GET['action']) && $_GET['action'] == 'getStats') {
    header('Content-Type: application/json');
    
    $totalResult = $connection->query("SELECT COUNT(*) as total FROM byspan");
    $total = $totalResult->fetch_assoc()['total'];
    
    // Активные записи (где заполнена номенклатура)
    $activeResult = $connection->query("SELECT COUNT(*) as active FROM byspan WHERE Nomenclature IS NOT NULL AND Nomenclature != ''");
    $active = $activeResult->fetch_assoc()['active'];
    
    echo json_encode([
        'success' => true,
        'total' => (int)$total,
        'active' => (int)$active
    ]);
    exit;
}

// Обработка AJAX-запроса для получения данных с пагинацией
if (isset($_GET['action']) && $_GET['action'] == 'getData') {
    header('Content-Type: application/json');
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $search = isset($_GET['search']) ? $connection->real_escape_string($_GET['search']) : '';
    
    $offset = ($page - 1) * $limit;
    
    // Построение WHERE условия для поиска
    $where = "";
    if (!empty($search)) {
        $where = " WHERE Nomenclature LIKE '%$search%' 
                   OR DecorNumber LIKE '%$search%' 
                   OR Character LIKE '%$search%' 
                   OR Complaint LIKE '%$search%'
                   OR Size LIKE '%$search%'
                   OR Basic LIKE '%$search%'";
    }
    
    // Подсчет общего количества записей
    $countResult = $connection->query("SELECT COUNT(*) as total FROM byspan $where");
    $total = $countResult->fetch_assoc()['total'];
    
    // Получение данных для текущей страницы
    $query = "SELECT * FROM byspan $where ORDER BY id DESC LIMIT $offset, $limit";
    $result = $connection->query($query);
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        // Получаем изображения для каждой записи
        $imageQuery = $connection->query("SELECT image_path FROM images_byspan WHERE byspan_id = $id");
        $images = [];
        while ($img = $imageQuery->fetch_assoc()) {
            $images[] = '../uploads/' . $img['image_path'];
        }
        $row['images'] = $images;
        $data[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit
    ]);
    exit;
}
// ======= ФУНКЦИИ =======
function handleFlexibleDateInput($input) {
    $input = trim($input);
    if ($input === '') {
        return '';
    }
    if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $input)) {
        list($day, $month, $year) = explode('.', $input);
        if (!checkdate((int)$month, (int)$day, (int)$year)) {
            die("Ошибка: введена несуществующая дата ($input).");
        }
        $converted = "$year-$month-$day";
        $minDate = date('Y') . '-01-01';
        $maxDate = date('Y-m-d');
        if ($converted < $minDate || $converted > $maxDate) {
            die("Дата должна быть от " . date('d.m.Y', strtotime($minDate)) . " до " . date('d.m.Y') . ".");
        }
        return $converted;
    }
    if (preg_match('/\d{6,}/', $input)) {
        die("Неверный формат даты. Введите дату в формате ДД.ММ.ГГГГ или текст.");
    }
    return $input;
}

function validateDate($dateInput) {
    if (empty($dateInput)) {
        return null;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateInput)) {
        $convertedDate = $dateInput;
    } elseif (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $dateInput)) {
        list($day, $month, $year) = explode('.', $dateInput);
        $convertedDate = "$year-$month-$day";
    } else {
        die("Ошибка: неверный формат даты. Ожидается ДД.ММ.ГГГГ или ГГГГ-ММ-ДД.");
    }
    return $convertedDate;
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

    $stmt = $connection->prepare("INSERT INTO `byspan` 
        (Nomenclature, DecorNumber, `Character`, `Complaint`, `Size`, `Basic`,
         `Group`, `Unit`, `accounting`, `Sheets`, `Cost`, `Date`, `FromWho`, `DateComplaint`, `Percent`, `Compensation`, `Sum`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("sssssssssssssssss", 
        $Nomenclature, $DecorNumber, $Character, $Complaint, $Size, $Basic,
        $Group, $Unit, $accounting, $Sheets, $Cost, $Date, $FromWho, $DateComplaint, $Percent, $Compensation, $Sum);

    if ($stmt->execute()) {
        $byspan_id = $stmt->insert_id;

        if (!empty($imagePaths)) {
            $imgStmt = $connection->prepare("INSERT INTO images_byspan (byspan_id, image_path) VALUES (?, ?)"); 
            foreach ($imagePaths as $path) {
                $imgStmt->bind_param("is", $byspan_id, $path);
                $imgStmt->execute();
            }
            $imgStmt->close();
        }

        header("Location: ../List/BySpanTable.php");
        exit();
    } else {
        die("Ошибка создания записи: " . $stmt->error);
    }
}

// ======= УДАЛЕНИЕ =======
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $imageQuery = $connection->query("SELECT image_path FROM images_byspan WHERE byspan_id = $id");
    if ($imageQuery) {
        while ($img = $imageQuery->fetch_assoc()) {
            $imagePath = '../uploads/' . $img['image_path'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
    }
    
    $connection->query("DELETE FROM images_byspan WHERE byspan_id = $id");
    $connection->query("DELETE FROM byspan WHERE id = $id");

    header("Location: ../List/BySpanTable.php");
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
    $query = "UPDATE `byspan` SET 
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
                    $imgStmt = $connection->prepare("INSERT INTO images_byspan (byspan_id, image_path) VALUES (?, ?)");     
                    $imgStmt->bind_param("is", $id, $imagePath);
                    $imgStmt->execute();
                    $imgStmt->close();
                }
            }
        }
    }

    if ($stmt->execute()) {
        header("Location: ../List/BySpanTable.php");
        exit();
    } else {
        die("Ошибка обновления: " . $stmt->error);
    }
}
?>