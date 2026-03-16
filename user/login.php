<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>Smart People Global - Login</title>
  <?php include('nav/links.php'); ?>
</head>

<body>
  <div class="wrapper">
    <!-- Sidebar -->

    <!-- End Sidebar -->

    <div class="">


      <div class="container-fluid">
        <div class="page-inner" style="margin: auto;">
          <div class="col-md-4 offset-md-3">
            <div class="card">
              <div class="card-body">
                <div class="">
                  <div class="card card-profile">
                    <div class="card-header" style="background-image: url('assets/img/blogpost.jpg')">
                      <div class="profile-picture">
                        <div class="avatar avatar-xl">
                          <img src="assets/img/spg-logo.jpg" alt="..." class="avatar-img rounded-circle" />
                        </div>
                      </div>
                    </div>
                    <div class="card-body">
                      <div>
                        <h5>Welcome to</h5>
                        <h3>Smart People Global</h3>
                        <hr>
                        <p class="text-center">Login to Access Account, changes to your Acconnt as per your Need.</p>
                      </div>
                      <div class="user-profile ">
                        <form>
                          <div class="row">
                            <div class="form-group ">
                              <label for="basic-url">Username: <span class="comp">*</span></label>
                              <div class="input-icon">
                                <span class="input-icon-addon">
                                  <i class="fa fa-user"></i>
                                </span>
                                <input type="text" class="form-control" placeholder="Username" />
                              </div>
                              <!-- <small id="emailHelp2" class="form-text text-muted">We'll never share your email with anyone else.</small> -->
                            </div>
                            <div class="form-group ">
                              <label for="basic-url">Password: <span class="comp">*</span></label>
                              <div class="input-icon">
                                <span class="input-icon-addon">
                                  <i class="fa fa-eye"></i>
                                </span>
                                <input type="password" class="form-control" placeholder="Password" />
                              </div>
                              <!-- <small id="emailHelp2" class="form-text text-muted">We'll never share your email with anyone else.</small> -->
                            </div>
                            <div class="col-md-12 form-group">
                              <label for="captcha">Enter CAPTCHA:</label>
                              <div class="input-wrapper">
                                <img src="captcha.php" alt="CAPTCHA Image">
                                <input type="text" id="captcha" class="form-control" name="captcha" required
                                  placeholder="Enter captcha">
                              </div>
                            </div>
                            <div class="form-check">
                              <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault" />
                              <label class="form-check-label" for="flexCheckDefault">
                                REMEMBER ME
                              </label>
                            </div>

                          </div>
                          <div class="form-group">
                            <div class="view-profile">
                              <a href="#" class="btn btn-secondary w-100">Login</a>
                            </div>
                          </div>
                        </form>
                        <p>Forgot your login credentials ? <a href="reset_password.php">Reset Password</a></p>
                        <!-- <div class="text-center social-media">
                          <a class="btn btn-info btn-twitter btn-sm btn-link" href="#">
                            <span class="btn-label just-icon"><i class="icon-social-twitter"></i>
                            </span>
                          </a>
                          <a class="btn btn-primary btn-sm btn-link" rel="publisher" href="#">
                            <span class="btn-label just-icon"><i class="icon-social-facebook"></i>
                            </span>
                          </a>
                          <a class="btn btn-danger btn-sm btn-link" rel="publisher" href="#">
                            <span class="btn-label just-icon"><i class="icon-social-instagram"></i>
                            </span>
                          </a>
                        </div> -->

                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

      <?php ?>
</body>

</html>