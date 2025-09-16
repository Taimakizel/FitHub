<?php
    $con = new mysqli("localhost", "root", "", "fithub");
    if ($con->connect_error) {
        die("Connection failed: " . $con->connect_error);
    }
    session_start(); // Start the session
    if (!isset($_SESSION['FirstName'])) {
        header("Location: login.php");
        exit();
    }
    $admin = $_SESSION['FirstName'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FitHub - Admin</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            color:black;
            font-family:Times New Roman;
            margin: 0;
            padding: 0;
            background: url('images/gym.jpeg');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
        }
        nav {
            display: flex;
            justify-content: center;
            gap: 30px;
            padding: 15px;
            margin:20px;
        }

        nav button {
            background-color:rgb(205, 232, 191);
            border: none;
            color: #000;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size:16px;
        }

        nav button:hover {
            background-color:rgb(229, 241, 220);
        }

        .section {
            display: none;
            padding: 40px;
            max-width: 800px;
            margin: auto;
            background-color:rgba(217, 217, 217, 0.3);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            align-items:center;
        }

        .section.active {
            display: block;
        }

        h2 {
            border-bottom: 1px solid #444;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        .form-container {
            background-color:rgba(255, 255, 255, 0.91);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            margin-bottom: 20px;
            
        }

        .form-container input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 8px;
            border: none;
            background-color:rgba(202, 201, 201, 0.45);
            color: rgba(0, 0, 0, 0.5);
            font-size: 16px;
        }

        .form-container button {
            background-color:rgb(205, 232, 191);
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            margin-right: 10px;
        }

        .form-container button:hover {
            background-color:rgb(229, 241, 220);
        }

        label {
            font-weight: bold;
            color:black;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            text-align: center;
        }

        th, td {
            padding: 12px;
            border:solid black 1px;
        }

        th {
            background-color:none;
        }

        tr:nth-child(even) {
            background-color:rgba(255, 255, 255, 0.62);
        }
        .Divchart{
            margin-top: 30px;
        }
        #chart {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        hr{
            margin: 30px 0;
            border-color: #444;
        }
        ul {
            list-style-type: none;
            padding-left: 0;
        }

        ul li {
            background-color: rgba(255, 255, 255, 0.53);
            margin: 10px;
            padding: 10px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* עיצוב התראה שגיאה */
        p.error {
            color: red;
            text-align: center;
        }
        
        .action-buttons {
            display: flex;
            align-items: center;
            gap: 5px;
            height:37px;
        }
        .edit-btn, .delete-btn {
            background-color:rgb(186, 213, 170);
            border: none;
            color: #000;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        
        .edit-btn:hover {
            background-color:rgb(196, 223, 177);
        }
        
        .delete-btn:hover {
            background-color:rgb(196, 223, 177);
        }
        .select-container {
    margin-bottom: 20px;
}



.select-container select {
    width: 100%;
    padding: 10px;
    background-color:rgba(202, 201, 201, 0.45);
    color:rgb(0, 0, 0);
    border:none;
    border-radius: 8px;
    font-size: 16px;
    margin-top:8px;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml;charset=US-ASCII,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12'><polygon points='0,0 12,0 6,6'  fill='%23000000'/></svg>");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 12px;
}

.select-container select:focus {
    outline: none;
    border-color:rgb(0, 0, 0);
}
.butt{
    display: flex;
}
.btn {
    font-size: 18px;
    background: none;
    border: none;
    cursor: pointer;
    transition: 0.3s ease;
    color:white;
    font-weight: bold;
    padding: 8px 12px;
    border-radius: 6px;
    transition: background-color 0.3s;
}
.btn:hover {
    background-color: rgba(167, 178, 139, 0.7);
}
.header {
    background-color: rgba(0, 0, 0, 0.7);
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.header h1 {
    font-size: 26px;
    color:rgb(255, 255, 255);
    margin: 0;
}
    .client-item:hover {
        background-color: #f0f0f0;
    }
    
    .client-item.selected {
        background-color: rgb(205, 232, 191);
    }
    
    #searchClient {
        width: 100%;
        padding: 10px;
        background-color: rgba(202, 201, 201, 0.45);
        color: rgb(0, 0, 0);
        border: none;
        border-radius: 8px;
        font-size: 16px;
        margin-top: 8px;
    }
    
    #searchClient:focus {
        outline: none;
        border: 2px solid rgb(0, 0, 0);
    }
</style>
</head>
<body>
    <div class="header">
        <h1 class='wel'>Welcome <?php echo $admin; ?>✔️</h1>
         <div class="butt">
            <button class="btn" onclick="location.href='home.php'"><i class="fas fa-home"></i></button>
            <button class="btn" onclick="location.href='profile.php'"><i class="fas fa-user"></i></button>
        </div>
    </div>
    <nav>
        <button onclick="showSection('trainers')">Manage Trainers</button>
        <button onclick="showSection('progress')">Client Progress</button>
        <button onclick="showSection('filters')">Manage Filters</button>
        <button onclick="showSection('events')">Manage Events</button>
    </nav>
    <!-- add trainer -->
    <div id="trainers" class="section active">
        <h2>Manage Trainers</h2>
        <?php
        $sql = "SELECT users.userId, users.FirstName, users.LastName, users.Email, users.Phone,users.salary, COUNT(training.trainingNum) AS trainings_count
        FROM users LEFT JOIN training ON users.userId = training.TrainerId
        WHERE users.Role = 1 GROUP BY users.userId ORDER BY trainings_count DESC";
        $result = $con->query($sql);
        if ($result && $result->num_rows > 0) {
            echo "<table border='1'>";
            echo "<tr>
                    <th>ID</th>
                    <th>Coach Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Salary per hour</th>
                    <th>Trainings Count</th>
                    <th>Actions</th>
                </tr>";
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['userId']) . "</td>";
                echo "<td>" . htmlspecialchars($row['FirstName']) ." ".htmlspecialchars($row['LastName']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Email']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Phone']) . "</td>";
                echo "<td>" . htmlspecialchars($row['salary']) . "</td>";
                echo "<td>" . (int)$row['trainings_count'] . "</td>";
                echo "<td class='action-buttons'>
                        <form method='POST'>
                            <input type='hidden' name='edit_trainer_id' value='" . htmlspecialchars($row['userId']) . "'>
                            <button type='submit' class='edit-btn' name='find_trainer'>Edit</button>
                        </form>
                        <form method='POST' onsubmit=\"return confirm('Confirm Deleting?');\">
                            <input type='hidden' name='delete_trainer_id' value='" . htmlspecialchars($row['userId']) . "'>
                            <button type='submit' class='delete-btn' name='delete_trainer'>Delete</button>
                        </form>
                      </td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>No coaches found.</p>";
        }
        
        // Process trainer deletion
        if (isset($_POST['delete_trainer']) && isset($_POST['delete_trainer_id'])) {
            $delete_id = $_POST['delete_trainer_id'];
            
            // First check if trainer has associated trainings
            $check_sql = "SELECT COUNT(*) as count FROM training WHERE TrainerId = ?";
            $check_stmt = $con->prepare($check_sql);
            $check_stmt->bind_param("s", $delete_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $row = $check_result->fetch_assoc();
            
            if ($row['count'] > 0) {
                echo "<script>alert('Cannot delete this trainer because they have associated trainings. Please reassign those trainings first.');</script>";
            } else {
                // Delete the trainer
                $delete_sql = "DELETE FROM users WHERE userId = ? AND Role = 1";
                $delete_stmt = $con->prepare($delete_sql);
                $delete_stmt->bind_param("s", $delete_id);
                
                if ($delete_stmt->execute()) {
                    echo "<script>alert('Trainer deleted successfully!'); window.location.href='admin.php';</script>";
                } else {
                    echo "<p class='error'>Error deleting trainer: " . $delete_stmt->error . "</p>";
                }
                $delete_stmt->close();
            }
            $check_stmt->close();
        }
        
        // Display trainer edit form
        $trainer = null;
        if (isset($_POST['find_trainer']) && isset($_POST['edit_trainer_id'])) {
            $search_id = $_POST['edit_trainer_id'];
            $search_sql = "SELECT * FROM users WHERE userId = ? AND Role = 1";
            $search_stmt = $con->prepare($search_sql);
            $search_stmt->bind_param("s", $search_id);
            $search_stmt->execute();
            $search_result = $search_stmt->get_result();
            
            if ($search_result->num_rows > 0) {
                $trainer = $search_result->fetch_assoc();
            } else {
                echo "<p class='error'>Trainer not found.</p>";
            }
            $search_stmt->close();
        }
        
        // Process trainer update
        if (isset($_POST['update_trainer']) && isset($_POST['edit_trainer_id'])) {
            $edit_id = $_POST['edit_trainer_id'];
            $edit_first_name = $_POST['edit_FirstName'];
            $edit_last_name = $_POST['edit_LastName'];
            $edit_email = $_POST['edit_Email'];
            $edit_phone = $_POST['edit_Phone'];
            $edit_password = $_POST['edit_Password'];
            
            if (empty($edit_password)) {
                // Update without changing password
                $update_sql = "UPDATE users SET FirstName = ?, LastName = ?, Email = ?, Phone = ? WHERE userId = ? AND Role = 1";
                $update_stmt = $con->prepare($update_sql);
                $update_stmt->bind_param("sssss", $edit_first_name, $edit_last_name, $edit_email, $edit_phone, $edit_id);
            } else {
                // Update including password
                $hashed_password = password_hash($edit_password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE users SET FirstName = ?, LastName = ?, Email = ?, Phone = ?, Password = ? WHERE userId = ? AND Role = 1";
                $update_stmt = $con->prepare($update_sql);
                $update_stmt->bind_param("ssssss", $edit_first_name, $edit_last_name, $edit_email, $edit_phone, $hashed_password, $edit_id);
            }
            
            if ($update_stmt->execute()) {
                echo "<script>alert('Trainer information updated successfully!'); window.location.href='admin.php';</script>";
            } else {
                echo "<p class='error'>Error updating trainer: " . $update_stmt->error . "</p>";
            }
            $update_stmt->close();
        }
        
        // Show edit form if a trainer was found
        if ($trainer) {
            ?>
            <hr>
            <div class="form-container">
                <h3>Update Trainer</h3>
                <form action="admin.php" method="POST">
                    <input type="hidden" name="edit_trainer_id" value="<?php echo htmlspecialchars($trainer['userId']); ?>">
                    <div>
                        <label for="edit_FirstName">First Name</label>
                        <input type="text" name="edit_FirstName" value="<?php echo htmlspecialchars($trainer['FirstName']); ?>" required>
                    </div>
                    <div>
                        <label for="edit_LastName">Last Name</label>
                        <input type="text" name="edit_LastName" value="<?php echo htmlspecialchars($trainer['LastName']); ?>" required>
                    </div>
                    <div>
                        <label for="edit_Email">Email</label>
                        <input type="email" name="edit_Email" value="<?php echo htmlspecialchars($trainer['Email']); ?>" required>
                    </div>
                    <div>
                        <label for="edit_Phone">Phone</label>
                        <input type="text" name="edit_Phone" id="edit_Phone" value="<?php echo htmlspecialchars($trainer['Phone']); ?>" pattern="[0-9]{10}" title="Phone number must be exactly 10 digits" required>
                        <small id="phoneError" style="color: #e53e3e; display: none;">Phone number must be exactly 10 digits</small>
                    </div>
                    <div>
                        <label for="edit_Password">New Password (leave blank to keep current)</label>
                        <input type="password" name="edit_Password">
                    </div>
                    <button type="submit" name="update_trainer">Update Trainer</button>
                </form>
            </div>
            <?php
        }
        ?>
        <hr>
        <div class="form-container">
            <h3>Add New Trainer</h3>
            <form action="admin.php" method="POST">
                <div>
                    <label for="userId">Trainer Id</label>
                    <input type="text" name="userId" required>
                </div>
                <div>
                    <label for="FirstName">First Name</label>
                    <input type="text" name="FirstName" required>
                </div>
                <div>
                    <label for="LastName">Last Name</label>
                    <input type="text" name="LastName" required>
                </div>
                <div>
                    <label for="Email">Email</label>
                    <input type="email" name="Email"  required>
                </div>
                <div>
                    <label for="Phone">Phone</label>
                    <input type="text" name="Phone" pattern="[0-9]{10}" title="Phone number must be exactly 10 digits" required>
                    <small id="phoneError" style="color: #e53e3e; display: none;">Phone number must be exactly 10 digits</small>
                </div>
                <div>
                    <label for="Password">Password</label>
                    <input type="password" name="Password" required>
                </div>
                    <input type="hidden" name="Role" value="1"> <!-- Role קבוע למאמן -->
                    <button type="submit" name="add_trainer">Add Trainer</button>
            </form>
            <script>
                function validateForm() {
                    const phoneInput = document.getElementById('edit_Phone');
                    const phoneError = document.getElementById('phoneError');
                    const phoneValue = phoneInput.value.trim();
                    
                    // Check if phone is exactly 10 digits
                    if (!/^\d{10}$/.test(phoneValue)) {
                        phoneError.style.display = 'block';
                        phoneInput.focus();
                        return false;
                    }
                    
                    phoneError.style.display = 'none';
                    return true;
                }

                // Add event listener to validate as user types
                document.getElementById('edit_Phone').addEventListener('input', function() {
                    const phoneInput = this;
                    const phoneError = document.getElementById('phoneError');
                    const phoneValue = phoneInput.value.trim();
                    
                    if (phoneValue !== '' && !/^\d{10}$/.test(phoneValue)) {
                        phoneError.style.display = 'block';
                    } else {
                        phoneError.style.display = 'none';
                    }
                });
            </script>
            <?php
                if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_trainer'])) {
                    $userId = $_POST['userId'];
                    $firstName = $_POST['FirstName'];
                    $lastName = $_POST['LastName'];
                    $email = $_POST['Email'];
                    $phone = $_POST['Phone'];
                    $password = $_POST['Password'];
                    $joinDate = date('Y-m-d H:i:s'); 
                    $role = 1; 
                    
                    // Check if userId already exists
                    $check_sql = "SELECT userId FROM users WHERE userId = ?";
                    $check_stmt = $con->prepare($check_sql);
                    $check_stmt->bind_param("s", $userId);
                    $check_stmt->execute();
                    $check_stmt->store_result();
                    
                    if ($check_stmt->num_rows > 0) {
                        echo "<p class='error'>Error: Trainer ID already exists!</p>";
                    } else {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $sql = "INSERT INTO users (userId, FirstName, LastName, Email, Phone, Password, JoinDate, Role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $con->prepare($sql);
                        $stmt->bind_param("sssssssi", $userId, $firstName, $lastName, $email, $phone, $hashedPassword, $joinDate, $role);
                        
                        if ($stmt->execute()) {
                            echo "<script>alert('Trainer added successfully'); window.location.href='admin.php';</script>";
                        } else {
                            echo "<p class='error'>Error: " . $stmt->error . "</p>";
                        }
                        $stmt->close();
                    }
                    $check_stmt->close();
                }
            ?>
        </div>
    </div>

<!-- Client Progress -->
<div id="progress" class="section">
    <h2>Client Progress Tracking</h2>
    <div class="form-container">
        <form method="POST" id="progressForm">
            <div class="select-container">
                <label for="searchClient">Search Client:</label>
                <input type="text" id="searchClient" placeholder="Type client name..." autocomplete="off">
                <input type="hidden" name="userId" id="selectedUserId" required>
                <div id="clientList" style="display: none; max-height: 200px; overflow-y: auto; border: 1px solid #ccc; border-radius: 8px; background-color: white; margin-top: 5px;">
                    <?php
                    $res = $con->query("SELECT userId, FirstName, LastName FROM users WHERE Role = 0 ORDER BY FirstName, LastName");
                    while ($row = $res->fetch_assoc()) {
                        echo "<div class='client-item' data-id='".htmlspecialchars($row['userId'])."' style='padding: 10px; cursor: pointer; border-bottom: 1px solid #eee;'>".htmlspecialchars($row['FirstName'])." ".htmlspecialchars($row['LastName'])."</div>";
                    }
                    ?>
                </div>
            </div>
            <br>
            <label>From Date:</label>
            <input type="date" name="startDate" required>
            <label>To Date:</label>
            <input type="date" name="endDate" required>
            <button type="submit" name="show_progress">Show Progress Chart</button>
        </form>
    </div>
    <?php
        $dates = [];
        $weights = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['show_progress'])) {
            $stmt = $con->prepare("SELECT DateRecorded, weight FROM weights WHERE userId = ? AND DateRecorded BETWEEN ? AND ? ORDER BY DateRecorded ASC");
            $stmt->bind_param("sss", $_POST['userId'], $_POST['startDate'], $_POST['endDate']);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $dates[] = date('d/m/Y', strtotime($row['DateRecorded']));
                $weights[] = $row['weight'];
            }
            $stmt->close();
        }

        if (!empty($weights)) {
            echo "<div class='Divchart'><h3 style='text-align:center;'>Progress Chart</h3><canvas id='chart'></canvas></div>";
            echo "<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
                     <script>
                new Chart(document.getElementById('chart'), {
                    type: 'line',
                    data: {
                        labels: ".json_encode($dates).",
                        datasets: [{
                            label: 'Weight (kg)',
                            data: ".json_encode($weights).",
                            borderColor: 'rgb(178, 211, 171)',
                            backgroundColor: 'rgba(212, 226, 209, 0.81)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        scales: {
                            x: { ticks: { color: 'black' }, grid: { color: 'rgba(172, 168, 168, 0.53)' }},
                            y: { ticks: { color: 'black' }, grid: { color: 'rgba(172, 168, 168, 0.53)' }}
                        }
                    }
                });
            </script>";
        }
    ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchClient');
    const clientList = document.getElementById('clientList');
    const selectedUserId = document.getElementById('selectedUserId');
    const clientItems = document.querySelectorAll('.client-item');

    // Show client list only when there's input
    searchInput.addEventListener('focus', function() {
        if (searchInput.value.trim() !== '') {
            clientList.style.display = 'block';
            filterClients();
        }
    });

    // Filter clients based on search input
    searchInput.addEventListener('input', function() {
        if (searchInput.value.trim() !== '') {
            clientList.style.display = 'block';
            filterClients();
        } else {
            clientList.style.display = 'none';
        }
    });

    // Hide list when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.select-container')) {
            clientList.style.display = 'none';
        }
    });

    // Handle client selection
    clientItems.forEach(function(item) {
        item.addEventListener('click', function() {
            const clientName = this.textContent;
            const clientId = this.dataset.id;
            
            searchInput.value = clientName;
            selectedUserId.value = clientId;
            clientList.style.display = 'none';
            
            // Remove previous selection highlighting
            clientItems.forEach(i => i.classList.remove('selected'));
            // Highlight selected item
            this.classList.add('selected');
        });
    });

    function filterClients() {
        const searchTerm = searchInput.value.toLowerCase();
        let hasVisibleItems = false;
        
        clientItems.forEach(function(item) {
            const clientName = item.textContent.toLowerCase();
            if (clientName.includes(searchTerm) && searchTerm !== '') {
                item.style.display = 'block';
                hasVisibleItems = true;
            } else {
                item.style.display = 'none';
            }
        });
        
        // Hide list if no items match or search is empty
        if (!hasVisibleItems || searchTerm === '') {
            clientList.style.display = 'none';
        }
    }

    // Clear selection when search input is manually changed
    searchInput.addEventListener('input', function() {
        selectedUserId.value = '';
        clientItems.forEach(i => i.classList.remove('selected'));
    });
});
</script>

    <div id="filters" class="section">
    <h2>Filters Managment</h2>
    <div class="form-container">
        <form action="admin.php" method="POST">
            <label for="typeName">Filter Name</label>
            <input type="text" name="typeName" required>
            <button type="submit" name="addType">Add</button>
        </form>
    </div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addType'])) {
        $typeName = trim($_POST['typeName']);
        if (empty($typeName)) {
            echo "<p class='error'>Insert Filter Name</p>";
        } elseif (!preg_match('/^[א-תa-zA-Z0-9\s]+$/u', $typeName)) {
            echo "<p class='error'>The training type name must contain only letters, numbers, and spaces.</p>";
        } else {
            $stmtCheck = $con->prepare("SELECT typeId FROM types WHERE typeName = ?");
            $stmtCheck->bind_param("s", $typeName);
            $stmtCheck->execute();
            $stmtCheck->store_result();

            if ($stmtCheck->num_rows > 0) {
                echo "<script>alert('Filter Type already exist!'); window.location.href='admin.php';</script>";
            } else {
                $stmt = $con->prepare("INSERT INTO types (typeName) VALUES (?)");
                $stmt->bind_param("s", $typeName);

                if ($stmt->execute()) {
                    echo "<script>alert('Added successfully!'); window.location.href='admin.php';</script>";
                } else {
                    echo "<p class='error'>error: " . $stmt->error . "</p>";
                }
                $stmt->close();
            }
            $stmtCheck->close();
        }
    }
    echo "<h3>Filter Types:</h3>";
    $resultTypes = $con->query("SELECT * FROM types ORDER BY typeId ASC");
    if ($resultTypes->num_rows > 0) {
        echo "<ul>";
        while ($row = $resultTypes->fetch_assoc()) {
            echo "<li>" . htmlspecialchars($row['typeName']). "
                  <form method='post' style='display:inline;'>
                      <input type='hidden' name='delete_id' value='" . (int)$row['typeId'] . "'>
                      <button type='submit' name='deleteType' class='delete-btn'>Delete</button>
                  </form>
                  </li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>No Filter Type Exist</p>";
    }
    if (isset($_POST['deleteType'])) {
        $deleteId = $_POST['delete_id'];
        $query = "DELETE FROM types WHERE typeId = $deleteId";
        if (mysqli_query($con, $query)) {
            echo "<script>alert('Deleted Successfully!'); window.location.href='admin.php';</script>";
        } else {
            echo "<p class='error'>error</p>";
        }
    }
    ?>
</div>
    <!-- add events -->
    <div id="events" class="section">
        <h2>Event Management</h2>
        <div class="form-container">
            <form method="POST">
                <input type="text" name="eventCode" placeholder="Events Code" required>
                <input type="text" name="eventName" placeholder="Events Name" required>
                <input type="datetime-local" name="eventDate" required>
                <input type="text" name="eventDescription" placeholder="Description" required>
                <input type="text" name="eventLocation" placeholder="Location" required>
                <input type="text" name="eventImg" id="eventImg" placeholder="Image Link" required /><br>
                <button type="submit" name="addEvent">Add</button>
            </form>
        </div>
        <?php
           if(isset($_POST['addEvent'])){
                if(isset($_POST['eventCode']) && isset($_POST['eventName']) && isset($_POST['eventDescription']) && isset($_POST['eventImg'])){
                    $eventCode = $_POST['eventCode'];
                    $eventName = $_POST['eventName'];
                    $eventDate = $_POST['eventDate']; // תאריך שנבחר בטופס
                    $eventDescription = $_POST['eventDescription'];
                    $eventLocation = $_POST['eventLocation'];
                    $eventImg = $_POST['eventImg'];        
                    $check_sql = "SELECT * FROM events WHERE eventId = '$eventCode'";
                    $result = $con->query($check_sql);
                    if ($result->num_rows > 0) {
                        echo "Error: Event Code already exists.";
                    } else {
                        $sql = "INSERT INTO events (eventId, eventName, Date, Description, Location, eventImg) 
                                VALUES ('$eventCode', '$eventName', '$eventDate', '$eventDescription', '$eventLocation', '$eventImg')";
                    
                        if ($con->query($sql) === TRUE) {
                            echo "<script>alert('Event added successfully')</script>";
                        } else {
                            echo "Error: " . $sql . "<br>" . $con->error;
                        }
                    }
                }
            }
        ?>
        <h2>Search Events</h2>
        <div class="form-container">
            <form method="POST">
                <input type="text" name="searchEvent" placeholder="Enter Event Code" required>
                <button type="submit" name="findEvent">Search</button>
            </form>
        </div>

        <?php
            $event = null;
            if (isset($_POST['findEvent'])) {
                $searchId = $_POST['searchEvent'];
                $query = "SELECT * FROM events WHERE eventId = '$searchId'";
                $result = $con->query($query);
                if ($result && $result->num_rows > 0) {
                    $event = $result->fetch_assoc();
                } else {
                    echo "<script>alert('Event Not Exist!')</script>";
                }
            }
            if (isset($_POST['updateEvent'])) {
                $id = $_POST['eventId'];
                $name = $_POST['eventName'];
                $date = $_POST['eventDate'];
                $desc = $_POST['eventDescription'];
                $location = $_POST['eventLocation'];
                $img = $_POST['eventImg'];

                $sql = "UPDATE events SET eventName='$name', Date='$date', Description='$desc', Location='$location', eventImg='$img' WHERE eventId='$id'";
                if ($con->query($sql) === TRUE) {
                    echo "<script>alert('Event Added Successfully!');</script>";
                } else {
                    echo "error: " . $con->error;
                }
            }
            if (isset($_POST['deleteEvent'])) {
                $id = $_POST['eventId'];
                $sql = "DELETE FROM events WHERE eventId = '$id'";
                if ($con->query($sql) === TRUE) {
                    echo "<script>alert('Event Deleted');</script>";
                } else {
                    echo "erroe: " . $con->error;
                }
            }
            if ($event) {
            ?>
                <form method="POST" class="form-container">
                    <h3>Update Event </h3>
                    <input type="hidden" name="eventId" value="<?php echo $event['eventId']; ?>">
                    <input type="text" name="eventName" value="<?php echo $event['eventName']; ?>" required>
                    <input type="datetime-local" name="eventDate" value="<?php echo str_replace(' ', 'T', $event['Date']); ?>" required>
                    <input type="text" name="eventDescription" value="<?php echo $event['Description']; ?>" required>
                    <input type="text" name="eventLocation" value="<?php echo $event['Location']; ?>" required>
                    <input type="text" name="eventImg" value="<?php echo $event['eventImg']; ?>" required>
                    <button type="submit" name="updateEvent">Update</button>
                    <form  method="POST" onsubmit="return confirm('Confirm Deleting?');">
                        <input type="hidden" name="eventId" value="<?php echo $event['eventId']; ?>">
                        <button type="submit" name="deleteEvent">Delete</button>
                    </form>
                </form>
                
            <?php
            }
            $con->close();
        ?>
    </div> 
    <script>
        function showSection(id) {
            document.querySelectorAll('.section').forEach(sec => sec.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            localStorage.setItem('activeSection', id);
        }
        document.addEventListener('DOMContentLoaded', function () {
            const lastSection = localStorage.getItem('activeSection');
            if (lastSection && document.getElementById(lastSection)) {
                showSection(lastSection);
            }
        });
    </script>
</body>
</html>