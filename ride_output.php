<?php
require 'resend.php';
session_start();

include 'db.php';

if (!isset($_SESSION['origin']) || !isset($_SESSION['destination'])) {
    header("Location: post_ride.php");
    exit();
}

$coords = [
    'Hyderabad' => [17.385044, 78.486671],
'Bangalore' => [12.971599, 77.594566],
'Chennai' => [13.082680, 80.270721],
'Mumbai' => [19.076090, 72.877426],
'Guntakal' => [15.174230, 77.372330],
'Tirupati' => [13.628800, 79.419230],
'Rangampeta' => [13.623653, 79.278233],
'Renigunta' => [13.634838, 79.510540],
'Banaganapalle' => [15.318202, 78.227886],
'Pulivendula' => [14.424225, 78.225189],
'Chandragiri' => [13.588201, 79.315461],
'Mangapuram' => [13.611300, 79.328365],
'MBU' => [13.623717, 79.289289],
'Kadapa' => [14.467350, 78.824160],
'Tadipatri' => [14.910682, 78.006774],
'Pileru' => [13.705460, 78.927820],
'Madanapalle' => [13.550340, 78.502880],
'Mantralayam' => [15.941332, 77.424632],
'Kurnool' => [15.828126, 78.037279],
'Rayachoti' => [14.056020, 78.751910],
'Vijayawada' => [16.506174, 80.648015],
'Anantapuram' => [14.685190, 77.596350],
'Dharmavaram' => [14.412524, 77.719426],
'Srikalahasti' => [13.750923, 79.699462],
'Visakhapatnam' => [17.686816, 83.218482],
'Guntur' => [16.306652, 80.436540],
'Kakinada' => [16.989065, 82.247465],
'Rajahmundry' => [17.000538, 81.804034],
'Nellore' => [14.442599, 79.986460],
'Chittoor' => [13.217240, 79.101530],
'Eluru' => [16.710663, 81.100725],
'Proddatur' => [14.750230, 78.548087],
'Tenali' => [16.239410, 80.606434],
'Bhimavaram' => [16.538055, 81.513455],
'Nandyal' => [15.487114, 78.484593],
'Bapatla' => [15.896224, 80.476471],
'Kothapeta' => [16.934037, 81.796631],
'Machilipatnam' => [16.189324, 81.131268],
'Srikakulam' => [18.294831, 83.899763],
'Visakhapatnam Beach' => [17.686816, 83.218482],
'Kumarakom' => [9.599802, 76.333576],
'Puttaparthi' => [14.134115, 77.665571],
'Chilakaluripet' => [16.089576, 80.167400],
'Kavali' => [14.917331, 79.980949],
'Kakinada Port' => [16.988387, 82.230293],
'Gooty' => [15.261700, 77.343982],
'Mangalagiri' => [16.234857, 80.555296],
'Peddaganjam' => [14.383193, 79.955069],
'Palakollu' => [16.427750, 81.667658],
'Atmakur' => [15.524623, 78.292212],
'Bhainsa' => [19.130309, 77.329895],
'Jaggayyapeta' => [16.832874, 80.144460],
'Mandapeta' => [16.809959, 81.882557],
'Peddapalli' => [17.660321, 78.100226],
'Tadepalligudem' => [16.853790, 81.524150],
'Vempalli' => [14.643401, 78.755709],
'Rajampet' => [14.146145, 78.866096],
'Thotapalliguduru' => [14.184778, 79.132750],
'Sabbavaram' => [17.703573, 83.226879],
'Chirala' => [15.831043, 80.355689],
'Gannavaram' => [16.508261, 80.794386],
'Nagari' => [13.615400, 79.517800],
'Vellore' => [12.972446, 79.132403],
'Puthalapattu' => [13.241200, 79.246400],
'Gudur' => [14.235186, 79.165263],
'Srinivasa Mangapuram' => [13.605600, 79.430800],
'Amaravati' => [16.573469, 80.358677],
'Markapur' => [15.736872, 79.269066],
'Vayalpad' => [13.645400, 78.625900],
'Punganur' => [13.365290, 78.571740],
'Palamaner' => [13.200100, 78.743190],
'Kapilatheertham' => [13.688800, 79.337100],
'Akasa Ganga' => [13.688800, 79.337100],
'Padmavathi Temple' => [13.626300, 79.431000],
'Vaikuntha Teertham' => [13.670000, 79.344400],
'Panchamukha Anjaneya Swamy Temple' => [13.602400, 79.429700],
'Ramakrishna Ashram' => [13.628000, 79.425300],
'Bellary' => [15.139646, 76.930663],
'Vajra Karur' => [14.632305, 77.555793],
'Singanamala' => [14.747928, 77.931195],
'Chennekothapalli' => [14.568121, 77.536528],
'Kottapalli' => [14.484060, 77.829856],
'Kondapalli' => [14.720093, 77.494132],
'Challakere' => [14.080268, 76.998293],
'Kalikiri' => [13.608640, 78.765550],
"Bengaluru" => [12.971599, 77.594566],
    "Mysuru" => [12.295810, 76.639381],
    "Hubballi" => [15.364708, 75.124008],
    "Dharwad" => [15.460001, 75.006653],
    "Belagavi" => [15.849695, 74.497674],
    "Ballari" => [15.139393, 76.921442],
    "Vijayapura" => [16.830170, 75.710030],
    "Shivamogga" => [13.929930, 75.568100],
    "Tumakuru" => [13.340880, 77.101000],
    "Raichur" => [16.205451, 77.370736],
    "Bidar" => [17.910000, 77.519722],
    "Kalaburagi" => [17.329731, 76.834295],
    "Chitradurga" => [14.230600, 76.398000],
    "Davangere" => [14.464400, 75.921300],
    "Udupi" => [13.340900, 74.742100],
    "Mangaluru" => [12.914100, 74.856000],
    "Chikmagalur" => [13.315300, 75.775400],
    "Hassan" => [13.007200, 76.096900],
    "Kolar" => [13.135700, 78.132000],
    "Ramanagara" => [12.715000, 77.281300],
    "Chamarajanagar" => [11.923000, 76.944000],
    "Kodagu" => [12.421800, 75.739700],
    "Yadgir" => [16.770200, 77.137500],
    "Bagalkot" => [16.172000, 75.658600],
    "Haveri" => [14.793600, 75.399300],
    "Koppal" => [15.347200, 76.154000],
    "Gadag" => [15.429800, 75.628000],
    "Karwar" => [14.813600, 74.129700],
    "Sirsi" => [14.619500, 74.835400],
    "Dandeli" => [15.247700, 74.617800],
    "Bhadravati" => [13.848500, 75.705000],
    "Sagara" => [14.172000, 75.037000],
    "Yelahanka" => [13.100700, 77.596300],
    "Mandya" => [12.522200, 76.895800],
    "Tiptur" => [13.262600, 76.477700],
    "Sira" => [13.745500, 76.904400],
    "Gokarna" => [14.550000, 74.318400],
    "Honnavar" => [14.281500, 74.444300],
    "Kumta" => [14.426500, 74.418900],
    "Bhalki" => [18.043500, 77.206500],
    "Gangavathi" => [15.438700, 76.531500],
    "Honnali" => [14.239400, 75.645500],
    "Kadur" => [13.552500, 76.011400],
    "Sakleshpur" => [12.979200, 75.750700],
    "Bhatkal" => [13.988000, 74.555100],
    "Ranebennur" => [14.617700, 75.616300],
    "Saundatti" => [15.763100, 75.117300],
    "Nanjangud" => [12.118800, 76.684600],
    "Malavalli" => [12.385400, 77.067000],
    "Mulbagal" => [13.166700, 78.399400]
];

