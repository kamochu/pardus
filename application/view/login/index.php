<?php 
use Ssg\Core\Config;
?>

<!-- echo out the system feedback (error and success messages) -->
<?php $this->renderFeedbackMessages(); ?>


<form class="form-signin"  action="<?php echo Config::get('URL'); ?>login/login" method="post">
    <h2 class="form-signin-heading">Please sign in</h2>
    <label for="user_name" class="sr-only">Email Address or Username</label>
    <input type="text" id="user_name" name="user_name" class="form-control" placeholder="Username or Email address" required autofocus>
    <label for="user_password" class="sr-only">Password</label>
    <input type="password" id="user_password" name="user_password" class="form-control" placeholder="Password" required>
   <!-- <div class="checkbox">
      <label for="set_remember_me_cookie" class="remember-me-label">
        <input type="checkbox" name="set_remember_me_cookie" class="remember-me-checkbox" /> Remember me for 2 weeks
      </label>
    </div>-->
    <button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
</form>
