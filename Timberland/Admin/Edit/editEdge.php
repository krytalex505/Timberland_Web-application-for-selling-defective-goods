<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../modules/db_connection.php';
$connection->set_charset("utf8mb4");
// Проверяем, что параметр 'edit' передан в URL
if (!isset($_GET['edit']) || !is_numeric($_GET['edit'])) {
    die("Ошибка: Некорректный ID.");
}
$id = intval($_GET['edit']);

// Получаем данные из базы по ID
$stmt = $connection->prepare("SELECT * FROM edge WHERE Id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Ошибка: Запись не найдена.");
}

$row = $result->fetch_assoc();

// Удаление изображения
if (isset($_GET['delete_image']) && is_numeric($_GET['delete_image'])) {
    $image_id = intval($_GET['delete_image']);

    $imageQuery = $connection->prepare("SELECT image_path FROM images_edge WHERE id = ?"); 
    $imageQuery->bind_param("i", $image_id);
    $imageQuery->execute();
    $imageResult = $imageQuery->get_result();
    
    if ($imageResult->num_rows > 0) {
        $image = $imageResult->fetch_assoc();
        $image_path = $image['image_path'];
        
        if (file_exists("../uploads/" . $image_path)) {
            unlink("../uploads/" . $image_path);
        }

        $deleteImageQuery = $connection->prepare("DELETE FROM images_edge WHERE id = ?");
        $deleteImageQuery->bind_param("i", $image_id);
        $deleteImageQuery->execute();

        header("Location: editEdge.php?edit=" . $id);
        exit();
    }
}
?>

<html lang="ru">
<head>
    <title>Редактирование записи - Кромка</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox/fancybox.css" rel="stylesheet">
    
    <script>
        function confirmEdit() {        
            return confirm("Вы действительно хотите изменить данные?");
        }
    </script>

    <style>
        .form-group {
            margin-bottom: 20px;
        }
        .lightbox-image {
            max-width: 150px;
            max-height: 150px;
        }
        .image-container {
            display: inline-block;
            margin: 10px;
            text-align: center;
        }
        .paste-zone {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            background: #f8fafc;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .paste-zone:hover, .paste-zone.active {
            border-color: #10b981;
            background: #f0fdf4;
        }
        .paste-zone.drag-over {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        .paste-hint {
            color: #64748b;
            font-size: 14px;
        }
        .paste-hint kbd {
            background: #1e293b;
            color: white;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 12px;
        }
        .image-preview {
            display: inline-block;
            position: relative;
            margin: 10px;
        }
        .image-preview img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .image-preview .remove-btn {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>

<body>
<div class="container mt-4">
    <h3>Редактирование записи - Кромка</h3>
    <form id="editEdge" method="POST" action="../Operations/EdgeOperations.php" onsubmit="return confirmEdit();" enctype="multipart/form-data">
        <input type="hidden" name="Id" value="<?= htmlspecialchars($id) ?>">
        <input type="hidden" name="edit" value="1">

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Номенклатура</label>
                    <input type="text" name="Nomenclature" class="form-control" value="<?= htmlspecialchars($row['Nomenclature'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Номер декора</label>
                    <input type="text" name="DecorNumber" class="form-control" value="<?= htmlspecialchars($row['DecorNumber'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Характер брака</label>
                    <input type="text" name="Character" class="form-control" value="<?= htmlspecialchars($row['Character'] ?? '') ?>">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Примечание</label>
                    <input type="text" name="Complaint" class="form-control" value="<?= htmlspecialchars($row['Complaint'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Полезный размер (длина*ширина), мм</label>
                    <input type="text" name="Size" class="form-control" value="<?= htmlspecialchars($row['Size'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Стоимость реализации с НДС</label>
                    <input type="text" name="Basic" class="form-control" value="<?= htmlspecialchars($row['Basic'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- ЗОНА ДЛЯ ВСТАВКИ ИЗОБРАЖЕНИЙ -->
        <div class="form-group">
            <label>Загрузите изображения</label>
            
            <div class="mb-2">
                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('imageInput').click()">📁 Выбрать файлы</button>
                <input type="file" id="imageInput" name="ImagePath[]" multiple style="display: none;" accept="image/*">
            </div>
            
            <div id="pasteZone" class="paste-zone" tabindex="0">
                <div class="paste-hint">
                    📸 <strong>Нажмите сюда и вставьте фото (Ctrl+V)</strong><br>
                    <small>Можно вставить несколько фото по очереди</small>
                </div>
            </div>
            
            <div id="selectedFilesList" class="mt-3 row"></div>
        </div>

        <!-- ТЕКУЩИЕ ИЗОБРАЖЕНИЯ -->
        <div class="form-group">
            <label class="mb-2">Текущие изображения:</label>
            <div id="currentImages" class="d-flex flex-wrap">
                <?php
                $imageQuery = $connection->query("SELECT id, image_path FROM images_edge WHERE edge_id = $id");
                if ($imageQuery && $imageQuery->num_rows > 0):
                    while ($img = $imageQuery->fetch_assoc()):
                        $imagePath = '../uploads/' . htmlspecialchars($img['image_path']);
                ?>
                    <div class="image-container text-center m-2">
                        <a href="<?= $imagePath ?>" data-fancybox="gallery">
                            <img src="<?= $imagePath ?>" width="100" height="100" style="object-fit: cover; border-radius: 8px;" class="img-thumbnail">
                        </a>
                        <br>
                        <a href="editEdge.php?edit=<?= $id ?>&delete_image=<?= $img['id'] ?>" class="btn btn-danger btn-sm mt-1" onclick="return confirm('Вы уверены, что хотите удалить это изображение?');">Удалить</a>
                    </div>
                <?php 
                    endwhile;
                else: 
                ?>
                    <p class="text-muted ml-2">Нет изображений</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group text-center">
            <button type="submit" name="edit" class="btn btn-success">Сохранить</button>
            <a href="../List/EdgeTable.php" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox/fancybox.umd.js"></script>

<script>
// Хранилище для файлов из буфера обмена
let pastedFiles = [];

document.addEventListener("DOMContentLoaded", function() {
    Fancybox.bind("[data-fancybox='gallery']", {
        closeButton: 'top',
        animationEffect: "fade",
    });
});

// ===== ВСТАВКА ИЗ БУФЕРА ОБМЕНА =====
const pasteZone = document.getElementById('pasteZone');
const selectedFilesList = document.getElementById('selectedFilesList');

if (pasteZone) {
    pasteZone.addEventListener('click', () => {
        pasteZone.focus();
    });
}

if (pasteZone) {
    pasteZone.addEventListener('paste', function(e) {
        e.preventDefault();
        
        const items = (e.clipboardData || e.originalEvent.clipboardData).items;
        
        for (let i = 0; i < items.length; i++) {
            const item = items[i];
            
            if (item.type.indexOf('image') !== -1) {
                const file = item.getAsFile();
                if (file) {
                    const timestamp = Date.now() + Math.random().toString(36).substring(2, 6);
                    const newFile = new File([file], `paste_${timestamp}.png`, { type: file.type });
                    pastedFiles.push(newFile);
                }
            }
        }
        
        renderFileList();
        pasteZone.classList.add('active');
        setTimeout(() => pasteZone.classList.remove('active'), 200);
    });
}

if (pasteZone) {
    pasteZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        pasteZone.classList.add('drag-over');
    });

    pasteZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        pasteZone.classList.remove('drag-over');
    });

    pasteZone.addEventListener('drop', function(e) {
        e.preventDefault();
        pasteZone.classList.remove('drag-over');
        
        const files = e.dataTransfer.files;
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (file.type.startsWith('image/')) {
                const timestamp = Date.now() + Math.random().toString(36).substring(2, 6);
                const newFile = new File([file], `drop_${timestamp}_${file.name}`, { type: file.type });
                pastedFiles.push(newFile);
            }
        }
        renderFileList();
    });
}