$origin = $_SESSION['origin'];
$destination = $_SESSION['destination'];

$startCoords = $coords[$origin] ?? null;
$endCoords = $coords[$destination] ?? null;

if ($startCoords === null || $endCoords === null) {
    $error_message = "Invalid origin or destination. Please make sure both locations are in the predefined list.";
    header("Location: post_ride.php?error=$error_message");
    exit();
}

function vincentyDistance($lat1, $lon1, $lat2, $lon2) {
    $a = 6378137; 
    $b = 6356752.314245;
    $f = 1 / 298.257223563;
    $L = deg2rad($lon2 - $lon1);
    
    $U1 = atan((1 - $f) * tan(deg2rad($lat1)));
    $U2 = atan((1 - $f) * tan(deg2rad($lat2)));
    $sinU1 = sin($U1);
    $cosU1 = cos($U1);
    $sinU2 = sin($U2);
    $cosU2 = cos($U2);

    $lambda = $L;
    $lambdaP = 2 * M_PI;
    $iterLimit = 100;

    while (abs($lambda - $lambdaP) > 1e-12 && --$iterLimit > 0) {
        $sinLambda = sin($lambda);
        $cosLambda = cos($lambda);
        $sinSigma = sqrt(
            ($cosU2 * $sinLambda) * ($cosU2 * $sinLambda) +
            ($cosU1 * $sinU2 - $sinU1 * $cosU2 * $cosLambda) * ($cosU1 * $sinU2 - $sinU1 * $cosU2 * $cosLambda)
        );
        if ($sinSigma == 0) return 0; 
        $cosSigma = $sinU1 * $sinU2 + $cosU1 * $cosU2 * $cosLambda;
        $sigma = atan2($sinSigma, $cosSigma);
        $sinAlpha = $cosU1 * $cosU2 * $sinLambda / $sinSigma;
        $cosSqAlpha = 1 - $sinAlpha * $sinAlpha;
        $cos2SigmaM = $cosU2 * $cosU1 * cos($lambda) - $sinU1 * $sinU2 / $cosSqAlpha;
        if (is_nan($cos2SigmaM)) $cos2SigmaM = 0; 
        $C = $f / 16 * $cosSqAlpha * (4 + $f * (4 - 3 * $cosSqAlpha));
        $lambdaP = $lambda;
        $lambda = $L + (1 - $C) * $f * $sinAlpha * (
            $sigma + $C * $sinSigma * ($cos2SigmaM + $C * $cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM))
        );
    }
    
    if ($iterLimit == 0) return null; 
    $uSq = $cosSqAlpha * ($a * $a - $b * $b) / ($b * $b);
    $A = 1 + $uSq / 16384 * (4096 + $uSq * (-768 + $uSq * (320 - 175 * $uSq)));
    $B = $uSq / 1024 * (256 + $uSq * (-128 + $uSq * (74 - 47 * $uSq)));
    $deltaSigma = $B * $sinSigma * (
        $cos2SigmaM + $B / 4 * (
            $cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM) - 
            $B / 6 * $cos2SigmaM * (-3 + 4 * $sinSigma * $sinSigma) * (-3 + 4 * $cos2SigmaM * $cos2SigmaM)
        )
    );

    $s = $b * $A * ($sigma - $deltaSigma); 
    return $s / 1000; 
}


