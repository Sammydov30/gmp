<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/x-icon" href="{{ asset('logo.png') }}" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,200;0,300;0,700;1,200&display=swap" rel="stylesheet">
  <title>OTP Verification</title>
</head>
<style>
  body {
    background-color: #f8f8f8;
  }

  b {
    color: #ff7b00;
    font-weight: 600;
  }

  .policy {
    color: #95989b;
  }

  .btn-block {
    background-color: #fb7b00;
    border-radius: 20px;
    color: #ffffff;
    width: 50%;
  }
  .link{
    text-decoration: none;
    color:#ff7b00;
  }
  p{
    font-family: 'Poppins', sans-serif;
  }
</style>

<body>

  <div class="row justify-content-center align-items-center" style="height: 100vh;">
    <div class="col-md-6">
      <div style="background-color: #ffffff; border: none;" class="card"></div>
      <div class="container mt-5">
        <img src="{{ asset('assets/img/gavice.png') }}" alt="Gavice" class="img-fluid" style="height:80px;"><br>
        <div style="background-color: #ffffff; border: 1px solid #ffffff;" class="card">
          <div class="card-body">
            <p>Hi there,</p>
            <p>{{$details['subject']}}</p><br>
            <p>Your GMP Admin OTP is:</p>
            <p>{{$details['otp']}}</p>
          </div>
        </div><br>
        <p style="font-weight: 700;">Get inspired on social</p>
        <div class="social-icons mt-3">
          <a href=""><img src="{{ asset('assets/img/twitter.png') }}" alt="twitter" class="img-fluid"></a>
          <a href=""><img src="{{ asset('assets/img/facebook.png') }}" alt="facebook" class="img-fluid"></a>
          <a href=""><img src="{{ asset('assets/img/linkedin.png') }}" alt="linkedin" class="img-fluid"></a>
        </div>
        <p class="policy">Need help? Try our <b>HelpCenter</b> or reach out to <b><a class="link" href="#">support@gavicemarketplace.com</a></b>. if you would rather
          not receive this kind of email, you can <b>unsubscribe</b> or <b>manage your email preferences</b></p>
        <p class="policy">By continuing to use GMP, you are accepting our

          <a href=""><b>Terms of Service</b></a> and
          <a href=""><>Privacy policy</b></a>.
        </p>
        <p class="policy">GFHV+9GX, Ojike St, Bende Rd, &, Umuahia 440236, Abia</p>
      </div>
    </div>
  </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
