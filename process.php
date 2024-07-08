<?php
require 'db.php';

function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = sanitize_input($_POST['name']);
        $email = sanitize_input($_POST['email']);
        $phone = sanitize_input($_POST['phone']);
        $address = sanitize_input($_POST['address']);
        $job = sanitize_input($_POST['job']);
        
        // Log received input for debugging
        error_log("Received input: name=$name, email=$email, phone=$phone, address=$address, job=$job\n", 3, 'input.log');

        // Check if the user has already applied
        $stmt = $conn->prepare("SELECT * FROM applications WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }
        
        $stmt->bind_param("s", $email);
        if (!$stmt->execute()) {
            throw new Exception("Error executing statement: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $response['message'] = 'You have already applied for a job.';
        } else {
            // Handle file upload
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES["resume"]["name"]);
            $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_types = ['pdf', 'doc', 'docx'];
            
            if (in_array($file_type, $allowed_types) && $_FILES["resume"]["size"] <= 5000000) {
                if (move_uploaded_file($_FILES["resume"]["tmp_name"], $target_file)) {
                    // Log successful file upload
                    error_log("File uploaded to: $target_file\n", 3, 'input.log');
                    
                    // Insert application data into database
                    $stmt = $conn->prepare("INSERT INTO applications (name, email, phone, resume, address, job) VALUES (?, ?, ?, ?, ?, ?)");
                    if (!$stmt) {
                        throw new Exception("Error preparing statement: " . $conn->error);
                    }
                    
                    $stmt->bind_param("ssssss", $name, $email, $phone, $target_file, $address, $job);
                    if (!$stmt->execute()) {
                        throw new Exception("Error executing statement: " . $stmt->error);
                    }
                    
                    $response['success'] = true;
                    $response['message'] = 'Your application has been submitted successfully.';
                } else {
                    throw new Exception('Error uploading your resume.');
                }
            } else {
                $response['message'] = 'Invalid file type or size.';
            }
        }
        $stmt->close();
    }
} catch (Exception $e) {
    // Log error to a file
    error_log($e->getMessage(), 3, 'errors.log');
    $response['message'] = 'Exception: ' . $e->getMessage();
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($response);
?>
