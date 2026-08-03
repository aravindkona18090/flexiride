<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login1.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ride Deleted</title>
    <style>
        /* Reset and General Styling */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Josefin Sans", sans-serif;
            background: #fff;
            color: #333;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
        }
        
        h1 {
            font-size: 2rem;
            margin-bottom: 20px;
            color: black;
            animation: fadeIn 1.5s ease-out;
        }

        /* Rotating Circles Loader */
        /* From Uiverse.io by mobinkakei */ 
/* From Uiverse.io by Nawsome */ 
.pl {
    background-color: #212122;
  box-shadow: 2em 0 2em rgba(0, 0, 0, 0.9) inset, -2em 0 2em rgba(255, 255, 255, 0.1) inset;
  display: flex;
  justify-content: center;
  align-items: center;
  position: relative;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  transform: rotateX(30deg) rotateZ(45deg);
  width: 14em;
  height: 14em;
  color: white;
}

.pl, .pl__dot {
  border-radius: 50%;
}

.pl__dot {
  animation-name: shadow724;
  box-shadow: 0.1em 0.1em 0 0.1em black, 0.3em 0 0.3em rgba(0, 0, 0, 0.5);
  top: calc(50% - 0.75em);
  left: calc(50% - 0.75em);
  width: 1.5em;
  height: 1.5em;
}

.pl__dot, .pl__dot:before, .pl__dot:after {
  animation-duration: 2s;
  animation-iteration-count: infinite;
  position: absolute;
}

.pl__dot:before, .pl__dot:after {
  content: "";
  display: block;
  left: 0;
  width: inherit;
  transition: background-color var(--trans-dur);
}

.pl__dot:before {
  animation-name: pushInOut1724;
  background-color: var(--bg);
  border-radius: inherit;
  box-shadow: 0.05em 0 0.1em rgba(255, 255, 255, 0.2) inset;
  height: inherit;
  z-index: 1;
}

.pl__dot:after {
  animation-name: pushInOut2724;
  background-color: var(--primary1);
  border-radius: 0.75em;
  box-shadow: 0.1em 0.3em 0.2em rgba(255, 255, 255, 0.4) inset, 0 -0.4em 0.2em #2e3138 inset, 0 -1em 0.25em rgba(0, 0, 0, 0.3) inset;
  bottom: 0;
  clip-path: polygon(0 75%, 100% 75%, 100% 100%, 0 100%);
  height: 3em;
  transform: rotate(-45deg);
  transform-origin: 50% 2.25em;
}

.pl__dot:nth-child(1) {
  transform: rotate(0deg) translateX(5em) rotate(0deg);
  z-index: 5;
}

.pl__dot:nth-child(1), .pl__dot:nth-child(1):before, .pl__dot:nth-child(1):after {
  animation-delay: 0s;
}

.pl__dot:nth-child(2) {
  transform: rotate(-30deg) translateX(5em) rotate(30deg);
  z-index: 4;
}

.pl__dot:nth-child(2), .pl__dot:nth-child(2):before, .pl__dot:nth-child(2):after {
  animation-delay: -0.1666666667s;
}

.pl__dot:nth-child(3) {
  transform: rotate(-60deg) translateX(5em) rotate(60deg);
  z-index: 3;
}

.pl__dot:nth-child(3), .pl__dot:nth-child(3):before, .pl__dot:nth-child(3):after {
  animation-delay: -0.3333333333s;
}

.pl__dot:nth-child(4) {
  transform: rotate(-90deg) translateX(5em) rotate(90deg);
  z-index: 2;
}

.pl__dot:nth-child(4), .pl__dot:nth-child(4):before, .pl__dot:nth-child(4):after {
  animation-delay: -0.5s;
}

.pl__dot:nth-child(5) {
  transform: rotate(-120deg) translateX(5em) rotate(120deg);
  z-index: 1;
}

.pl__dot:nth-child(5), .pl__dot:nth-child(5):before, .pl__dot:nth-child(5):after {
  animation-delay: -0.6666666667s;
}

.pl__dot:nth-child(6) {
  transform: rotate(-150deg) translateX(5em) rotate(150deg);
  z-index: 1;
}

.pl__dot:nth-child(6), .pl__dot:nth-child(6):before, .pl__dot:nth-child(6):after {
  animation-delay: -0.8333333333s;
}

.pl__dot:nth-child(7) {
  transform: rotate(-180deg) translateX(5em) rotate(180deg);
  z-index: 2;
}

.pl__dot:nth-child(7), .pl__dot:nth-child(7):before, .pl__dot:nth-child(7):after {
  animation-delay: -1s;
}

.pl__dot:nth-child(8) {
  transform: rotate(-210deg) translateX(5em) rotate(210deg);
  z-index: 3;
}

