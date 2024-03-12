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
      background-color: #f8eded;
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
      background-color: #fff;
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
        <h2>Welcome {{$details['name']}} ,&nbsp;</strong></span></h2>
        <p>Its official, you’re now a part of the GMP community!!</p><br>
        <p>Get ready to enjoy the best delivery services at your fingertips.
            Think of us as your best companions when it comes to reliable, swift and efficient deliveries.
            Can not wait? Log on to your account and request a delivery now.
            Feel free to contact us if you have any problem or suggestion.
            Email us via support@gavicemarketplace.com or call +2348137519848
        </p><br>
      </div>
    </div>
    <br>
    <p class="inspire">Get inspired on social</p>
    <div class="social-icons">
      <a href=""><img src="{{ asset('assets/img/twitter.png') }}" alt="twitter" class="img-fluid"></a>
      <a href=""><img src="{{ asset('assets/img/facebook.png') }}" alt="facebook" class="img-fluid"></a>
      <a href=""><img src="{{ asset('assets/img/linkedin.png') }}" alt="linkedin" class="img-fluid"></a>
    </div>
    <p class="policy">Need help? Try our <b>HelpCenter</b> or reach out to <b><a class="link"
          href="mailto:support@gavicemarketplace.com">support@gavicemarketplace.com</a></b>. if you would rather
      not receive this kind of email, you can <b>unsubscribe</b> or <b>manage your email preferences</b>
      By continuing to use GMP, you are accepting our <b>Terms of Service</b> and <b>Privacy
        policy</b>
    </p>

  </div>
  </div>
  </div>

</body>

</html>
