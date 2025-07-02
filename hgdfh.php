<?php
include('../connection.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];

    // Sanitize inputs
    function sanitize($data) {
        global $conn;
        return mysqli_real_escape_string($conn, trim($data));
    }

    // Capture form data
    $owner_agent_type = sanitize($_POST['owner_agent_type'] ?? ''); // Owner or Agent
    $owner_bhk_type = sanitize($_POST['owner_bhk_type'] ?? '');
    $agent_bhk_type = sanitize($_POST['agent_bhk_type'] ?? '');

    // Determine BHK type based on Owner or Agent selection
    $property_bhk_type = $owner_agent_type === 'Owner' ? $owner_bhk_type : $agent_bhk_type;

    // Other fields
    $property_type = sanitize($_POST['property_type'] ?? '');
    $build_up_area = sanitize($_POST['build_up_area'] ?? '');
    $property_age = sanitize($_POST['property_age'] ?? '');
    $floor = sanitize($_POST['floor'] ?? '');
    $total_floor = sanitize($_POST['total_floor'] ?? '');
    $area = sanitize($_POST['area'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $state = sanitize($_POST['state'] ?? '');
    $available_for = isset($_POST['available_for']) ? $_POST['available_for'] : [];
    $expected_rent = '';
    $expected_deposit = sanitize($_POST['expected_deposit'] ?? '');
    $maintenance = sanitize($_POST['maintenance'] ?? '');
    $available_from = sanitize($_POST['available_from'] ?? '');
    $preferred_tenants = isset($_POST['preferred_tenants']) ? sanitize(implode(',', $_POST['preferred_tenants'])) : '';
    $furnishing = sanitize($_POST['furnishing'] ?? '');
    $parking = sanitize($_POST['parking'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $bathrooms = sanitize($_POST['bathrooms'] ?? 1);
    $balcony = sanitize($_POST['balcony'] ?? 1);
    $water_supply = sanitize($_POST['water_supply'] ?? '');
    $amenities = isset($_POST['amenities']) ? sanitize(implode(',', $_POST['amenities'])) : '';
    $availability = sanitize($_POST['availability'] ?? '');
    $start_time = sanitize($_POST['start_time'] ?? '');
    $end_time = sanitize($_POST['end_time'] ?? '');
    $available_all = isset($_POST['available_all']) ? 1 : 0;

    // Determine expected_rent based on available_for selections
    foreach ($available_for as $option) {
        if ($option === "Rent" && isset($_POST['expected_rent'])) {
            $expected_rent = sanitize($_POST['expected_rent']);
        } elseif ($option === "Sale" && isset($_POST['expected_price'])) {
            $expected_rent = sanitize($_POST['expected_price']);
        } elseif ($option === "Only Lease" && isset($_POST['expected_lease'])) {
            $expected_rent = sanitize($_POST['expected_lease']);
        }
    }

    // File upload logic
    $uploads_dir = '../uploads'; // Directory for uploads
    $uploaded_files = [];
    $max_files = 11;

    if (!empty($_FILES['file_upload']['name'][0])) {
        $total_files = count($_FILES['file_upload']['name']);

        if ($total_files > $max_files) {
            echo "<script>
                alert('You can upload a maximum of $max_files images.');
                window.history.back();
            </script>";
            exit();
        }

        foreach ($_FILES['file_upload']['name'] as $key => $name) {
            $tmp_name = $_FILES['file_upload']['tmp_name'][$key];
            $file_ext = pathinfo($name, PATHINFO_EXTENSION);
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array(strtolower($file_ext), $allowed_exts)) {
                $file_name = 'pro_' . substr(uniqid(), -3) . '.' . $file_ext;
                $file_path = "$uploads_dir/$file_name";

                if (move_uploaded_file($tmp_name, $file_path)) {
                    $uploaded_files[] = $file_path;
                } else {
                    echo "<script>
                        alert('Error uploading file: $name');
                        window.history.back();
                    </script>";
                    exit();
                }
            } else {
                echo "<script>
                    alert('Invalid file type for $name. Only jpg, jpeg, png, and gif are allowed.');
                    window.history.back();
                </script>";
                exit();
            }
        }
    }

    $file_paths = sanitize(implode(',', $uploaded_files));

    // SQL Query for inserting property details
    $sql = "INSERT INTO properties 
        (user_id, owner_agent_type, bhk_type, property_type, build_up_area, property_age, floor, total_floor, area, city, state, available_for, expected_rent, expected_deposit, maintenance, available_from, preferred_tenants, furnishing, 
        parking, description, bathrooms, balcony, water_supply, amenities, file_upload, availability, start_time, 
        end_time, available_all, created_at) 
        VALUES 
        ('$user_id', '$owner_agent_type', '$property_bhk_type', '$property_type', '$build_up_area', '$property_age', 
        '$floor', '$total_floor', '$area', '$city', '$state', '" . implode(',', $available_for) . "', '$expected_rent', '$expected_deposit', '$maintenance', 
        '$available_from', '$preferred_tenants', '$furnishing', '$parking', '$description', '$bathrooms', '$balcony', 
        '$water_supply', '$amenities', '$file_paths', '$availability', '$start_time', '$end_time', '$available_all', NOW())";

    if ($conn->query($sql) === TRUE) {
        echo "<script>
        alert('Property posted successfully!');
        window.location.href = 'post-property';
        </script>";
    } else {
        echo "Error: " . $conn->error;
    }

    $conn->close();
}
?>