const imageInput = document.getElementById('imageInput');
if (imageInput) {
    imageInput.addEventListener('change', function(event) {
        for (let file of event.target.files) {
            pastedFiles.push(file);
        }
        renderFileList();
        imageInput.value = '';
    });
}

function renderFileList() {
    if (!selectedFilesList) return;
    
    selectedFilesList.innerHTML = '';
    
    if (pastedFiles.length === 0) {
        selectedFilesList.innerHTML = '<div class="col-12"><div class="alert alert-light">📭 Нет выбранных файлов</div></div>';
        return;
    }
    
    pastedFiles.forEach((file, index) => {
        const col = document.createElement('div');
        col.className = 'col-md-2 col-sm-3 col-6 mb-3';
        
        const preview = document.createElement('div');
        preview.style.position = 'relative';
        preview.style.display = 'inline-block';
        preview.style.margin = '5px';
        
        const img = document.createElement('img');
        img.style.width = '100px';
        img.style.height = '100px';
        img.style.objectFit = 'cover';
        img.style.borderRadius = '8px';
        img.style.border = '1px solid #e2e8f0';
        
        const url = URL.createObjectURL(file);
        img.src = url;
        img.onload = () => URL.revokeObjectURL(url);
        
        const removeBtn = document.createElement('div');
        removeBtn.innerHTML = '✕';
        removeBtn.style.position = 'absolute';
        removeBtn.style.top = '-8px';
        removeBtn.style.right = '-8px';
        removeBtn.style.background = '#ef4444';
        removeBtn.style.color = 'white';
        removeBtn.style.borderRadius = '50%';
        removeBtn.style.width = '22px';
        removeBtn.style.height = '22px';
        removeBtn.style.display = 'flex';
        removeBtn.style.alignItems = 'center';
        removeBtn.style.justifyContent = 'center';
        removeBtn.style.cursor = 'pointer';
        removeBtn.style.fontSize = '12px';
        removeBtn.style.fontWeight = 'bold';
        removeBtn.onclick = () => {
            pastedFiles.splice(index, 1);
            renderFileList();
        };
        
        preview.appendChild(img);
        preview.appendChild(removeBtn);
        col.appendChild(preview);
        selectedFilesList.appendChild(col);
    });
}

// Отправка формы
document.getElementById('editEdge').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    
    for (let el of this.elements) {
        if (el.name && el.type !== 'file') {
            formData.append(el.name, el.value);
        }
    }
    
    pastedFiles.forEach(file => {
        formData.append('ImagePath[]', file);
    });
    
    fetch(this.action, {
        method: 'POST',
        body: formData
    }).then(response => {
        if (response.redirected) {
            window.location.href = response.url;
        } else {
            return response.text();
        }
    }).then(data => {
        if (data && data.includes('error')) {
            alert("Ошибка: " + data);
        }
    }).catch(error => {
        alert("Ошибка при загрузке: " + error);
    });
});
</script>
</body>
</html>