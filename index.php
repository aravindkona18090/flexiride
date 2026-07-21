<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&family=Sofadi+One&display=swap" rel="stylesheet">
  <style>
    * {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}


.navbar {
  font-family:"Josefin Sans", sans-serif;
  background-color: #000;
  color: #fff;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0px 0px;
  position: sticky;
  top: 0;
  z-index: 1000;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3); 
}

.logo a {
  font-size: 24px;
  font-weight: bold;
  color: #fff;
  text-decoration: none;
  letter-spacing: 2px;
  transition: color 0.3s ease;
}

.logo a:hover {
  color: #ff9800; 
}


.nav-links {
  list-style: none;
  display: flex;
}

.nav-links li {
  margin-left: 30px;
}

.nav-links a {
  text-decoration: none;
  color: #fff;
  font-size: 18px;
  transition: color 0.3s ease, background-color 0.3s ease;
  padding: 8px 16px;
  border-radius: 4px;
}


.nav-links a:hover {
    color: white; /* Text color on hover */
    background: linear-gradient(135deg, #4a4a8a, #6767b3); /* Gradient matching the button's color */
    border-radius: 30%;
}

        /* Active/Current Page Link */
        .nav-links a.active {
            color: #ff9800;
            background-color:linear-gradient(135deg, #4a4a8a, #6767b3); 
        }

@media screen and (max-width: 768px) {
  .navbar {
    flex-direction: column;
  }

  .nav-links {
    flex-direction: column;
    align-items: center;
    margin-top: 10px;
  }

  .nav-links li {
    margin-left: 0;
    margin-bottom:0;
  }
}
.section1{
  background-image: url("images/home.jpg");
  height: 665px;
  width:100%;
  background-repeat: no-repeat;
      background-size: cover; 
      background-position: center;
      margin-top: -70px;
}
.section2{
  background-image: url("");
  height: 500px;
  width:100%;
  background-repeat: no-repeat;
      background-size: cover; 
      background-position: center;
      display: flex;
    justify-content: center;
    align-items: center;
}
.section2 * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

.section2 {
    font-family: "Josefin Sans", sans-serif;
    background-color: #f9f9f9;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.section2 .info-cards {
    display: flex;
    gap: 20px;
}

.section2 .card {
    background-color: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    padding-top: -10px;
    text-align: center;
    width: 300px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.section2 .card:hover {
    transform: translateY(-10px);
    box-shadow: 0 8px 16px  #4a4a8a;
}

.section2 .icon {
    width: 50px;
    height: 50px;
    margin-bottom: 20px;
    margin-left: 23%;
    margin-top: 0px;
    align-items: center;
}

.section2 .icon-rides {
    background: url('icon-rides.png') no-repeat center center;
    background-size: contain;
}

.section2 .icon-trust {
    background: url('icon-trust.png') no-repeat center center;
    background-size: contain;
}

.section2 .icon-scroll {
    background: url('icon-scroll.png') no-repeat center center;
    background-size: contain;
}

.section2 h3 {
    color: #003c57;
    margin-top: 70px;
    margin-bottom: 15px;
    font-size: 1.2em;
}

.section2 p {
    color: #7a7d82;
    font-size: 0.95em;
    line-height: 1.6;
}

.section2 .card:hover h3 {
    background: linear-gradient(135deg, #4a4a8a, #6767b3);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    transition: color 0.3s ease;
}

.section2 .card:hover p {
    color: #555;
    transition: color 0.3s ease;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
    }
}

.section2 .card:hover .icon {
    animation: pulse 0.6s ease-in-out;
}
.section3 .help-center {
    max-width: 1000px;
    margin: 0 auto;
    text-align: center;
}

.section3 h1 {
    color: #003c57;
    margin-bottom: 40px;
    font-size: 2.5rem;
}

.section3 .help-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: space-between;
}
.section3 {
    font-family:"Josefin Sans", sans-serif;
    padding:2%;
}
.section3 .help-item {
    background-color: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    width: 48%;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    position: relative;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
}

.section3 .help-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.7);
}