$distance = vincentyDistance($startCoords[0], $startCoords[1], $endCoords[0], $endCoords[1]);


$systemPrice = round($distance * 2 / 10) * 10;
$successMessage = "";
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $price = $_POST['price'];
    $origin = $_SESSION['origin'];
    $destination = $_SESSION['destination'];
    $ride_date = $_SESSION['ride_date'];
    $ride_time = $_SESSION['ride_time'];
    $vehicle_type = $_GET['vehicle_type'];
    $seats_available = $_SESSION['seats_available'];
    $userId = $_SESSION['user_id'];

    if ($price >= $systemPrice - 10 && $price <= $systemPrice + 10) {
        $query = "INSERT INTO rides (user_id, origin, destination, ride_date, ride_time, vehicle_type, seats_available, price) 
                  VALUES ('$userId', '$origin', '$destination', '$ride_date', '$ride_time', '$vehicle_type', '$seats_available', '$price')";

        if (mysqli_query($conn, $query)) {
            $userEmailQuery = "SELECT email FROM users WHERE id = '$userId'";
            $userEmailResult = mysqli_query($conn, $userEmailQuery);
            $userEmailData = mysqli_fetch_assoc($userEmailResult);

            if ($userEmailData) {
                $userEmail = $userEmailData['email'];
                $update_sql = "UPDATE rides SET posted_email = '$userEmail' WHERE id = '$userId'";
                $conn->query($update_sql);

                $emailBody = "
<h2>Dear User,</h2>

<p>You have successfully posted a ride on <strong>FlexiRide</strong>.</p>

<h3>Ride Details:</h3>

<ul>
    <li><strong>Origin:</strong> {$origin}</li>
    <li><strong>Destination:</strong> {$destination}</li>
    <li><strong>Ride Date:</strong> {$ride_date}</li>
    <li><strong>Ride Time:</strong> {$ride_time}</li>
    <li><strong>Vehicle Type:</strong> {$vehicle_type}</li>
    <li><strong>Seats Available:</strong> {$seats_available}</li>
    <li><strong>Price per Seat:</strong> ₹{$price}</li>
    <li><strong>Distance:</strong> {$distance} km</li>
</ul>

<p>Thank you for using <strong>FlexiRide</strong>.</p>

<p>Regards,<br><strong>FlexiRide Team</strong></p>
";

try {

    sendResendEmail(
        $userEmail,
        $userName,
        "Your Ride Details - FlexiRide",
        $emailBody
    );

    $successMessage = "Ride Submitted! Your custom price is ₹$price. You will be charged ₹10 (₹5 Insurance + ₹5 Convenience Fee). Distance is $distance km.";

} catch (Exception $e) {

    error_log("Resend Error: " . $e->getMessage());

    $errorMessage = "Ride posted successfully, but email could not be sent.";

}
            } else {
                $errorMessage = "Error: Unable to fetch user email.";
            }
        } else {
            $errorMessage = "Error: " . mysqli_error($conn);
        }
    } else {
        $errorMessage = "Price must be within ₹10 of the system-generated price (₹$systemPrice).";
    }
}
?>

