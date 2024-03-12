<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gavice</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('logo.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,200;0,300;0,700;1,200&display=swap"
    rel="stylesheet">
  <style>
    body {
      background-color: #f8f8f8;
      font-family: 'Poppins', sans-serif;
      height: auto;
    }

    .container {
      max-width: 800px;
      margin: 0 auto;
    }

    .img {
      width: 100px;
    }

    .card {
      background-color: #f8eded;
      border: 1px solid #ffffff;
      border-radius: 10px;
      padding: 20px;
    }

    .card-body {
      padding: 20px;
    }

    .btn-block {
      background-color: #fb7b00;
      border-radius: 20px;
      color: #ffffff;
      width: 100%;
      padding: 10px;
      text-align: center;
      font-weight: 600;
      margin-top: 20px;
      text-decoration: none;
    }


    .link {
      text-decoration: none;
      color: #ff7b00;
    }

    .social-icons img {
      width: 30px;
      height: 30px;
      margin-right: 10px;
    }

    .policy {
      color: #95989b;
      font-weight: 400;
      /* margin-top: 20px; */
      line-height: 35px;
    }

    b {
      color: #ff7b00;
      font-weight: 600;
    }

    .inspire {
      font-weight: 700;
    }
  </style>
</head>

<body>
  <div class="container">
    <img src="{{ asset('gmp-logo.png') }}" alt="Gavice Logistics" class="img">
    <div class="card">
      <div class="card-body">
        <p>Hi there,</p>
        <p>{{$details['subject']}}</p><br>
        <p>Your GMP Admin OTP is:</p>
        <p style="font-weight:700; font-size: 30px; font-style:italic;">{{$details['otp']}}</p>
      </div>
    </div>
    <br>

  </div>
  </div>
  </div>

</body>

</html>
