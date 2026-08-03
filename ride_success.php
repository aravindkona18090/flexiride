<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submiting Your Ride</title>
    <link rel="stylesheet" href="ride_success.css"> 
</head>
<body>
    <div class="load">
    <h1>Submiting Your Ride</h1>

<div style="margin-left:25%;">
  <svg class="loader" viewBox="0 0 48 30" width="48px" height="30px">
  <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1">
    <g transform="translate(9.5,19)">
      <circle class="loader_tire" r="9" stroke-dasharray="56.549 56.549"></circle>
      <g class="loader_spokes-spin" stroke-dasharray="31.416 31.416" stroke-dashoffset="-23.562">
        <circle class="loader_spokes" r="5"></circle>
        <circle class="loader_spokes" r="5" transform="rotate(180,0,0)"></circle>
      </g>
    </g>
    <g transform="translate(24,19)">
      <g class="loader_pedals-spin" stroke-dasharray="25.133 25.133" stroke-dashoffset="-21.991" transform="rotate(67.5,0,0)">
        <circle class="loader_pedals" r="4"></circle>
        <circle class="loader_pedals" r="4" transform="rotate(180,0,0)"></circle>
      </g>
    </g>
    <g transform="translate(38.5,19)">
      <circle class="loader_tire" r="9" stroke-dasharray="56.549 56.549"></circle>
      <g class="loader_spokes-spin" stroke-dasharray="31.416 31.416" stroke-dashoffset="-23.562">
        <circle class="loader_spokes" r="5"></circle>
        <circle class="loader_spokes" r="5" transform="rotate(180,0,0)"></circle>
      </g>
    </g>
    <polyline class="loader_seat" points="14 3,18 3" stroke-dasharray="5 5"></polyline>
    <polyline class="loader_body" points="16 3,24 19,9.5 19,18 8,34 7,24 19" stroke-dasharray="79 79"></polyline>
    <path class="loader_handlebars" d="m30,2h6s1,0,1,1-1,1-1,1" stroke-dasharray="10 10"></path>
    <polyline class="loader_front" points="32.5 2,38.5 19" stroke-dasharray="19 19"></polyline>
  </g>
</svg>
</div>
    </div>
    <div id="success-message">
    <p>Ride posted successfully!</p>
    <a href="index.php"><button>Back to home page</button></a>
    </div>
    <script>
        // Simulate loading process
        setTimeout(() => {
            // After 3 seconds, hide the loader and show the success message
            document.querySelector(".load").style.display = "none"; // Hide loader
            document.getElementById("success-message").style.display = "block"; // Show success message
        }, 3000); // Simulate a loading time of 3 seconds
    </script>
</body>
</html>
