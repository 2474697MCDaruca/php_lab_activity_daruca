<?php
require_once __DIR__ . '/dbcon.php';

$error = "";
$editMode = false;
$editId = 0;
$name = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST['action'] ?? 'add';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $editId = (int)($_POST['id'] ?? 0);

    $editMode = ($action === 'update');

    if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid name and email address.";

    } elseif ($editMode) {

        if ($editId <= 0) {
            $error = "Invalid user selected.";
        } else {

            $stmt = $conn->prepare(
                "SELECT id FROM users WHERE id = ?"
            );
            $stmt->bind_param("i", $editId);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 0) {
                $error = "User not found.";
            }
            $stmt->close();

            if ($error === "") {
                $stmt = $conn->prepare(
                    "UPDATE users SET name = ?, email = ? WHERE id = ?"
                );

                $stmt->bind_param("ssi", $name, $email, $editId);
                $stmt->execute();
                $stmt->close();

                header("Location: index.php?msg=updated");
                exit();
            }
        }

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO users (name, email) VALUES (?, ?)"
        );

        $stmt->bind_param("ss", $name, $email);
        $stmt->execute();
        $stmt->close();

        header("Location: index.php?msg=added");
        exit();
    }
}


if ($_SERVER["REQUEST_METHOD"] !== "POST" && isset($_GET['edit'])) {

    $editId = (int)$_GET['edit'];

    if ($editId > 0) {

        $stmt = $conn->prepare(
            "SELECT name, email FROM users WHERE id = ?"
        );

        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $stmt->bind_result($name, $email);

        if ($stmt->fetch()) {
            $editMode = true;
        } else {
            $error = "User not found.";
            $name = "";
            $email = "";
        }

        $stmt->close();
    }
}

$result = $conn->query(
    "SELECT * FROM users ORDER BY id ASC"
);


$messages = [
    "added" => "User added successfully!",
    "updated" => "User updated successfully!",
    "deleted" => "User deleted successfully!"
];

$msg = $_GET['msg'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>User Management System</title>

    <!-- BOOTSTRAP 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #f4f7ff, #eef1fb);
            font-family: 'Segoe UI', Arial, sans-serif;
            min-height: 100vh;
            color: #28324a;
        }

        .main-container {
            max-width: 1450px;
            margin: 55px auto;
        }

        .page-title {
            text-align: center;
            font-size: 38px;
            font-weight: 600;
            margin-bottom: 55px;
            color: #303b67;
        }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #35436b;
        }

        .form-box {
            background-color: white;
            padding: 28px;
            border-radius: 15px;
            box-shadow: 0 6px 22px rgba(40,55,100,0.08);
        }

        .form-label {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 9px;
        }

        .form-control {
            padding: 12px;
            border-radius: 9px;
            border: 1px solid #dce2ef;
        }

        .form-control:focus {
            border-color: #6976d9;
            box-shadow: 0 0 0 3px rgba(105,118,217,0.12);
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(120deg, #4263eb, #7061cb);
            border: none;
            border-radius: 9px;
            font-size: 17px;
            font-weight: 500;
            color: white;
        }

        .submit-btn:hover {
            background: #354ec5;
            color: white;
        }

        .users-box {
            background: white;
            padding: 24px;
            border-radius: 15px;
            box-shadow: 0 6px 22px rgba(40,55,100,0.08);
        }

        .table th {
            font-weight: 600;
            padding: 15px 10px;
            background: #f3f5fc;
            color: #405077;
        }

        .table td {
            padding: 15px 10px;
            vertical-align: middle;
        }

        .action-buttons {
            white-space: nowrap;
        }

        .btn-success {
            background-color: #198754;
            border-radius: 7px;
        }

        .btn-danger {
            background-color: #dc3545;
            border-radius: 7px;
        }

        .alert {
            border-radius: 9px;
        }

        .footer {
            text-align: center;
            padding: 25px;
            color: #8790a7;
            font-size: 13px;
        }
    </style>
</head>

<body>

<div class="container-fluid main-container px-4">

    <!-- PAGE TITLE -->
    <h1 class="page-title">
        User Management System
    </h1>

    <!-- SUCCESS MESSAGE -->
    <?php if (isset($messages[$msg])): ?>

        <div class="alert alert-success alert-dismissible fade show"
             role="alert">

            <?= htmlspecialchars($messages[$msg]) ?>

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close">
            </button>

        </div>

    <?php endif; ?>

    <!-- ERROR MESSAGE -->
    <?php if ($error !== ""): ?>

        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <div class="row g-4">

        <!-- LEFT SIDE: ADD OR EDIT USER -->
        <div class="col-lg-5">

            <h3 class="section-title">
                <?= $editMode ? "Edit User" : "Add New User" ?>
            </h3>

            <div class="form-box">

                <form method="POST" action="index.php">

                    <input type="hidden"
                           name="action"
                           value="<?= $editMode ? 'update' : 'add' ?>">

                    <input type="hidden"
                           name="id"
                           value="<?= (int)$editId ?>">

                    <!-- FULL NAME -->
                    <div class="mb-4">

                        <label class="form-label">
                            Full Name
                        </label>

                        <input type="text"
                               name="name"
                               class="form-control"
                               placeholder="Enter Full Name"
                               value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                               required>

                    </div>

                    <!-- EMAIL ADDRESS -->
                    <div class="mb-4">

                        <label class="form-label">
                            Email Address
                        </label>

                        <input type="email"
                               name="email"
                               class="form-control"
                               placeholder="Enter Email Address"
                               value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                               required>

                    </div>

                    <!-- SUBMIT OR UPDATE -->
                    <button type="submit"
                            class="btn submit-btn">

                        <?= $editMode ? "Submit" : "Submit" ?>

                    </button>

                    <!-- CANCEL EDIT -->
                    <?php if ($editMode): ?>

                        <a href="index.php"
                           class="btn btn-outline-secondary w-100 mt-3">
                            Cancel Edit
                        </a>

                    <?php endif; ?>

                </form>

            </div>
        </div>

        <!-- RIGHT SIDE: REGISTERED USERS -->
        <div class="col-lg-7">

            <h3 class="section-title">
                Registered Users
            </h3>

            <div class="users-box">

                <div class="table-responsive">

                    <table class="table table-hover">

                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Joined</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($result->num_rows > 0): ?>

                            <?php
                            $number = 1;
                            ?>

                            <?php while ($row = $result->fetch_assoc()): ?>

                                <tr>

                                    <!-- AUTOMATIC NUMBER -->
                                    <td>
                                        <?= $number++ ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($row['name']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($row['email']) ?>
                                    </td>

                                    <td>
                                        <?= date(
                                            "M d, Y",
                                            strtotime($row['created_at'])
                                        ) ?>
                                    </td>

                                    <td class="action-buttons">

                                        <!-- EDIT BUTTON -->
                                        <a href="index.php?edit=<?= (int)$row['id'] ?>"
                                           class="btn btn-success btn-sm">
                                            Edit
                                        </a>

                                        <!-- DELETE BUTTON -->
                                        <a href="delete.php?id=<?= (int)$row['id'] ?>"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Are you sure you want to delete this user?');">
                                            Delete
                                        </a>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="5"
                                    class="text-center text-muted py-4">
                                    No registered users yet.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>

<!-- FOOTER -->
<div class="footer">
    User Management System | PHP & MySQL CRUD
</div>

<!-- BOOTSTRAP 5 JAVASCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