<!-- HTML code for displaying success or error message -->


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&family=Sofadi+One&display=swap" rel="stylesheet">    
    <link rel="icon" href="images\favvi.png" type="image/x-icon">
    <title>Ride Pricing</title>
    <style>
        body {
            font-family: "Josefin Sans", sans-serif;
    background:url(images/rideout.jpg);
    background-size: cover; /* This ensures the image covers the entire screen */
  background-position: center center; /* Centers the image */
  background-attachment: fixed;
    color: black;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    overflow: hidden;
}

.container {
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.8s forwards;
    animation-delay: 0.2s;
    background: rgba(255, 255, 255, 0.1);
    padding: 20px 30px;
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.9);
    transition: transform 0.5s ease-in-out, box-shadow 0.3s ease;
    width: 90%;
    backdrop-filter: blur(5px);
    max-width: 500px;
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 10px;
    font-size: 18px;
}


.container:hover {
    transform: scale(1.02);
    box-shadow:0 10px 40px rgba(0, 0, 0, 0.9);
}


h1::after {
    content: '';
    display: block;
    width: 90px;  /* You can adjust this width */
    height: 4px;  /* The thickness of the line */
    background: #3498db;  /* Color of the line */
    margin: 10px auto 0;  /* Centers the line and adds space */
    border-radius: 2px;
}

p {
    animation: slideIn 1s forwards;
    opacity: 0;
    transform: translateY(20px);
    animation-delay: 0.4s;
    font-size: 1rem; /* Consistent font size */
    margin-bottom: 8px;
}