.section3 .help-item h3 {
    color: #003c57;
    margin-bottom: 10px;
    font-size: 1.2em;
}

.section3 .help-item p {
    color: #7a7d82;
    font-size: 0.95em;
    line-height: 1.6;
    height: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.section3 .read-more {
    color: #00aaff;
    font-size: 0.9em;
    text-decoration: none;
    position: absolute;
    bottom: 15px;
    right: 20px;
    transition: color 0.3s ease;
}

.section3 .read-more:hover {
    color: #0077cc;
}

.section3 .help-item:hover h3 {
    background: linear-gradient(135deg, #4a4a8a, #6767b3);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    transition: color 0.3s ease;
}

.section3 .help-item:hover p {
    color: #555;
    transition: color 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.section3 .help-item {
    animation: slideIn 1s ease-in-out;
}
footer {
            background-color: white;
            color: white;
            padding: 40px 0;
            position: relative;
            text-align: center;
            font-family: "Josefin Sans", sans-serif;
        }

        .footer-container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
            padding: 0 20px;
        }

        .footer-section {
            flex: 1;
            min-width: 200px;
            max-width: 300px;
            padding: 20px;
            background:linear-gradient(135deg, #8EC5FC, #E0C3FC);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .footer-section:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }

        .footer-section h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            position: relative;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .footer-section h3:after {
            content: '';
            width: 50px;
            height: 3px;
            background-color: white;
            display: block;
            margin: 5px auto 10px;
        }

        .footer-section p, 
        .footer-section ul {
            font-size: 1rem;
            margin: 10px 0;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li {
            margin: 10px 0;
        }

        .footer-section ul li a {
            color: white;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: yellow;
            text-decoration: underline;
        }

        .footer-section a {
            color: white;
            transition: color 0.3s ease;
        }

        .footer-section a:hover {
            color: yellow;
        }

        .social-icons a {
            margin: 0 10px;
            text-decoration: none;
            color: white;
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .social-icons a:hover {
            transform: scale(1.3);
            color: yellow;
        }

        .footer-bottom {
            margin-top: 20px;
            padding: 10px;
            background-color: rgba(0, 0, 0, 0.2);
            color: black;
            text-align: center;
        }

        .footer-bottom p {
            font-size: 1rem;
            margin: 0;
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-5px);
            }
        }

        .social-icons a:hover {
            animation: bounce 0.6s ease-in-out infinite;
        }

        @media screen and (max-width: 768px) {
            .footer-container {
                flex-direction: column;
                text-align: center;
            }

            .footer-section {
                margin-bottom: 20px;
            }
        }
        .moving-car {
            position: fixed; 
            bottom: -80px; 
            left: -250px;
            width: 9%;
            height: 27%;
            background-size: cover;
            z-index: 10;
            animation: car-animation 5s linear infinite;
        }

        @keyframes car-animation {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(calc(100vw + 100px)); 
            }
        }
        .why-choose-us {
    font-family: "Josefin Sans", sans-serif;
    width: 90%; 
    height: auto;
    background-color: rgba(255, 255, 255, 0.8); 
    border-radius: 10px;
    padding: 20px;
    margin: 20px auto;
    text-align: center;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.why-choose-us:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
}

.why-choose-us h2 {
    font-size: 24px;
    margin-bottom: 10px;
    color: #333;
    font-family: "Josefin Sans", sans-serif;
}

.why-choose-us p {
    font-size: 18px;
    color: #555;
    line-height: 1.5;
    font-family: "Josefin Sans", sans-serif;
}

@keyframes fadeInUp {
    0% {
        opacity: 0;
        transform: translateY(20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.why-choose-us:hover {
    animation: fadeInUp 2.5s ease forwards;
}

.section4{
    background: linear-gradient(135deg, #4a4a8a, #6767b3);
    display: flex;
    align-items: center;
}
.socials {
            list-style-type: none;
            display: flex;
            justify-content: center;
            padding: 0;
            margin: 20px 0;
        }

        .socials li {
            margin: 0 10px;
        }

        .socials li a {
            color: #000000;
            font-size: 20px;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .socials li a:hover {
            color: #000000;
        }

        .danger-btn-floating {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: red;
            color: white;
            border: none;
            padding: 30px;
            font-size: 18px;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
  </style>
  
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FlexiRide</title>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="icon" href="images\favvi.png" type="image/x-icon">

</head>
<body>

 
  <nav class="navbar">
    <div class="logo">
      <img src="images/logo1.png" alt="logo"  height="30%"; width="30%;">

    </div>
    <img src="images\name.png" alt="logo"  height="20%"; width="20%;">
    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="rides.php">Find</a></li>
      <?php if (isset($_SESSION['user_id'])): ?>
      <li><a href="post_ride.php">Post</a></li>
      <li><a href="myrides.php">MyRides</a></li>
      <li><a href="#" id="logout-link">Logout</a></li>
      <li><a href="profile.php"><i class='bx bxs-user bx-sm' ></i></a></li>
      <?php else: ?>
        <li><a href="login.php">Login</a></li>
       <?php endif; ?>
    </ul>
  </nav>
  <script>
  
    document.getElementById('logout-link').addEventListener('click', function(e) {
       
        e.preventDefault();
        
        
        var confirmed = confirm('Are you sure you want to logout?');
        
        
        if (confirmed) {
            window.location.href = 'logout.php';
        }
    });
</script>
<div class="moving-car">
        <img src="images/car3.png" height="50%" width="150%">
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
 <br/>
 <section class="section1"></section>
 <section class="section2">
    <div class="info-cards">
        <div class="card">
            <div class="icon icon-rides">
                <img src="images/1img-removebg-preview.png" alt="car" height="300%" width="300%">
            </div>
            <h3>Your pick of rides at low prices</h3>
            <p>No matter where you’re going, by bus or carpool, find the perfect ride from our wide range of destinations and routes at low prices.</p>
        </div>
        <div class="card">
            <div class="icon icon-trust">
            <img src="images/2logo.png" alt="bike" height="200%" width="250%">
            </div>
            <h3>Trust who you travel with</h3>
            <p>We take the time to get to know each of our members and bus partners. We check reviews, profiles and IDs, so you know who you’re travelling with and can book your ride at ease on our secure platform.</p>
        </div>
        <div class="card">
            <div class="icon icon-scroll">
            <img src="images/3img-removebg-preview.png" alt="bike" height="200%" width="200%">
            </div>
            <h3>Scroll, click, tap and go!</h3>
            <p>Booking a ride has never been easier! Thanks to our simple app powered by great technology, you can book a ride close to you in just minutes.</p>
        </div>
    </div>
 </section>
 
 <section class="section4">
 <div class="why-choose-us">
    <h2>Why Should You Prefer Our Website?</h2>
    <p>Our website integrates <strong>bike pooling</strong>, a feature you won't find on any other platform! With bike pooling, you can save money, reduce your carbon footprint, and make commuting easier than ever before. Whether you're looking for a ride or offering one, our platform provides a seamless experience tailored to your needs.</p>
    <p>We are committed to promoting eco-friendly and cost-effective transportation options, all while ensuring convenience and safety for our users. Ride with us and experience the future of ride-sharing today!</p>
</div>
<br/>
<br/>
<br/>
<br/>
<br/>
<br/>

 </section>
 <section class="section3">
 <div class="help-center">
  
        <h1>Bikepool Help Centre</h1>
        <div class="help-container">
            <div class="help-item">
                <h3>How do I book a Bikepool ride?</h3>
                <p >You can book a Bikepool ride on our mobile app, or on FlexiRide.in. Simply search for your destination, choose the date you want to travel and pick the Bikepool that suits you best! Some  rides can be booked instantly, while other rides require manual approval from the driver. Either way, booking a Bikepool ride is fast, simple and easy.

                </p>            
            </div>
            <div class="help-item">
                <h3>How do I publish a Bikepool ride?</h3>
                <p>Offering a Bikepool ride on FlexiRide is easy. To publish your ride, use our mobile app or FlexiRide.in. Indicate your departure and arrival points, the date and time of your departure how many passengers you can take and the price per seat. You’ll also need to choose how you want to accept bookings (either automatically or manually), and you have the option of adding any important details you think your passengers should know about. Then tap ‘Publish ride’ and you’re done!</p>         
            </div>
            <div class="help-item">
                <h3>How do I cancel my Bikepool ride?</h3>
                <p>If you have a change of plans, you can always cancel your Bikepool ride from the ‘Your rides’ section of our app. The sooner you cancel, the better. That way the driver has time to accept new passengers. The amount of your refund will depend on how far in advance you cancel. If you cancel more than 24 hours before departure, for example, you’ll receive a full refund, excluding the service fee.</p>
              
            </div>
            <div class="help-item">
                <h3>What are the benefits of travelling by Bikepool?</h3>
                <p>There are multiple advantages to Bikepooling, over other means of transport. Travelling by Bikepool is usually more affordable, especially for longer distances. Bikepooling is also more ecoeco-friendly, as sharing a Bike means there will be fewer Bikes on the road, and therefore fewer emissions. Taking a Bikepool ride is also a safe way to travel in the current times. </p>
                
            </div>
            <div class="help-item">
                <h3>How much does a Bikepool ride cost?</h3>
                <p>The costs of a Bikepool ride can vary greatly, and depend on factors like distance, time of departure, the demand of that ride and more. It is also up to the driver to decide how much to chargrge per seat, so it’s hard to put an exact price tag on a ride. Check out some of our top Bikepool destinations to get an idea of price, or start searching for your next Bikepool ride on FlexiRide.in.</p>
               
            </div>
            <div class="help-item">
                <h3>How do I start Bikepooling?</h3>
                <p>Bikepooling with FlexiRide is super easy, and free! Simply sign up for an account and tell us some basic details about yourself. Once you have a FlexiRide account, you can start booking or publishing rides directly on our app or website.</p>
                
            </div>
        </div>
    </div>

 </section>
 <br/>
 <br/>
 <br/>
 <br/>
 <br/>
 <br/>
 <br/>
 <br/>
 <br/>
 <br/>
 <br/>
 <br/>
 <br/>
 <br/>
 <br/>
 <hr>
 <footer>
         <div class="footer-container">
            <div class="footer-section about">
                <h3>About Us</h3>
                <p>We are dedicated to providing reliable and sustainable transportation solutions, making travel affordable and accessible to everyone.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="footer-section links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="feedback.php">FeedBack</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="sevices.php">Services</a></li>
                    <li><a href="queries.php">Queries</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                </ul>
            </div>
            <div class="footer-section contact">
                <h3>Contact Us</h3>
                <p>Email: flexiride247@gmail.com</p>
                <p>Phone: +91 6305737776</p>
                <p>Address: Sree Vidyanikethan,Tirupati, Andhra Pradesh,India</p>
            </div>
        </div>
        <div class="footer-content">
            <ul class="socials">
                <li><a href="#"><i class='bx bxl-facebook'></i></a></li>
                <li><a href="#"><i class='bx bxl-twitter'></i></a></li>
                <li><a href="https://www.instagram.com/flexi_ride247?igsh=b212c3Bjc2xkaWo5"><i class='bx bxl-instagram'></i></a></li>
                <li><a href="#"><i class='bx bxl-linkedin'></i></a></li>
            </ul>
        </div>
        <div class="footer-bottom">
            <p>© 2024 Travel Company | All Rights Reserved by FlexiRide</p>
        </div>
    </footer>
    <?php if (isset($_SESSION['user_id'])): ?>
    <button id="danger-link" class="danger-btn-floating" onclick="sendEmergencyAlert()">Emergency</button>
    <?php endif; ?>



<script>
   
    document.getElementById('danger-link').addEventListener('click', function(e) {
     
        e.preventDefault();
        
        var confirmed = confirm('Do you really want to share your location with your emergency emails');
        
        if (confirmed) {
            window.location.href = 'danger.php';
        }
    });
</script>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>

</body>
</html>