.pl__dot:nth-child(8), .pl__dot:nth-child(8):before, .pl__dot:nth-child(8):after {
  animation-delay: -1.1666666667s;
}

.pl__dot:nth-child(9) {
  transform: rotate(-240deg) translateX(5em) rotate(240deg);
  z-index: 4;
}

.pl__dot:nth-child(9), .pl__dot:nth-child(9):before, .pl__dot:nth-child(9):after {
  animation-delay: -1.3333333333s;
}

.pl__dot:nth-child(10) {
  transform: rotate(-270deg) translateX(5em) rotate(270deg);
  z-index: 5;
}

.pl__dot:nth-child(10), .pl__dot:nth-child(10):before, .pl__dot:nth-child(10):after {
  animation-delay: -1.5s;
}

.pl__dot:nth-child(11) {
  transform: rotate(-300deg) translateX(5em) rotate(300deg);
  z-index: 6;
}

.pl__dot:nth-child(11), .pl__dot:nth-child(11):before, .pl__dot:nth-child(11):after {
  animation-delay: -1.6666666667s;
}

.pl__dot:nth-child(12) {
  transform: rotate(-330deg) translateX(5em) rotate(330deg);
  z-index: 6;
}

.pl__dot:nth-child(12), .pl__dot:nth-child(12):before, .pl__dot:nth-child(12):after {
  animation-delay: -1.8333333333s;
}

.pl__text {
  font-size: 0.75em;
  max-width: 5rem;
  position: relative;
  text-shadow: 0 0 0.1em var(--fg-t);
  transform: rotateZ(-45deg);
}

/* Animations */
@keyframes shadow724 {
  from {
    animation-timing-function: ease-in;
    box-shadow: 0.1em 0.1em 0 0.1em black, 0.3em 0 0.3em rgba(0, 0, 0, 0.3);
  }

  25% {
    animation-timing-function: ease-out;
    box-shadow: 0.1em 0.1em 0 0.1em black, 0.8em 0 0.8em rgba(0, 0, 0, 0.5);
  }

  50%, to {
    box-shadow: 0.1em 0.1em 0 0.1em black, 0.3em 0 0.3em rgba(0, 0, 0, 0.3);
  }
}

@keyframes pushInOut1724 {
  from {
    animation-timing-function: ease-in;
    background-color: var(--bg);
    transform: translate(0, 0);
  }

  25% {
    animation-timing-function: ease-out;
    background-color: var(--primary2);
    transform: translate(-71%, -71%);
  }

  50%, to {
    background-color: var(--bg);
    transform: translate(0, 0);
  }
}

@keyframes pushInOut2724 {
  from {
    animation-timing-function: ease-in;
    background-color: var(--bg);
    clip-path: polygon(0 75%, 100% 75%, 100% 100%, 0 100%);
  }

  25% {
    animation-timing-function: ease-out;
    background-color: var(--primary1);
    clip-path: polygon(0 25%, 100% 25%, 100% 100%, 0 100%);
  }

  50%, to {
    background-color: var(--bg);
    clip-path: polygon(0 75%, 100% 75%, 100% 100%, 0 100%);
  }
}        /* Success Message Styling */
        #success-message {
            display: none;
            color: #000;
            font-size: 20px;
            margin-top: 20px;
            animation: fadeIn 2s ease-out;
        }

        #success-message p {
            margin-bottom: 20px;
            font-weight: bold;
            font-size: 1.2rem;
        }

        a button {
            background-color: #000;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, background-color 0.3s ease;
        }

        a button:hover {
            background-color: #2980b9;
            transform: scale(1.05);
        }

        /* Animations */
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&family=Sofadi+One&display=swap" rel="stylesheet">    
</head>
<body>
    <div class="load">
        <div class="pl">
	<div class="pl__dot"></div>
	<div class="pl__dot"></div>
	<div class="pl__dot"></div>
	<div class="pl__dot"></div>
	<div class="pl__dot"></div>
	<div class="pl__dot"></div>
	<div class="pl__dot"></div>
	<div class="pl__dot"></div>
	<div class="pl__dot"></div>
	<div class="pl__dot"></div>
	<div class="pl__dot"></div>
	<div class="pl__dot"></div>
	<div class="pl__text">Deleting…</div>
</div>
    </div>
    <div id="success-message">
        <p>Ride Deleted Successfully!</p>
        <a href="myrides.php"><button>Back to Dashboard</button></a>
    </div>

    <script>
        // Simulate loading process
        setTimeout(() => {
            // Hide loader and show success message after 2 seconds
            document.querySelector(".load").style.display = "none";
            document.getElementById("success-message").style.display = "block";
        }, 5000); // Simulate a 2-second loading time
    </script>
</body>
</html>