button[type="submit"] {
    font-family: "Josefin Sans", sans-serif;
    background: linear-gradient(135deg, #4a4a8a, #6767b3);
    color: white;
    border: none;
    padding: 12px 20px; /* Slightly smaller padding */
    border-radius: 15px; /* Slightly smaller */
    font-size: 1.1rem;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease, transform 0.2s ease;
    width: 80%;
    margin-top: 10px;
    align-self: center;
}

button[type="submit"]:hover {
    background: linear-gradient(135deg, #6767b3, #4a4a8a);
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 5px 20px rgba(74, 74, 138, 0.4);
}

button[type="submit"]:active {
    transform: translateY(1px);
    background: #39396b;
}

input[type="number"] {
    padding: 12px;
    margin-top: 8px;
    border: 2px solid #ccc;
    border-radius: 8px;
    font-size: 1rem;
    width: 80%;
    margin: 8px auto;
    box-sizing: border-box;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

input[type="number"]:focus {
    outline: none;
    border-color: #4a4a8a;
    box-shadow: 0 0 10px rgba(74, 74, 138, 0.3);
}

p.success {
    color: #28a745;
    animation: fadeIn 1s forwards;
    font-size: 1.1em;
    margin-top: 12px;
}

p.error {
    color: #dc3545;
    animation: fadeIn 1s forwards;
    font-size: 1.1em;
    margin-top: 12px;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes bounceIn {
    0% {
        transform: scale(0);
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}

@keyframes slideIn {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    padding-top: 60px;
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 25px;
    border: 1px solid #888;
    width: 80%;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    border-radius: 8px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 26px;
    font-weight: bold;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}
.modal-button {
    background-color: #007BFF; /* Blue color */
    color: white;
    border: none;
    padding: 12px 24px;
    font-size: 16px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    margin-top: 20px; /* Space between content and button */
    display: block; /* Makes the button block-level for full width */
    width: 20%; /* Optional: Adjust width */
}

/* Button hover effect */
.modal-button:hover {
    background-color: #0056b3; /* Darker blue on hover */
}
@media (max-width: 600px) {
    .container {
        padding: 15px;
        width: 90%;
    }

    h2 {
        font-size: 1.4em;
    }

    button {
        width: 100%;
        padding: 10px;
    }

    input[type="number"] {
        padding: 10px;
    }
}

    </style>
</head>
<body>

<div class="container">
    <h1>Ride Details</h1><h2>
    <p>Origin: <?php echo htmlspecialchars($origin); ?></p>
    <p>Destination: <?php echo htmlspecialchars($destination); ?></p>
    <p>Distance: <?php echo round($distance, 2); ?> km</p>
    <p>System-generated Price: ₹<?php echo $systemPrice; ?></p></h2>
    <form method="POST" id="priceForm">
        <label for="price">Enter your price (within ₹10 of the system-generated price): </label>
        <input type="number" name="price" id="price" required>
        <button type="submit">Submit</button>
    </form>
</div>
<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <p id="modalMessage">
            <?php if ($successMessage): ?>
                <strong>Success:</strong> <?php echo $successMessage; ?><br>
            <?php elseif ($errorMessage): ?>
                <strong>Error:</strong> <?php echo $errorMessage; ?><br>
            <?php endif; ?>
        </p>     
        <p><strong>Origin:</strong> <?php echo htmlspecialchars($origin); ?></p>
        <p><strong>Destination:</strong> <?php echo htmlspecialchars($destination); ?></p>
        <p><strong>Ride Date:</strong> <?php echo htmlspecialchars($_SESSION['ride_date']); ?></p>
        <p><strong>Ride Time:</strong> <?php echo htmlspecialchars($_SESSION['ride_time']); ?></p>
        <button class="modal-button" onclick="window.location.href='index.php'">Back to Home</button>
    </div>
</div>
<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <p id="modalMessage">
            <?php if ($showProfileUpdate): ?>
                <strong>Error:</strong> <?php echo $errorMessage; ?><br>
                <button onclick="window.location.href='profile.php'">Go to Profile Page</button>
            <?php elseif ($successMessage): ?>
                <strong>Success:</strong> <?php echo $successMessage; ?>
            <?php elseif ($errorMessage): ?>
                <strong>Error:</strong> <?php echo $errorMessage; ?>
            <?php endif; ?>
        </p>
    </div>
</div>
<script>
    $(document).ready(function() {
        <?php if ($successMessage || $errorMessage): ?>
            $("#myModal").css("display", "block");
        <?php endif; ?>

        $(".close").click(function() {
            $("#myModal").css("display", "none");
        });

        $(window).click(function(event) {
            if (event.target.id === "myModal") {
                $("#myModal").css("display", "none");
            }
        });
    });
</script>
</body>
</html>
