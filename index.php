<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Relay - Bridging Connections">
    <meta name="author" content="Relay Team">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <!-- Custom CSS -->


    <title>RELAY - Bridging Connections</title>
</head>
<body>
<!-- Start of the Navbar -->
<?php include 'views/template/header.phtml'; ?>

<!-- Hero Section -->
<div class="container-fluid p-5 text-white text-center" style="background: linear-gradient(to right, #0056b3, #004080);">
    <img src="/images/image.jpg" alt="Relay Logo" class="mb-4" width="150" height="150">
    <h1>Welcome to Relay</h1>
    <p class="lead">Bridging Connections, Empowering Communities</p>
    <div class="login-box" style="width: 300px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); text-align: center;">
        <!--Passes to the Staff List -->
        <form action="controllers/processLogin.php" method="post" style="display: flex; flex-direction: column; gap: 10px;">
            <input type="text" name="username" placeholder="Username" required style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
            <input type="password" name="password" placeholder="Password" required style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
            <button type="submit" style="padding: 10px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer;">
                Login
            </button>
        </form>
    </div>

</div>

<!-- Features Section -->
<div class="container mt-5">
    <h2 class="text-center mb-4">Why Choose Relay?</h2>
    <div class="row text-center">
        <div class="col-md-4">
            <i class="fa fa-users fa-3x text-primary mb-3"></i>
            <h4>Seamless Connections</h4>
            <p>Keep track of all your contacts and build stronger relationships effortlessly.</p>
        </div>
        <div class="col-md-4">
            <i class="fa fa-clock-o fa-3x text-primary mb-3"></i>
            <h4>Time-Saving Tools</h4>
            <p>Log and review activities easily to stay organized and efficient.</p>
        </div>
        <div class="col-md-4">
            <i class="fa fa-line-chart fa-3x text-primary mb-3"></i>
            <h4>Impact Tracking</h4>
            <p>Measure community goals and see the difference you're making.</p>
        </div>
    </div>
</div>




