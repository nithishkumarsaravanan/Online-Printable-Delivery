<?php
session_start();
include("connection/connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['uploadedFile']) && $_FILES['uploadedFile']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['uploadedFile']['tmp_name'];
        $fileName = $_FILES['uploadedFile']['name'];
        $fileSize = $_FILES['uploadedFile']['size'];
        $fileType = $_FILES['uploadedFile']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Sanitize file name
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;

        // Allowed file extensions
        $allowedfileExtensions = array('pdf', 'jpg', 'jpeg', 'png', 'gif');

        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Directory in which the uploaded file will be moved
            $uploadFileDir = 'uploads/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Insert file info into database
                $stmt = $db->prepare("INSERT INTO uploaded_files (file_name, file_path, file_type, file_size, uploaded_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("sssi", $fileName, $dest_path, $fileType, $fileSize);
                $stmt->execute();
                $stmt->close();

                // Redirect back with success message
                header("Location: landing.php?upload=success");
                exit;
            } else {
                $message = 'There was an error moving the uploaded file.';
            }
        } else {
            $message = 'Upload failed. Allowed file types: ' . implode(', ', $allowedfileExtensions);
        }
    } else {
        $message = 'No file uploaded or unknown error.';
    }
} else {
    $message = 'Invalid request method.';
}

// If error, redirect back with error message
header("Location: landing.php?upload=error&message=" . urlencode($message));
exit;
